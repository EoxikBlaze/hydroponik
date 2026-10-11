// ============================================================
// UJI SENSOR: pH DFRobot Gravity Analog (SEN0161-V2)
// Koneksi: SIGNAL -> GPIO33 (ADC1), VCC -> 5V (sesuai modul), GND -> GND
//
// KALIBRASI 2 TITIK via Serial Monitor (ketik lalu Enter):
//   1. ENTERPH  -> masuk mode kalibrasi
//   2. celup ke buffer 7.0 (atau 4.0), tunggu stabil, ketik CALPH
//   3. ulangi untuk buffer titik kedua
//   4. EXITPH   -> simpan parameter ke EEPROM
// Serial Monitor: 115200 baud
// ============================================================
#include "DFRobot_PH.h"
#include <EEPROM.h>

#define PH_PIN             33
#define PH_VOLTAGE_FACTOR  (3300.0f / 4095.0f)   // ADC 12-bit, referensi 3.3V
// ===== KALIBRASI pH HASIL UJI (buffer 4.01 & 6.86 @25C) =====
// Rumus lab: pH_true = 1.009338 * pH_measured - 1.115993
#define PH_CAL_SLOPE    1.009338f
#define PH_CAL_OFFSET   (-1.115993f)

DFRobot_PH ph;

float readVoltageMV() {
    long sum = 0;
    for (int i = 0; i < 20; i++) { sum += analogRead(PH_PIN); delay(5); }
    return (float)(sum / 20) * PH_VOLTAGE_FACTOR;
}

void handleCalibration(float voltage) {
    static char cmdBuf[16];
    static byte cmdIdx = 0;
    while (Serial.available() > 0) {
        char c = Serial.read();
        if (c == '\n' || cmdIdx >= sizeof(cmdBuf) - 1) {
            cmdBuf[cmdIdx] = '\0';
            if (cmdIdx > 0) {
                ph.calibration(voltage, 25.0f, cmdBuf);
                if (strstr(cmdBuf, "EXITPH") != NULL) {
                    EEPROM.commit();
                    Serial.println("[EEPROM] Parameter kalibrasi tersimpan.");
                }
            }
            cmdIdx = 0;
        } else if (c != '\r') {
            cmdBuf[cmdIdx++] = c;
        }
    }
}

void setup() {
    Serial.begin(115200);
    delay(500);
    Serial.println("\n=== UJI pH DFRobot SEN0161-V2 ===");
    Serial.println("Perintah kalibrasi: ENTERPH / CALPH / EXITPH");

    analogReadResolution(12);
    analogSetAttenuation(ADC_11db);
    EEPROM.begin(64);
    ph.begin();
}

void loop() {
    float voltage = readVoltageMV();
    handleCalibration(voltage);
    float phMeasured = ph.readPH(voltage, 25.0f); // kompensasi 25C (tes tanpa sensor suhu)
    float phValue = PH_CAL_SLOPE * phMeasured + PH_CAL_OFFSET;

    Serial.printf("[OK] Tegangan: %.0f mV | pH terukur: %.2f | pH benar: %.2f\n", voltage, phMeasured, phValue);
    if (phValue < 0.0f || phValue > 14.0f) {
        Serial.println("[PERINGATAN] pH di luar 0-14 — probe terendam? Kalibrasi ulang?");
    }
    delay(2000);
}
