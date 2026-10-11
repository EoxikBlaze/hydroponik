// ============================================================
// UJI ALAT: RTC DS3231 (I2C-2)
// Koneksi: SDA -> GPIO16, SCL -> GPIO17, VCC -> 3.3V/5V, GND -> GND
// (pasang baterai CR2032 agar waktu tetap jalan saat mati)
// Serial Monitor: 115200 baud
// ============================================================
#include <Wire.h>
#include <RTClib.h>

#define SDA2_PIN 16
#define SCL2_PIN 17

TwoWire     I2C_2 = TwoWire(1);
RTC_DS3231  rtc;
bool        rtcOk = false;

void setup() {
    Serial.begin(115200);
    delay(500);
    Serial.println("\n=== UJI RTC DS3231 ===");

    I2C_2.begin(SDA2_PIN, SCL2_PIN, 100000);
    I2C_2.setTimeOut(20);

    if (rtc.begin(&I2C_2)) {
        rtcOk = true;
        if (rtc.lostPower()) {
            Serial.println("[INFO] Daya cadangan pernah habis — waktu diisi dari waktu compile.");
            rtc.adjust(DateTime(F(__DATE__), F(__TIME__)));
        }
        Serial.println("[OK] DS3231 terdeteksi.");
    } else {
        Serial.println("[GAGAL] DS3231 tidak terdeteksi! Cek SDA2/SCL2 (GPIO16/17).");
    }
}

void loop() {
    if (!rtcOk) { delay(2000); return; }

    DateTime now = rtc.now();
    Serial.printf("[OK] %04d-%02d-%02d %02d:%02d:%02d | Suhu chip: %.1f C\n",
                  now.year(), now.month(), now.day(),
                  now.hour(), now.minute(), now.second(),
                  rtc.getTemperature());
    delay(1000);
}
