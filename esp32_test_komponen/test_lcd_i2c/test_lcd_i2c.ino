// ============================================================
// UJI ALAT: LCD 20x4 I2C (hd44780 + backpack PCF8574)
// Koneksi: SDA -> GPIO21, SCL -> GPIO22, VCC -> 5V, GND -> GND
// Tes: scan alamat I2C lalu tampilan 4 baris + counter
// Serial Monitor: 115200 baud
// ============================================================
#include <Wire.h>
#include <hd44780.h>
#include <hd44780ioClass/hd44780_I2Cexp.h>

#define SDA_PIN 21
#define SCL_PIN 22

hd44780_I2Cexp lcd;
bool           lcdOk = false;

void scanI2C() {
    Serial.println("Scan bus I2C...");
    int found = 0;
    for (byte a = 0x03; a < 0x78; a++) {
        Wire.beginTransmission(a);
        if (Wire.endTransmission() == 0) {
            Serial.printf("  -> Perangkat ditemukan di alamat 0x%02X\n", a);
            found++;
        }
    }
    if (found == 0) {
        Serial.println("[GAGAL] Tidak ada perangkat I2C! Cek SDA/SCL/VCC/GND.");
    }
}

void setup() {
    Serial.begin(115200);
    delay(500);
    Serial.println("\n=== UJI LCD 20x4 I2C ===");

    Wire.begin(SDA_PIN, SCL_PIN);
    Wire.setTimeOut(20);
    scanI2C();

    if (lcd.begin(20, 4) == 0) {
        lcdOk = true;
        lcd.backlight();
        lcd.clear();
        lcd.setCursor(0, 0); lcd.print("LCD 20x4 TERDETEKSI");
        lcd.setCursor(0, 1); lcd.print("Baris 2: OK");
        lcd.setCursor(0, 2); lcd.print("Baris 3: OK");
        lcd.setCursor(0, 3); lcd.print("Baris 4: OK");
        Serial.println("[OK] LCD terdeteksi, tampil 4 baris.");
    } else {
        Serial.println("[GAGAL] LCD tidak terdeteksi (alamat 0x27 / 0x3F?).");
    }
}

void loop() {
    if (!lcdOk) { delay(2000); return; }
    static unsigned long n = 0;
    lcd.setCursor(14, 3); lcd.printf("%6lu", n++);
    delay(1000);
}
