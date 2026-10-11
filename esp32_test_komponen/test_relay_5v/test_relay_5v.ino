// ============================================================
// UJI RELAY 5V 1-CHANNEL  (BUKAN SSR — modul relay elektromekanik)
//
// KONEKSI modul:
//   DC+ / VCC -> 5V   (koil relay butuh 5V)
//   DC- / GND -> GND
//   IN / CH1  -> GPIO25
// KONEKSI beban (terminal biru):
//   COM + NO  = sambung saat relay ON   (pakai ini untuk solenoid/valve)
//   COM + NC  = sambung saat relay OFF
//
// PERILAKU: ON 5 detik -> OFF 5 detik -> berulang (bunyi "klik" tiap 5 detik)
//
// Serial Monitor 115200 (Newline):
//   '1' = ON paksa
//   '0' = OFF paksa
//   'a' = balik mode auto (ON/OFF tiap 5 detik)
//
// Relay 5V umum = TRIGGER LOW (active-LOW): IN LOW = relay ON (coil nyala).
//   Kalau hasilnya terbalik (relay ON saat seharusnya OFF),
//   ubah RELAY_ON_LEVEL menjadi HIGH.
// Kalau relay tidak mau klik sama sekali:
//   - pastikan VCC benar-benar 5V dan GND satu dengan ESP32
//   - coba jumper VCC modul ke 3.3V (sebagian modul optocoupler butuh level IN 5V,
//     dengan VCC 3.3V logika ESP32 cukup untuk trigger)
// ============================================================

#define RELAY_PIN       25    // IN / CH1 modul relay
#define RELAY_ON_LEVEL  LOW   // modul relay 5V umum = trigger LOW (active-LOW)
#define INTERVAL_MS     5000  // ganti durasi ON/OFF di sini

#define RELAY_OFF_LEVEL ((RELAY_ON_LEVEL == LOW) ? HIGH : LOW)

bool          relayOn  = false;
bool          modeAuto = true;
unsigned long lastToggle = 0;

void setRelay(bool on) {
    relayOn = on;
    digitalWrite(RELAY_PIN, on ? RELAY_ON_LEVEL : RELAY_OFF_LEVEL);
    Serial.printf("[RELAY] %s\n", on ? "ON  (COM-NO menyambung, harus terdengar klik)"
                                     : "OFF (COM-NO putus)");
}

void setup() {
    Serial.begin(115200);
    delay(500);
    Serial.println("\n=== UJI RELAY 5V (BUKAN SSR) ===");
    Serial.println("Koneksi: DC+ -> 5V | DC- -> GND | IN/CH1 -> GPIO25 | beban: COM+NO");
    Serial.println("Mode auto: ON 5 detik / OFF 5 detik.");
    Serial.println("Perintah serial: '1' = ON paksa, '0' = OFF paksa, 'a' = auto");

    pinMode(RELAY_PIN, OUTPUT);
    digitalWrite(RELAY_PIN, RELAY_OFF_LEVEL);   // kondisi awal OFF (aman)
    delay(1000);

    setRelay(true);            // mulai dengan ON
    lastToggle = millis();
}

void loop() {
    if (Serial.available()) {
        char c = Serial.read();
        if (c == '1')      { modeAuto = false; setRelay(true); }
        else if (c == '0') { modeAuto = false; setRelay(false); }
        else if (c == 'a') {
            modeAuto = true;
            lastToggle = millis();
            Serial.println("[RELAY] Mode AUTO aktif");
        }
    }

    if (modeAuto && millis() - lastToggle >= INTERVAL_MS) {
        lastToggle = millis();
        setRelay(!relayOn);
    }
}
