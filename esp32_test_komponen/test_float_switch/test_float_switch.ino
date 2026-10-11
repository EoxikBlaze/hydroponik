// ============================================================
// UJI SENSOR: Float Switch Magnetik Batang (2 pelampung / 2 titik)
// Sensor: batang stainless + 2 pelampung (PANJANG & PENDEK) + 6 kabel (3 merah + 3 hitam)
//
// POSISI TERPASANG: kepala drat DI BAWAH, batang menghadap ke ATAS
//   - pelampung PANJANG (dekat kepala) = posisi RENDAH
//   - pelampung PENDEK  (ujung batang)  = posisi TINGGI
//
// >>> PEMETAAN PIN TIDAK DITENTUKAN DI TEST INI <<<
//   GPIO27 dan GPIO14 dipanggil SWITCH A dan SWITCH B saja.
//   Sketsa versi sebelumnya malah menulis TIGA versi yang saling
//   bertentangan dalam satu file (ada yang bilang GPIO27 = bawah,
//   ada yang bilang GPIO14 = bawah), sehingga membingungkan.
//
//   Untuk MENENTUKAN mana bawah dan mana atas, flash dulu:
//       esp32_test_komponen/test_identifikasi_switch/
//   Test itu menyimpulkan sendiri pin mana yang posisi rendah,
//   lalu bilang apakah cocok dengan definisi firmware utama.
//
// KONEKSI tiap switch: pakai 2 kabel (COM + NO)
//   COM -> GND, NO -> GPIO (ESP32 pakai INPUT_PULLUP internal, cukup ke GND)
//   Kalau tiap switch punya 3 kabel (COM/NC/NO): pakai COM + NO, NC dibiarkan.
//
// CARA KENALI 6 KABEL (multimeter mode continuity/bunyi):
//   1. Pisahkan ujung semua kabel. Probe 1 -> GND bersama.
//   2. Probe 2 sentuh satu kabel, lalu GESER satu pelampung naik-turun
//      melewati titik reed. Kabel yang bunyi = milik pelampung itu.
//   3. Ulangi untuk pelampung satunya.
//   4. Tentukan pin mana yang terbaca HIGH -> pakai test_identifikasi_switch.
//
// Logika: kedua switch sama - terendam (pelampung naik) = pin HIGH,
//   kering = pin LOW. Jika terbalik, ganti WET_LEVEL jadi LOW.
//
// Serial Monitor: 115200 baud, Newline (tampilan otomatis tiap 500 ms)
// ============================================================
#define SWITCH_A_PIN  27   // SWITCH A - peran (bawah/atas) -> test_identifikasi_switch
#define SWITCH_B_PIN  14   // SWITCH B

#define WET_LEVEL     HIGH // terendam = pin HIGH (uji 3 titik 2026-10-08)

void setup() {
    Serial.begin(115200);
    delay(500);
    Serial.println("\n=== UJI FLOAT SWITCH MAGNETIK (2 titik) ===");
    Serial.println("A = GPIO27, B = GPIO14 (mana bawah/atas belum diketahui)");
    Serial.println("Untuk identifikasi pin: flash test_identifikasi_switch");
    Serial.println("Geser pelampung untuk uji.");
    Serial.println();
    pinMode(SWITCH_A_PIN, INPUT_PULLUP);
    pinMode(SWITCH_B_PIN, INPUT_PULLUP);
}

void loop() {
    int  rawA = digitalRead(SWITCH_A_PIN);
    int  rawB = digitalRead(SWITCH_B_PIN);
    bool a = (rawA == WET_LEVEL);   // true = terendam
    bool b = (rawB == WET_LEVEL);

    String level;
    // Catatan: "hanya satu terendam" sebenarnya TIDAK bisa dipastikan
    // mustahil tanpa tahu mana pin bawah/atas. Kalau test_identifikasi_switch
    // sudah menentukan pemetaan, barulah salah satunya jadi "ERROR".
    if (a && b)       level = "PENUH  (keduanya terendam)";
    else if (a && b == false) level = "SEDANG (hanya A terendam)";
    else if (b && a == false) level = "SEDANG (hanya B terendam)";
    else              level = "KOSONG (kedua kering)";

    Serial.printf("[OK] A(GPIO27)=%-8s | B(GPIO14)=%-8s | %s\n",
                  a ? "TERENDAM" : "kering  ",
                  b ? "TERENDAM" : "kering  ",
                  level.c_str());
    delay(500);
}
