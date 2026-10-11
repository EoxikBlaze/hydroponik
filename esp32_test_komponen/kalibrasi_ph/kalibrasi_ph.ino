// ============================================================
// KALIBRASI pH (TERPISAH) — DFRobot Gravity SEN0161-V2
// Koneksi: AO -> GPIO33 (ADC1), VCC -> 3.3V/5V, GND -> GND
// Serial Monitor: 115200 baud, Newline
//
// Buffer NBS yang dipakai: 4.01 / 6.86 / 9.18 (25°C)
// Bilas probe dengan aquades + tepuk tisu antar buffer (jangan digosok).
//
// ===== DUA CARA KALIBRASI (PAKAI SALAH SATU) =====
//
// [A] Standar DFRobot -> tersimpan di EEPROM (ikut terbaca firmware):
//       ENTERPH -> celup buffer 6.86 -> ketik CALPH
//       -> bilas, celup buffer 4.01 -> ketik CALPH -> ketik EXITPH
//     Jika pakai cara ini, di firmware set:
//       #define PH_CAL_SLOPE    1.0f
//       #define PH_CAL_OFFSET   (0.0f)
//
// [B] Pemetaan buffer -> hasilnya disalin ke firmware:
//       celup buffer 6.86, tunggu stabil -> ketik SET686
//       celup buffer 4.01, tunggu stabil -> ketik SET401
//       celup buffer 9.18 (opsional)     -> ketik SET918
//       ketik HITUNG -> salin PH_CAL_SLOPE / PH_CAL_OFFSET ke firmware
//
// Perintah lain: STATUS, SETTEMP <suhu>, RESET, HELP
// ============================================================
#include <EEPROM.h>
#include "DFRobot_PH.h"

#define PH_PIN       33
#define EEPROM_SIZE  64     // DFRobot_PH pakai alamat 0..31

DFRobot_PH ph;
float temperatureC = 25.0;   // suhu larutan buffer (ubah via SETTEMP)

// Titik pemetaan (cara B)
bool  has686 = false, has401 = false, has918 = false;
float m686 = 0, m401 = 0, m918 = 0;   // pH terukur saat di tiap buffer

float readVoltageMV(uint8_t samples = 15) {
    uint32_t sum = 0;
    for (uint8_t i = 0; i < samples; i++) { sum += analogRead(PH_PIN); delay(10); }
    return (float)sum / samples * 3300.0f / 4095.0f;
}

float readPHMeasured() {          // sama persis dengan firmware (phMeasured)
    return ph.readPH(readVoltageMV(), temperatureC);
}

void printHelp() {
    Serial.println("\n=== PERINTAH ===");
    Serial.println("CARA A (EEPROM, ikut firmware):");
    Serial.println("  ENTERPH / CALPH / EXITPH");
    Serial.println("CARA B (pemetaan buffer -> salin ke firmware):");
    Serial.println("  SET686 | SET401 | SET918 | HITUNG");
    Serial.println("LAIN: STATUS | SETTEMP <suhu> | RESET | HELP");
}

void printStatus() {
    float v = readVoltageMV();
    float p = ph.readPH(v, temperatureC);
    Serial.println("\n=== STATUS ===");
    Serial.printf("Suhu buffer : %.1f C\n", temperatureC);
    Serial.printf("Tegangan    : %.1f mV\n", v);
    Serial.printf("pH terukur  : %.2f\n", p);
    Serial.printf("Titik simpan: 6.86=%s  4.01=%s  9.18=%s\n",
        has686 ? String(m686, 2).c_str() : "-",
        has401 ? String(m401, 2).c_str() : "-",
        has918 ? String(m918, 2).c_str() : "-");
}

void hitung() {
    if (has686 && has401) {
        // 2 titik: (m401 -> 4.01) dan (m686 -> 6.86)
        float a = (6.86f - 4.01f) / (m686 - m401);
        float b = 4.01f - a * m401;
        Serial.println("\n=== HASIL KALIBRASI (salin ke firmware) ===");
        Serial.printf("pH_true = %.6f * pH_terukur %s %.6f\n", a, b < 0 ? "-" : "+", fabs(b));
        Serial.printf("#define PH_CAL_SLOPE    %.6ff\n", a);
        Serial.printf("#define PH_CAL_OFFSET   (%.6ff)\n", b);
        Serial.println("(cek: SET401 -> " + String(a * m401 + b, 2) +
                       ", SET686 -> " + String(a * m686 + b, 2) + ")");
    } else {
        Serial.println("[!] Minimal butuh SET686 dan SET401 dulu.");
    }

    if (has686 && has918) {   // area basa (opsional, untuk 3 titik)
        float a2 = (9.18f - 6.86f) / (m918 - m686);
        float b2 = 6.86f - a2 * m686;
        Serial.printf("\nArea basa (> %.2f terukur): pH_true = %.6f*x %s %.6f\n",
            m686, a2, b2 < 0 ? "-" : "+", fabs(b2));
        Serial.println("(firmware sekarang 2 titik; untuk 3 titik bilang saja)");
    }
}

void handleCommand(String cmd, float measured) {
    cmd.trim();
    if (cmd.length() == 0) return;
    String up = cmd; up.toUpperCase();

    if (up == "HELP")          { printHelp(); return; }
    if (up == "STATUS")        { printStatus(); return; }

    if (up == "SET686") { has686 = true; m686 = measured; Serial.printf("[OK] Buffer 6.86 -> terukur %.2f\n", m686); return; }
    if (up == "SET401") { has401 = true; m401 = measured; Serial.printf("[OK] Buffer 4.01 -> terukur %.2f\n", m401); return; }
    if (up == "SET918") { has918 = true; m918 = measured; Serial.printf("[OK] Buffer 9.18 -> terukur %.2f\n", m918); return; }
    if (up == "HITUNG") { hitung(); return; }

    if (up == "RESET") {
        has686 = has401 = has918 = false;
        Serial.println("[OK] Titik pemetaan dihapus.");
        return;
    }

    if (up.startsWith("SETTEMP")) {
        int sp = cmd.indexOf(' ');
        if (sp > 0) {
            temperatureC = cmd.substring(sp + 1).toFloat();
            Serial.printf("[OK] Suhu buffer = %.1f C\n", temperatureC);
        } else {
            Serial.println("[!] Format: SETTEMP 25");
        }
        return;
    }

    // Cara A: teruskan ke library DFRobot (EEPROM)
    if (up.indexOf("ENTERPH") >= 0 || up.indexOf("CALPH") >= 0 || up.indexOf("EXITPH") >= 0) {
        float voltage = readVoltageMV();
        ph.calibration(voltage, temperatureC, (char*)cmd.c_str());
        if (up.indexOf("EXITPH") >= 0) { EEPROM.commit(); Serial.println("[OK] Kalibrasi EEPROM disimpan."); }
        return;
    }

    Serial.println("[!] Perintah tidak dikenal. Ketik HELP");
}

void setup() {
    Serial.begin(115200);
    delay(500);
    pinMode(PH_PIN, INPUT);
    analogReadResolution(12);
    analogSetPinAttenuation(PH_PIN, ADC_11db);

    EEPROM.begin(EEPROM_SIZE);
    ph.begin();

    Serial.println("\n=== KALIBRASI pH (TERPISAH) ===");
    Serial.println("Buffer: 6.86 (utama) / 4.01 / 9.18 @25C");
    printHelp();
}

void loop() {
    float measured = readPHMeasured();
    Serial.printf("pH terukur: %.2f  (suhu %.1fC)\n", measured, temperatureC);

    if (Serial.available()) {
        String cmd = Serial.readStringUntil('\n');
        handleCommand(cmd, measured);
    }
    delay(1500);
}
