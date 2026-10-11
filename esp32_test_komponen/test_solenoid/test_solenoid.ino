// ============================================================
// UJI SOLENOID VALVE (SSR) — ON / OFF OTOMATIS TIAP 5 DETIK
//
// Langsung jalan: kran dibuka 5 detik, ditutup 5 detik, begitu seterusnya.
// Status ON/OFF tercetak terus di Serial Monitor (115200 baud).
//
// KONEKSI:
//   ESP32 GPIO25 -> input SSR ("+"/PHOTOCOUPLER), GND ESP32 -> SSR ("-")
//   Sisi daya: LIVE -> SSR OUT-1, SSR OUT-2 -> kran, kaki lain kran -> NETRAL
//   (untuk Arduino Uno: ganti SSR_SOLENOID ke pin misal 7)
//
// CATATAN AMAN: sediakan jalur pembuangan air - kran terbuka tiap 5 detik!
// ============================================================
#define SSR_SOLENOID  25      // pin kontrol RELAY kran; Arduino Uno -> ganti mis. 7
#define BUZZER_PIN    26      // buzzer nyala bersamaan saat relay ON
#define RELAY_ON_LEVEL  LOW   // modul relay umum = ACTIVE-LOW (IN ke GND = ON); tukar bila active-HIGH
#define RELAY_OFF_LEVEL HIGH
#define ON_MS         5000    // lama kran terbuka
#define OFF_MS        5000    // lama kran tertutup

void setup() {
    Serial.begin(115200);
    pinMode(SSR_SOLENOID, OUTPUT);
    digitalWrite(SSR_SOLENOID, RELAY_OFF_LEVEL);   // relay TUTUP saat boot
    pinMode(BUZZER_PIN, OUTPUT);
    digitalWrite(BUZZER_PIN, LOW);
    Serial.println("=== UJI SOLENOID: ON 5 dtk / OFF 5 dtk (otomatis terus) ===");
    Serial.println("PASTIKAN air punya jalur pembuangan!");
}

void loop() {
    digitalWrite(SSR_SOLENOID, RELAY_ON_LEVEL);
    digitalWrite(BUZZER_PIN, HIGH);   // buzzer ikut nyala saat kran ON
    Serial.println("SOLENOID: ON  (kran terbuka) | buzzer NYALA");
    delay(ON_MS);

    digitalWrite(SSR_SOLENOID, RELAY_OFF_LEVEL);
    digitalWrite(BUZZER_PIN, LOW);
    Serial.println("SOLENOID: OFF (kran tertutup) | buzzer mati");
    delay(OFF_MS);
}
