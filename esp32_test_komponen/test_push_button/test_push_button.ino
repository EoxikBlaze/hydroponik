// ============================================================
// UJI PUSH BUTTON — tekan/lepas muncul di Serial Monitor
//
// KONEKSI: satu kaki tombol -> BUTTON_PIN, kaki lain -> GND
//   (pull-up internal, tidak perlu resistor tambahan)
//   ESP32: GPIO32 | Arduino Uno: ganti BUTTON_PIN ke 2 (atau pin bebas lain)
//
// CARA PAKAI: flash sketch ini -> buka Serial Monitor (115200 baud) ->
//   tekan tombolnya -> layar langsung menulis "TOMBOL DITEKAN!"
// ============================================================
#define BUTTON_PIN 32        // ESP32; Arduino Uno -> ganti mis. 2

int  jumlahTekan = 0;
bool terakhir    = true;     // true = dilepas (pullup)

void setup() {
    Serial.begin(115200);
    pinMode(BUTTON_PIN, INPUT_PULLUP);
    Serial.println("=== UJI PUSH BUTTON ===");
    Serial.print("Pin: GPIO");
    Serial.print(BUTTON_PIN);
    Serial.println(" -> GND");
    Serial.println("Tekan tombolnya sekarang...");
}

void loop() {
    bool lepas = (digitalRead(BUTTON_PIN) == HIGH);

    if (lepas != terakhir) {
        if (!lepas) {
            jumlahTekan++;
            Serial.print(">>> TOMBOL DITEKAN! (");
            Serial.print(jumlahTekan);
            Serial.println("x)");
        } else {
            Serial.println("    tombol dilepas");
        }
        terakhir = lepas;
        delay(30);   // anti-getar sederhana
    }
}
