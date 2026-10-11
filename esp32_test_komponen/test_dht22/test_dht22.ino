// ============================================================
// UJI SENSOR: DHT22 (suhu & kelembaban udara)
// Koneksi: DATA -> GPIO4, VCC -> 3.3V, GND -> GND
//          (modul tanpa pull-up: tambah resistor 10k DATA->VCC)
// Serial Monitor: 115200 baud
// ============================================================
#include <DHT.h>

#define DHTPIN  4
#define DHTTYPE DHT22

DHT dht(DHTPIN, DHTTYPE);

void setup() {
    Serial.begin(115200);
    delay(500);
    Serial.println("\n=== UJI DHT22 (Suhu & Kelembaban Udara) ===");
    dht.begin();
}

void loop() {
    float t = dht.readTemperature();
    float h = dht.readHumidity();

    if (isnan(t) || isnan(h)) {
        Serial.println("[GAGAL] DHT22 tidak terbaca! Cek koneksi DATA/VCC/GND.");
    } else {
        Serial.printf("[OK] Suhu: %.1f C | Kelembaban: %.1f %%\n", t, h);
    }
    delay(2000);
}
