# ESP32 Test Komponen — Uji Satu per Satu

Test sketch terpisah untuk tiap sensor & alat di proyek hidroponik.
Pin **100% sama dengan firmware utama**, jadi tinggal flash — tidak perlu pindah kabel.

| Folder | Komponen | Pin | Cara baca hasil |
|---|---|---|---|
| `test_dht22` | DHT22 suhu/lembab udara | DATA GPIO4 | Serial: suhu & kelembaban tiap 2 dtk |
| `test_ds18b20` | DS18B20 suhu air | DATA GPIO18 | Serial: jumlah probe + suhu masing-masing |
| `test_ph_dfrobot` | pH DFRobot SEN0161-V2 | SIGNAL GPIO33 | Serial: tegangan mV + nilai pH |
| `test_tds` | Sensor TDS | SIGNAL GPIO34 | Serial: ADC + tegangan + ppm |
| `test_lcd_i2c` | LCD 20x4 I2C | SDA 21 / SCL 22 | LCD tampil 4 baris + counter; Serial scan I2C |
| `test_rtc_ds3231` | RTC DS3231 | SDA 16 / SCL 17 | Serial: tanggal-jam + suhu chip |
| `test_float_switch` | Float switch bawah & atas | 27 & 14 | Serial: status terendam + level air |
| `test_identifikasi_switch` | **Tentukan pin mana BAWAH, mana ATAS** | 27 & 14 | Auto-kesimpulan di Serial + LCD: "GPIO27=BAWAH, cocok/salah dengan firmware" |
| `test_solenoid` | SSR + solenoid valve | GPIO25 | Katup buka/tutup + perintah Serial |
| `test_buzzer` | Buzzer aktif | GPIO26 | Bunyi klik/sukses/alarm |

## Cara pakai

1. Colok ESP32 via USB, lalu flash satu test:
   ```sh
   arduino-cli compile --fqbn esp32:esp32:esp32 test_dht22
   arduino-cli upload  --fqbn esp32:esp32:esp32 --port /dev/cu.usbserial-* test_dht22
   ```
   (atau buka file .ino di Arduino IDE, pilih board "ESP32 Dev Module", Upload)
2. Buka Serial Monitor **115200 baud** (Arduino IDE / `arduino-cli monitor -p <port> -c baudrate=115200`)
3. Baca baris `[OK]` / `[GAGAL]` / `[PERINGATAN]`

## Catatan penting

- **Solenoid** (`test_solenoid`): mode default auto ON/OFF 5 detik — pastikan wadah aman!
  Perintah Serial: `1` = ON, `0` = OFF, `a` = auto.
- **pH DFRobot**: kalibrasi 2 titik via Serial: `ENTERPH` → celup buffer → `CALPH` → `EXITPH` (tersimpan di EEPROM).
- **DS18B20**: butuh resistor pull-up 4.7k DATA→VCC.
- **Float switch**: terendam = **HIGH**, kering = **LOW** (`INPUT_PULLUP`, cukup sambung ke GND).
  Peran tiap pin (mana bawah/atas) ditentukan lewat `test_identifikasi_switch` — jangan dihafal
  dari catatan lama, karena dokumentasi sebelumnya sempat menulis dua versi yang bertentangan.
- Semua test independen — uji satu-satu agar tahu persis komponen mana yang bermasalah.
