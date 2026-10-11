// ============================================================
// UJI SENSOR: DS18B20 (suhu air, protokol OneWire)
// Koneksi: DATA -> GPIO18, VCC -> 3.3V, GND -> GND
//          (wajib resistor pull-up 4.7k antara DATA dan VCC)
// Serial Monitor: 115200 baud
// ============================================================
#include <OneWire.h>
#include <DallasTemperature.h>

#define ONE_WIRE_BUS 18

OneWire           oneWire(ONE_WIRE_BUS);
DallasTemperature ds18b20(&oneWire);
int               deviceCount = 0;

void setup() {
    Serial.begin(115200);
    delay(500);
    Serial.println("\n=== UJI DS18B20 (Suhu Air) ===");

    pinMode(ONE_WIRE_BUS, INPUT_PULLUP);
    ds18b20.begin();
    deviceCount = ds18b20.getDeviceCount();
    Serial.printf("Sensor terdeteksi: %d\n", deviceCount);

    if (deviceCount == 0) {
        Serial.println("[GAGAL] Tidak ada sensor! Cek DATA/VCC/GND & pull-up 4.7k.");
    } else {
        DeviceAddress addr;
        for (int i = 0; i < deviceCount; i++) {
            if (ds18b20.getAddress(addr, i)) {
                Serial.printf("  Probe #%d alamat: ", i);
                for (uint8_t j = 0; j < 8; j++) Serial.printf("%02X", addr[j]);
                Serial.println();
            }
        }
    }
}

void loop() {
    if (deviceCount == 0) { delay(2000); return; }

    ds18b20.requestTemperatures();
    for (int i = 0; i < deviceCount; i++) {
        float t = ds18b20.getTempCByIndex(i);
        if (t > -50.0f && t < 120.0f) Serial.printf("[OK] Probe #%d: %.2f C\n", i, t);
        else                          Serial.printf("[GAGAL] Probe #%d tidak terbaca (%.1f)\n", i, t);
    }
    delay(2000);
}
