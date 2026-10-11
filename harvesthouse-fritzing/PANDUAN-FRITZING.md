# PANDUAN FRITZING — Breadboard HarvestHouse (ESP32)

Diagram breadboard resmi untuk rakitan hidroponik HarvestHouse. Format file diverifikasi
terhadap dokumentasi resmi Fritzing (wiki GitHub fritzing-app) dan file `.fz` nyata dari
Fritzing 1.0.8, plus pinout ESP32 diverifikasi 3 sumber (KiCad footprint DOIT DevKit V1,
CIRCUITSTATE, espboards.dev) dan wiring tiap modul diverifikasi lewat datasheet/manual pabrikan.

## 1. Isi paket

| File | Fungsi |
|---|---|
| `harvesthouse_breadboard.fzz` | **Sketch utama** — buka ini di Fritzing (breadboard view). Semua part custom sudah tertanam di dalamnya. |
| `parts_fzpz/*.fzpz` (15 file) | Part custom per komponen — hanya perlu diimpor manual jika sketch minta (fallback). |
| `preview_breadboard.png` | Gambar preview diagram (untuk lihat cepat tanpa Fritzing). |
| `gen_parts.py` + `build_sketch.py` | Skrip generator (untuk regenerasi/rubah wiring di masa depan). |
| `parts_build/` | Hasil build part (`.fzp` + SVG) dalam format Fritzing. |

**Ekstensi resmi Fritzing** (sumber: wiki fritzing-app):
- `.fzz` = sketch yang dibuka user → isinya ZIP berisi 1 file `.fz` (XML) + part custom
  (`part.<nama>.fzp` + `svg.<view>.<nama>.svg`) — **part custom di-embed, jadi penerima
  TIDAK perlu instal apa pun**.
- `.fz` = XML sketch mentah.
- `.fzpz` = bundel 1 part (ZIP: `part.<nama>.fzp` + `svg.breadboard|schematic|pcb|icon.<nama>.svg`).

## 2. Cara membuka

1. Download Fritzing resmi: **<https://fritzing.org/download/>** (Windows/macOS/Linux, gratis).
   (Di Mac Apple Silicon saat ini tidak ada di Homebrew — unduh DMG dari situs resmi.)
2. Klik 2× `harvesthouse_breadboard.fzz` (atau File → Open).
3. Fritzing membuka **breadboard view** — semua modul sudah tertata & terkabel.
4. Jika muncul peringatan *"missing SVG file"* (jarang): import manual
   **File → Load Custom Part…** → pilih semua file di `parts_fzpz/`.

## 3. Tabel wiring FINAL (sudah diverifikasi web)

Semua jalur di diagram = tabel ini. Pin ESP32 memakai urutan fisik DOIT DevKit V1 30-pin.

| Komponen | Pin ESP32 | Kabel lain | Catatan |
|---|---|---|---|
| **DHT22** modul 3-pin | GPIO4 = DATA | VCC→3V3, GND→GND | Modul umumnya sudah ada pull-up 10k onboard (10k eksternal hanya untuk sensor polos 4-pin). **Cek silkscreen** — urutan bisa VCC-DATA-GND atau −/OUT/+. |
| **DS18B20** probe | GPIO18 = DATA | VCC→3V3, GND→GND, **R 4.7k DATA–3V3 (R2)** | Pull-up 4.7k **wajib**. Cek silkscreen modul. |
| **pH SEN0161-V2** | GPIO33 = `A` (ADC1) | `−`→GND, `+`→**5V**, `A`→GPIO33 | Label papan: **− + A** (bukan P/S/G). Supply 3.3–5.5 V, output 0–3.0 V → **tanpa divider**. |
| **TDS SEN0244** | GPIO34 = `A` (ADC1) | `−`→GND, `+`→3V3, `A`→GPIO34 | Output 0–2.3 V. GPIO34 input-only = tetap valid untuk analog. |
| **LCD 20x4 I2C** | GPIO21=SDA, GPIO22=SCL | 1 GND→GND, 2 **VCC→3V3**, 3 SDA, 4 SCL | Header GND-VCC-SDA-SCL; alamat 0x27 (clone bisa 0x3F → I2C scan). **Lihat §4.** |
| **RTC DS3231** | GPIO16=SDA, GPIO17=SCL | VCC→**3V3**, GND→GND; 32K & SQW tidak dipakai | Urutan header umum 32K-SQW-SCL-SDA-VCC-GND. Pasang CR2032. |
| **Float switch BAWAH** | GPIO14 | kutub C→GPIO14, kutub NO→GND | INPUT_PULLUP, aktif LOW (terendam = LOW). |
| **Float switch ATAS** | GPIO27 | kutub C→GPIO27, kutub NO→GND | sama. |
| **Tombol push (NO)** | GPIO32 | kaki→GPIO32 & GND | INPUT_PULLUP. |
| **Relay 5V 1-ch** | GPIO25 = IN | VCC→**5V**, GND→GND | Firmware **ACTIVE-LOW** — cek jumper H/L modul! Koil ≈71 mA **wajib dari 5V, bukan 3V3**. |
| **Buzzer aktif** | GPIO26 = IN | VCC→3V3, GND→GND | Modul ≤30 mA; daya 3V3 agar drive 3.3V penuh. |
| **Pull-up DHT (R1 10k)** | — | R1 antara DATA dan VCC DHT | Opsional untuk modul 3-pin. |
| **Sisi beban 12V** | — | PSU12V `+`→COM relay; NO→solenoid `+`; solenoid `−`→PSU `GND`; **D1 (1N4007)** paralel solenoid: **K(−) ke `+`**, A ke `−` | Terisolasi dari sisi ESP32 (lewat kontak relay). |
| **Catu daya** | pin 5V/VIN (USB) & 3V3 | Relay(5V) + pH(5V) dari VIN; DHT/DS18B20/TDS/LCD/RTC/Buzzer dari 3V3; semua GND bersama | Beban 5V total ≈130–160 mA → **adapter USB ≥1 A**. Jangan tarik koil relay dari 3V3. |

## 4. Beda dengan `PANDUAN-RAKIT.md` (penting!)

| Item | PANDUAN-RAKIT | Diagram ini | Alasan |
|---|---|---|---|
| LCD VCC | 5V | **3V3** | Backpack PCF8574 punya pull-up I2C ke VCC — dengan 5V, SDA/SCL idle 5 V melebihi toleransi GPIO ESP32 (3.6 V). 3V3 lebih aman (backlight sedikit lebih redup; alternatif: 5V + level shifter BSS138). |
| RTC VCC | 3.3V/5V | **3V3** | Alasan sama (pull-up I2C onboard). |
| Pull-up DHT 10k | wajib pasang | ditandai **opsional** | Modul 3-pin umumnya sudah ada 10k onboard. |
| Label modul pH/TDS | SIGNAL | **A** (+ `−`, `+`) | Sesuai label asli papan DFRobot. |
| Buzzer VCC | 3.3V/5V | **3V3** | Agar drive GPIO 3.3V penuh, beban 5V ringan. |

Selebihnya **100 % sama** dengan firmware (`esp32_hidroponik_terbaru`) — GPIO4/18/33/34/21/22/16/17/14/27/25/26/32 persis PANDUAN-RAKIT.

## 5. Warna kabel di diagram

| Warna | Fungsi |
|---|---|
| Hitam | GND |
| Merah | 5V / +12V |
| Oranye | 3V3 |
| Kuning | I2C (SDA/SCL) |
| Biru | Analog (pH, TDS) |
| Hijau | Sinyal digital (DHT, float switch, tombol) |
| Ungu | 1-Wire DS18B20 |
| Abu-abu | Relay IN, buzzer |

## 6. Checklist perakitan

1. Rakit sesuai tabel §3 (test tiap komponen dengan sketch di `esp32_test_komponen/`,
   Serial Monitor 115200 → cari `[OK]`).
2. Cek **jumper relay** = active-LOW (sesuai `RELAY_ON_LEVEL=LOW`).
3. **Jangan cabut probe pH dari papan board-nya**; jangan sentuh kaca pH.
4. Solenoid: pastikan ada jalur pembuangan air sebelum uji (firmware buka maks 7 menit).
5. Semua OK → flash `esp32_hidroponik_local_test` (tes meja), lalu
   `esp32_hidroponik_terbaru` (produksi).
6. Uji 3 titik float switch: kosong (27=LOW/14=LOW… lihat PANDUAN-KALIBRASI),
   sedang, penuh — firmware mengunci solenoid bila kombinasi mustahil.

## 7. Sumber verifikasi

- Format file Fritzing: <https://github.com/fritzing/fritzing-app/wiki/2.2-Sketch-file-format>,
  <https://github.com/fritzing/fritzing-app/wiki/2.1-Part-file-format>
- Pinout ESP32 DevKit V1 30-pin: KiCad `tyme2tb/doit-esp32-devkit-v1`, CIRCUITSTATE pinout PDF,
  <https://www.espboards.dev/esp32/esp32doit-devkit-v1>
- pH SEN0161-V2: <https://wiki.dfrobot.com/sen0161-v2/> · TDS SEN0244: <https://wiki.dfrobot.com/sen0244/>
- LCD I2C: <https://solderhub.com/displays/lcd2004-i2c> · RTC DS3231: <https://www.watelectronics.com/ds3231-rtc-module>
- Relay active-LOW & arus koil: datasheet SRD-05VDC-SL-C + <https://ptsolns.com/blogs/tinker-thoughts/ttb8-can-a-3-3v-signal-reliably-switch-a-5v-relay-module>
- Solenoid + flyback: <https://industrialmonitordirect.com/blogs/knowledgebase/driving-a-5v-solenoid-from-a-relay-board-wiring-and-protection>
- GPIO & ADC ESP32: <https://docs.espressif.com/projects/esp-idf/en/stable/esp32/api-reference/peripherals/gpio.html>
