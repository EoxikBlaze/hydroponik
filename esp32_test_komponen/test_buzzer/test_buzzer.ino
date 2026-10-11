// ============================================================
// UJI ALAT: Buzzer aktif
// Koneksi: IN/SIGNAL -> GPIO26, VCC -> 3.3V/5V, GND -> GND
// Pola berulang tiap ±4 detik: klik, sukses, alarm (3x)
// Serial Monitor: 115200 baud
// ============================================================
#define BUZZER_PIN 26

void beep(int onMs, int offMs) {
    digitalWrite(BUZZER_PIN, HIGH); delay(onMs);
    digitalWrite(BUZZER_PIN, LOW);  delay(offMs);
}

void setup() {
    Serial.begin(115200);
    delay(500);
    Serial.println("\n=== UJI BUZZER ===");
    pinMode(BUZZER_PIN, OUTPUT);
    digitalWrite(BUZZER_PIN, LOW);
}

void loop() {
    Serial.println("[1] Beep klik...");
    beep(40, 300);

    Serial.println("[2] Beep sukses...");
    beep(80, 60); beep(120, 300);

    Serial.println("[3] Beep alarm (3x)...");
    for (int i = 0; i < 3; i++) beep(100, 70);

    delay(3000);
}
