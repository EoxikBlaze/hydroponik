// ============================================================
// UJI SENSOR: TDS (Total Dissolved Solids) — analog + KALIBRASI
// Koneksi: SIGNAL -> GPIO34 (ADC1), VCC -> 3.3V, GND -> GND
// Serial Monitor: 115200 baud, Newline
//
// Perintah kalibrasi:
//   TDSCAL <ppm>  : kalibrasi 1 titik saat probe di larutan standar
//                   (mis. larutan 500 ppm -> ketik: TDSCAL 500)
//   TDSRESET      : kembalikan faktor ke default (1.252)
//   TDSSTATUS     : tampilkan faktor kalibrasi sekarang
//
// Faktor disimpan di EEPROM alamat 32 (SAMA dengan firmware utama),
// jadi hasil kalibrasi otomatis ikut terbaca setelah firmware utama di-flash.
// ============================================================
#include <EEPROM.h>

#define TDS_PIN    34
#define TDS_CAL_DEFAULT      1.252f
#define TDS_CAL_EEPROM_ADDR  32   // sama dengan firmware utama

float tdsCalFactor = TDS_CAL_DEFAULT;

void setup() {
    Serial.begin(115200);
    delay(500);
    analogReadResolution(12);
    analogSetAttenuation(ADC_11db);

    EEPROM.begin(64);
    EEPROM.get(TDS_CAL_EEPROM_ADDR, tdsCalFactor);
    if (isnan(tdsCalFactor) || tdsCalFactor < 0.1f || tdsCalFactor > 20.0f) {
        tdsCalFactor = TDS_CAL_DEFAULT;
    }

    Serial.println("\n=== UJI SENSOR TDS + KALIBRASI ===");
    Serial.printf("Faktor kalibrasi: %.3f\n", tdsCalFactor);
    Serial.println("Perintah: TDSCAL <ppm> | TDSRESET | TDSSTATUS");
}

int readRaw() {
    long sum = 0;
    for (int i = 0; i < 20; i++) { sum += analogRead(TDS_PIN); delay(2); }
    return (int)(sum / 20);
}

void loop() {
    int raw = readRaw();
    float v = (float)raw * (3.3f / 4095.0f);
    float ppm = (133.42f * v * v * v - 255.86f * v * v + 857.39f * v) * 0.5f * tdsCalFactor;

    if (raw <= 15) {
        Serial.printf("[PERINGATAN] ADC sangat rendah (%d) — probe di udara/kering?\n", raw);
    } else {
        Serial.printf("[OK] ADC: %d | Tegangan: %.2f V | TDS: %.0f ppm (faktor %.3f)\n",
                      raw, v, ppm, tdsCalFactor);
    }

    // Cek perintah kalibrasi dari Serial
    if (Serial.available()) {
        String cmd = Serial.readStringUntil('\n');
        cmd.trim();
        if (cmd.startsWith("TDSCAL")) {
            float truePPM = cmd.substring(6).toFloat();
            if (truePPM <= 0) {
                Serial.println("[TDS] Format: TDSCAL <ppm>, mis. TDSCAL 500");
            } else {
                float measured = (133.42f * v * v * v - 255.86f * v * v + 857.39f * v) * 0.5f * tdsCalFactor;
                tdsCalFactor *= (truePPM / measured);
                EEPROM.put(TDS_CAL_EEPROM_ADDR, tdsCalFactor);
                EEPROM.commit();
                Serial.printf("[TDS] Terbaca %.0f ppm -> dikoreksi ke %.0f ppm | Faktor baru: %.3f (tersimpan)\n",
                              measured, truePPM, tdsCalFactor);
            }
        } else if (cmd == "TDSRESET") {
            tdsCalFactor = TDS_CAL_DEFAULT;
            EEPROM.put(TDS_CAL_EEPROM_ADDR, tdsCalFactor);
            EEPROM.commit();
            Serial.printf("[TDS] Faktor direset ke %.3f\n", tdsCalFactor);
        } else if (cmd == "TDSSTATUS") {
            Serial.printf("[TDS] Faktor: %.3f | ADC: %d | TDS: %.0f ppm\n", tdsCalFactor, raw, ppm);
        } else if (cmd.length() > 0) {
            Serial.println("[TDS] Perintah tidak dikenal. Gunakan: TDSCAL <ppm> | TDSRESET | TDSSTATUS");
        }
    }

    delay(2000);
}
