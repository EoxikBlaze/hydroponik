// ============================================================
// KALIBRASI TDS (TERPISAH) — sensor TDS analog
// Koneksi: SIGNAL -> GPIO34 (ADC1), VCC -> 3.3V, GND -> GND
// Serial Monitor: 115200 baud, Newline
//
// Larutan standar: mis. 1382 ppm (NaCl) @25°C — pakai nilai label botol.
// Bilas probe dengan aquades + tepuk tisu sebelum masuk larutan standar.
//
// ===== CARA KALIBRASI (1 titik, faktor) =====
//   1. Masukkan probe ke larutan standar, buang gelembung, tunggu stabil.
//   2. Ukur suhu larutan (termometer), ketik: SETTEMP <suhu>
//        contoh: SETTEMP 28   (kompensasi suhu, sama seperti firmware)
//   3. Ketik: TDSCAL <nilai standar>
//        contoh: TDSCAL 1382
//   4. Selesai — faktor tersimpan di EEPROM alamat 32 (SAMA dengan
//      firmware utama, jadi langsung terbaca setelah firmware di-flash).
//   5. Cek: TDSSTATUS | Ulangi titik ke-2 dgn standar lain untuk perbaiki.
//
// Perintah lain: TDSRESET (kembali ke 1.252), TDSSTATUS, HELP
// ============================================================
#include <EEPROM.h>

#define TDS_PIN    34
#define TDS_CAL_DEFAULT      1.252f
#define TDS_CAL_EEPROM_ADDR  32   // sama dengan firmware utama

float tdsCalFactor  = TDS_CAL_DEFAULT;
float temperatureC  = 25.0;      // suhu larutan standar (ubah via SETTEMP)

int readRaw() {
    long sum = 0;
    for (int i = 0; i < 20; i++) { sum += analogRead(TDS_PIN); delay(5); }
    return (int)(sum / 20);
}

// Rumus sama persis dengan firmware (kompensasi suhu + kalibrasi faktor)
float readPPM(int raw) {
    float v  = (float)raw * (3.3f / 4095.0f);
    float cc = 1.0f + 0.02f * (temperatureC - 25.0f);
    float vc = v / cc;
    float ppm = (133.42f * vc * vc * vc - 255.86f * vc * vc + 857.39f * vc) * 0.5f * tdsCalFactor;
    return (ppm < 0.0f) ? 0.0f : ppm;
}

void printHelp() {
    Serial.println("\n=== PERINTAH ===");
    Serial.println("TDSCAL <ppm>   : kalibrasi saat probe di larutan standar (mis. TDSCAL 1382)");
    Serial.println("TDSRESET       : kembalikan faktor ke default (1.252)");
    Serial.println("TDSSTATUS      : lihat faktor & pembacaan");
    Serial.println("SETTEMP <suhu> : set suhu larutan standar (mis. SETTEMP 28)");
    Serial.println("HELP           : bantuan ini");
}

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

    Serial.println("\n=== KALIBRASI TDS (TERPISAH) ===");
    Serial.printf("Faktor aktif: %.3f | Suhu: %.1f C\n", tdsCalFactor, temperatureC);
    Serial.println("Masukkan probe ke larutan standar, lalu: TDSCAL <nilai>");
    printHelp();
}

void loop() {
    int raw = readRaw();
    float v = (float)raw * (3.3f / 4095.0f);
    float ppm = readPPM(raw);

    if (raw <= 15) {
        Serial.printf("[PERINGATAN] ADC rendah (%d) — probe di udara/kering?\n", raw);
    } else {
        Serial.printf("ADC: %d | %.2f V | TDS: %.0f ppm (faktor %.3f, suhu %.1f C)\n",
                      raw, v, ppm, tdsCalFactor, temperatureC);
    }

    if (Serial.available()) {
        String cmd = Serial.readStringUntil('\n');
        cmd.trim();
        if (cmd.length() == 0) return;

        if (cmd.equalsIgnoreCase("HELP")) {
            printHelp();
        } else if (cmd.equalsIgnoreCase("TDSSTATUS")) {
            Serial.printf("[STATUS] Faktor: %.3f | TDS: %.0f ppm | Suhu: %.1f C\n",
                          tdsCalFactor, ppm, temperatureC);
        } else if (cmd.equalsIgnoreCase("TDSRESET")) {
            tdsCalFactor = TDS_CAL_DEFAULT;
            EEPROM.put(TDS_CAL_EEPROM_ADDR, tdsCalFactor);
            EEPROM.commit();
            Serial.printf("[OK] Faktor direset ke %.3f (tersimpan)\n", tdsCalFactor);
        } else if (cmd.startsWith("TDSCAL")) {
            float truePPM = cmd.substring(6).toFloat();
            if (truePPM <= 0) {
                Serial.println("[!] Format: TDSCAL <ppm>, mis. TDSCAL 1382");
            } else if (raw <= 15) {
                Serial.println("[!] Probe terbaca kering — celup dulu ke larutan standar.");
            } else {
                float measured = ppm;
                tdsCalFactor *= (truePPM / measured);
                EEPROM.put(TDS_CAL_EEPROM_ADDR, tdsCalFactor);
                EEPROM.commit();
                Serial.printf("[OK] Terbaca %.0f ppm -> dikoreksi ke %.0f ppm\n", measured, truePPM);
                Serial.printf("[OK] Faktor baru: %.3f (tersimpan di EEPROM alamat %d)\n",
                              tdsCalFactor, TDS_CAL_EEPROM_ADDR);
                Serial.println("     Firmware utama otomatis memakai faktor ini.");
            }
        } else if (cmd.startsWith("SETTEMP")) {
            int sp = cmd.indexOf(' ');
            if (sp > 0) {
                temperatureC = cmd.substring(sp + 1).toFloat();
                Serial.printf("[OK] Suhu larutan = %.1f C\n", temperatureC);
            } else {
                Serial.println("[!] Format: SETTEMP 28");
            }
        } else {
            Serial.println("[!] Perintah tidak dikenal. Ketik HELP");
        }
    }

    delay(2000);
}
