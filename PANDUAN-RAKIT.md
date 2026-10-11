# PANDUAN MERAKIT — Sistem Hidroponik HarvestHouse (ESP32)

Untuk perakit alat: ikuti wiring di bawah, uji tiap komponen dengan sketch `esp32_test_komponen/`,
lalu flash firmware utama. Semua pin di firmware sudah PERSIS dengan tabel ini — tidak usah ubah kabel.

## Komponen & Wiring (board: ESP32 DevKit / DOIT 30-pin / 38-pin)

| Komponen | Pin di ESP32 | Kabel lain | Catatan penting |
|---|---|---|---|
| **DHT22** (suhu & lembab udara) | DATA → **GPIO4** | VCC → 3.3V, GND → GND | Modul tanpa pull-up: pasang resistor 10k DATA→VCC |
| **DS18B20** (suhu air) | DATA → **GPIO18** | VCC → 3.3V, GND → GND | **Wajib** resistor pull-up 4.7k DATA→VCC |
| **pH DFRobot SEN0161-V2** | SIGNAL → **GPIO33** (ADC) | VCC → **5V**, GND → GND | Probe jangan dicabut dari board-nya |
| **Sensor TDS** | SIGNAL → **GPIO34** (ADC) | VCC → 3.3V, GND → GND | |
| **LCD 20x4 I2C** | SDA → **GPIO21**, SCL → **GPIO22** | VCC → 5V, GND → GND | Alamat umum 0x27 (atau 0x3F) |
| **RTC DS3231** | SDA → **GPIO16**, SCL → **GPIO17** | VCC → 3.3V/5V, GND → GND | Pasang baterai CR2032 |
| **Float switch BAWAH** | → **GPIO14** | kabel satunya → GND | aktif LOW = terendam |
| **Float switch ATAS** | → **GPIO27** | kabel satunya → GND | aktif LOW = terendam |
| **SSR / Relay solenoid** | INPUT → **GPIO25** | VCC → 5V, GND → GND | Sisi katup sesuai spesifikasi solenoid |
| **Buzzer aktif** | IN → **GPIO26** | VCC → 3.3V/5V, GND → GND | Semua firmware: alarm & feedback suara |

> Ground semua modul disatukan (common GND ke ESP32). Jangan pakai GPIO 6-11 (dipakai flash).

## Firmware yang tersedia

| Folder | Fungsi | Untuk |
|---|---|---|
| `esp32_hidroponik_terbaru/` | **Firmware produksi** — REST ke harvesthouse.biz.id, ambil threshold dari DB, notif alert, keamanan penuh | Dipasang di alat kebun |
| `esp32_hidroponik_local_test/` | Versi uji — REST ke server lokal (WiFiManager portal), cocok untuk tes rakitan | Tes meja |
| `esp32_hidroponik_full_rest_api/` | Versi tengah — REST ke server produksi + WiFiManager | Alternatif |
| `esp32_test_komponen/` | 9 sketch uji satu-per-satu komponen (lihat README di dalamnya) | Tahap perakitan |

## Urutan perakitan yang dianjurkan
1. Rakit kabel sesuai tabel (kerjakan per komponen)
2. **Uji satu-satu** — flash `esp32_test_komponen/test_dht22`, lalu `test_ds18b20`,
   `test_ph_dfrobot`, `test_tds`, `test_lcd_i2c`, `test_rtc_ds3231`, `test_float_switch`,
   `test_solenoid`, `test_buzzer`. Semua mencetak `[OK]`/`[GAGAL]` di Serial Monitor **115200 baud**
3. Semua OK → flash `esp32_hidroponik_local_test` untuk tes sistem menyeluruh
4. Siap dipasang → flash `esp32_hidroponik_terbaru` (versi produksi)

## Cara flash (Arduino IDE)
1. Install **Arduino IDE 2.x**, lalu:
   - File → Preferences → Additional Boards URLs:
     `https://espressif.github.io/arduino-esp32/package_esp32_index.json`
   - Tools → Board → Boards Manager → install **esp32** by Espressif
2. Library yang dibutuhkan (Library Manager):
   `DFRobot_PH`, `DHT sensor library`, `Adafruit Unified Sensor`, `DallasTemperature`,
   `OneWire`, `RTClib`, `hd44780`, `ArduinoJson`, `NTPClient`, `WiFiManager`, `PubSubClient` (tidak dipakai lagi tapi aman)
3. Buka file `.ino`, pilih board **ESP32 Dev Module**, pilih port (/dev/cu.usbserial-...),
   klik **Upload**, buka Serial Monitor **115200 baud**

Atau via terminal:
```sh
arduino-cli compile --fqbn esp32:esp32:esp32 esp32_hidroponik_terbaru
arduino-cli upload  --fqbn esp32:esp32:esp32 --port /dev/cu.usbserial-0001 esp32_hidroponik_terbaru
```

## KALIBRASI pH (PENTING)

Rumus hasil kalibrasi lab (buffer 4.0 & 7.0) **sudah tertanam di kode**:

```
pH_benar = 1.111111 x pH_terukur - 1.777778
```

di kode:
```cpp
#define PH_CAL_SLOPE    1.111111f
#define PH_CAL_OFFSET   (-1.777778f)
```

- Probe DFRobot baru = tetap pakai rumus ini (hasil uji yang sudah dilakukan)
- **Kalibrasi ulang via Serial Monitor** (lebih akurat, tersimpan di EEPROM):
  kirim `ENTERPH` → celup ke buffer 7.0 (atau 4.0) tunggu stabil → kirim `CALPH` →
  ulangi buffer kedua → kirim `EXITPH`. **Setelah itu ubah `PH_CAL_SLOPE` jadi `1.0`
  dan `PH_CAL_OFFSET` jadi `0.0`** lalu upload ulang (agar tidak koreksi dobel)
- Tes cepat: flash `test_ph_dfrobot` — Serial menampilkan "pH terukur" dan "pH benar" berdampingan

## Konfigurasi & Setting firmware produksi (`esp32_hidroponik_terbaru`)

Semua yang perlu di-set ada di **satu blok di bagian atas kode** (baris ~18, kotak `KONFIGURASI ALAT`):

| Yang di-set | Di mana | Catatan |
|---|---|---|
| WiFi (nama + password) | `ssid`, `password` | jaringan **2.4 GHz** (ESP32 tidak dukung 5 GHz) |
| Server web | `website_notif_url`, `website_threshold_url` | sudah default `https://harvesthouse.biz.id` |
| API key | `website_api_key` | **harus SAMA** dengan `ESP32_API_KEY` di `.env` website |

Setelah diubah → **Compile + Upload** ulang.

### Sinkronisasi (otomatis, tanpa setting)
- **Waktu**: NTP saat boot + **koreksi otomatis dari jam server** tiap kirim data
  (RTC meleset >30 detik → dikoreksi sendiri, tampil di Serial: `[TIME] RTC dikoreksi...`)
- **Data**: kirim ke server tiap **30 detik** (`REST_SAVE_INTERVAL`), ambil threshold tiap 10 menit
- **Status**: Serial Monitor 115200 → cari `[REST] Sukses! HTTP 200` = sinkron OK

### Cek cepat kalau sinkron bermasalah
| Gejala di Serial | Artinya |
|---|---|
| `[REST] Gagal HTTP: -1` | WiFi/internet belum nyambung |
| `HTTP 403` | API key tidak cocok dengan `.env` website |
| SSL/connection error | SSL website belum aktif (Let's Encrypt) |
| `[TIME] RTC dikoreksi...` terus menerus | normal sekali-dua kali; kalau terus, RTC bermasalah |
