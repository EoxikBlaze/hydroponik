// ============================================================
// UJI TAMPILAN: LCD I2C 20x4 (hd44780 + backpack I2C)
//
// KONEKSI (bus I2C-1, sama dengan firmware utama):
//   LCD VCC -> 5V (backpack umum 5V; cek modul masing-masing)
//   LCD GND -> GND
//   LCD SDA -> GPIO21
//   LCD SCL -> GPIO22
//
// CARA UJI (Serial Monitor 115200, Newline):
//   DEMO            -> putar 3 layar uji otomatis (teks 4 baris / karakter / progress)
//   TEXT <teks>     -> tulis teks bebas di layar (baris 1)
//   CLEAR           -> bersihkan layar
//   BACK ON/OFF     -> nyalakan / matikan backlight
//   SCAN            -> pindai alamat I2C (LCD normal terdeteksi di 0x27 / 0x3F)
//   STATUS          -> info layar sekarang
//
// TES MATA (lihat langsung di LCD):
//   1. Empat baris tampil terang & jelas  -> kalau kabur: putar potensio KONTRAS di backpack
//   2. Tidak ada kotak kosong / baris campur -> gejala kabel SDA/SCL lepas/terbalik
//   3. Backlight mati/nyala sesuai perintah BACK
//   4. Layar DEMO 3 (progress) naik mulus  -> bukti CGRAM custom char jalan
// ============================================================
#include <Wire.h>
#include <hd44780.h>
#include <hd44780ioClass/hd44780_I2Cexp.h>

#define LCD_COLS 20
#define LCD_ROWS 4
#define SDA_PIN  21
#define SCL_PIN  22

hd44780_I2Cexp lcd;

bool          demoAktif  = true;
int           layarDemo  = 0;
unsigned long layarSejak = 0;
unsigned long animSejak  = 0;
unsigned long hitung     = 0;
int           faseAnim   = 0;
bool          backlightOn = true;

// 8 karakter gradasi (CGRAM 0..7): kolom kiri terisi 1..8
byte shade[8][8];

void buatShade() {
    for (int i = 0; i < 8; i++) {
        byte bar = 0;
        for (int c = 0; c <= i; c++) bar |= (byte)(0x10 >> c);
        for (int r = 0; r < 8; r++) shade[i][r] = bar;
        lcd.createChar(i, shade[i]);
    }
}

void i2cScan() {
    Serial.println("-- Pindai I2C (umum: LCD di 0x27 atau 0x3F) --");
    int ketemu = 0;
    for (byte a = 0x08; a < 0x78; a++) {
        Wire.beginTransmission(a);
        if (Wire.endTransmission() == 0) {
            Serial.printf("   Alamat I2C ditemukan: 0x%02X\n", a);
            ketemu++;
        }
    }
    if (ketemu == 0) Serial.println("   TIDAK ada perangkat I2C -> cek kabel SDA/SCL/VCC/GND!");
}

void tampilDemo() {
    lcd.clear();
    if (layarDemo == 0) {
        lcd.setCursor(0, 0); lcd.print("== LCD 20x4 OK ==");
        lcd.setCursor(0, 1); lcd.print("Baris 2 tampil normal");
        lcd.setCursor(0, 2); lcd.print("Baris 3 tampil normal");
        lcd.setCursor(0, 3); lcd.print("Hitung: ");
    } else if (layarDemo == 1) {
        lcd.setCursor(0, 0); lcd.print("UJI KARAKTER ASCII");
    } else {
        lcd.setCursor(0, 0); lcd.print("UJI CGRAM (custom)");
        lcd.setCursor(0, 1);
        for (byte i = 0; i < 8; i++) lcd.write((byte)i);
        lcd.print(" gradasi");
        lcd.setCursor(0, 2); lcd.print("Progress:");
    }
    layarSejak = millis();
    animSejak  = millis();
    faseAnim   = 0;
}

void animDemo() {
    if (layarDemo == 0) {
        // counter tiap detik di baris 4 -> bukti refresh tanpa blank
        lcd.setCursor(8, 3); lcd.print(hitung); lcd.print("    ");
    } else if (layarDemo == 1) {
        // gulir semua karakter ASCII -> bukti CGROM utuh
        if (millis() - animSejak >= 300) {
            animSejak = millis();
            for (int r = 0; r < 4; r++) {
                lcd.setCursor(0, r);
                for (int c = 0; c < LCD_COLS; c++) {
                    int k = 0x20 + ((faseAnim + r * LCD_COLS + c) % 96);
                    lcd.write((byte)k);
                }
            }
            faseAnim++;
        }
    } else {
        // bar progress naik 0 -> 100% -> bukti custom char bekerja
        if (millis() - animSejak >= 250) {
            animSejak = millis();
            int total = 20;                     // 20 kolom = 100%
            int isi   = faseAnim % (total + 1);
            lcd.setCursor(0, 3);
            for (int c = 0; c < total; c++) {
                if (c < isi)        lcd.write((byte)7);   // blok penuh
                else if (c == isi)  lcd.write((byte)(byte)(faseAnim % 8)); // ujung parsial
                else                lcd.print(' ');
            }
            lcd.setCursor(14, 0); lcd.print((isi * 5)); lcd.print("% ");   // 20 kolom = 5%/kolom
            faseAnim++;
        }
    }
}

void bacaPerintah() {
    static char buf[32];
    static byte idx = 0;
    while (Serial.available() > 0) {
        char c = Serial.read();
        if (c == '\n' || idx >= sizeof(buf) - 1) {
            buf[idx] = '\0';
            if (idx > 0) {
                char* sp = strchr(buf, ' ');
                char  cmd[10];
                int   n = 0;
                while (buf[n] && buf[n] != ' ' && n < 9) { cmd[n] = toupper(buf[n]); n++; }
                cmd[n] = '\0';
                char* arg = sp ? sp + 1 : NULL;

                if      (!strcmp(cmd, "DEMO"))  { demoAktif = true; layarDemo = 0; tampilDemo(); Serial.println("-> Mode DEMO aktif (3 layar berganti tiap 5 dtk)"); }
                else if (!strcmp(cmd, "TEXT"))  {
                    demoAktif = false; lcd.clear(); lcd.setCursor(0, 0);
                    lcd.print(arg ? arg : "(kosong)");
                    Serial.println("-> Teks ditampilkan");
                }
                else if (!strcmp(cmd, "CLEAR")) { demoAktif = false; lcd.clear(); Serial.println("-> Layar dibersihkan"); }
                else if (!strcmp(cmd, "BACK"))  {
                    bool nyala = (arg && !strcmp(arg, "OFF")) ? false : true;
                    backlightOn = nyala;
                    if (nyala) lcd.backlight(); else lcd.noBacklight();
                    Serial.printf("-> Backlight %s\n", nyala ? "NYALA" : "MATI");
                }
                else if (!strcmp(cmd, "SCAN"))   { i2cScan(); }
                else if (!strcmp(cmd, "STATUS")) {
                    Serial.printf("-> Mode: %s | layar demo: %d | backlight: %s\n",
                                  demoAktif ? "DEMO" : "manual", layarDemo + 1, backlightOn ? "nyala" : "mati");
                }
                else {
                    Serial.println("Perintah tidak dikenal. Gunakan: DEMO | TEXT <teks> | CLEAR | BACK ON/OFF | SCAN | STATUS");
                }
            }
            idx = 0;
        } else if (c != '\r') {
            buf[idx++] = c;
        }
    }
}

void setup() {
    Serial.begin(115200);
    delay(500);
    Serial.println("\n=== UJI LCD I2C 20x4 ===");

    Wire.begin(SDA_PIN, SCL_PIN);
    Wire.setClock(100000);

    int stat = lcd.begin(LCD_COLS, LCD_ROWS);
    if (stat) {
        Serial.printf("LCD GAGAL diinisialisasi (kode %d)\n", stat);
        Serial.println("Kemungkinan: kabel lepas / alamat I2C beda / LCD rusak. Pindai terus-menerus:");
        while (true) { i2cScan(); delay(3000); }
    }

    lcd.backlight();
    buatShade();
    Serial.println("LCD OK 20x4. Perintah: DEMO | TEXT <teks> | CLEAR | BACK ON/OFF | SCAN | STATUS");
    tampilDemo();
}

void loop() {
    bacaPerintah();
    if (!demoAktif) return;

    if (layarDemo == 0) {
        if (millis() - animSejak >= 1000) { animSejak = millis(); hitung++; animDemo(); }
    } else {
        animDemo();
    }

    if (millis() - layarSejak >= 5000) {
        layarDemo = (layarDemo + 1) % 3;
        tampilDemo();
    }
}
