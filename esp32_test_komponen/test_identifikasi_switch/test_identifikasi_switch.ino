// ============================================================
// UJI IDENTIFIKASI FLOAT SWITCH — mana BAWAH, mana ATAS?
//
// Tujuan: MENENTUKAN pin mana yang terhubung ke switch di posisi
// RENDAH dan mana yang ke posisi TINGGI — TANPA menebak.
// Hasilnya otomatis dibandingkan dengan definisi firmware utama.
//
// KONEKSI (sama persis dengan firmware utama - tidak perlu pindah kabel):
//   GPIO27 -> switch 1        GPIO14 -> switch 2
//   Tiap switch pakai 2 kabel: COM -> GND, NO -> GPIO
//   ESP32 pakai INPUT_PULLUP internal, cukup sambung ke GND.
//   (Kalau kabelnya 3: COM/NC/NO -> pakai COM + NO, NC dibiarkan.)
//   Sketch ini TIDAK menyentuh relay/solenoid - aman untuk air.
//
// ───────────────────────────────────────────────────────────
// DUA CARA UJI (pilih salah satu)
// ───────────────────────────────────────────────────────────
//
//  CARA A - pakai air (paling meyakinkan)
//    1. Pastikan air di bawah kedua switch. Kirim `r` di Serial Monitor.
//    2. Naikkan air PELAN-PELAN dari bawah.
//    3. Pin yang HIGH PERTAMA KALI = switch di posisi paling rendah.
//
//  CARA B - pakai tangan (tanpa air, lebih cepat)
//    1. Kirim `r` untuk mulai merekam.
//    2. Geser ke atas pelampung yang posisinya LEBIH RENDAH
//       (pelampung PANJANG, dekat kepala drat) sampai berbunyi klik.
//    3. Baru geser pelampung yang lebih tinggi (pelampung PENDEK).
//    4. Pin yang berubah PERTAMA = switch bawah.
//
//  KEDUA CARA: setelah kedua pin pernah HIGH, sketch MENYIMPULKAN
//  sendiri pin mana bawah dan mana atas, lalu bilang apakah cocok
//  dengan definisi firmware utama.
//
//  PENTING: jangan mulai dari pelampung/air posisi ATAS dulu -
//  itu membalik kesimpulannya.
//
// ───────────────────────────────────────────────────────────
// PERINTAH SERIAL (Monitor 115200 baud, "Newline")
//   r  -> reset rekaman, mulai uji dari awal
//   h  -> cetak riwayat perubahan
//   s  -> statistik (berapa kali tiap pin berubah + deteksi goyang)
//   i  -> tampilkan instruksi lagi
//   t  -> tampilkan kesimpulan sekarang
// ───────────────────────────────────────────────────────────
//
// PANDUAN MEMBACA HASIL:
//   - Pin berubah tiap beberapa detik padahal air TENANG
//     = kontak pelampung goyang / kabel longgar -> cek fisik.
//   - Kombinasi "switch atas terendam tapi bawah kering" muncul
//     berulang = gejala yang membuat firmware utama melapor ERROR.
// ============================================================

#include <Wire.h>
#include <hd44780.h>
#include <hd44780ioClass/hd44780_I2Cexp.h>

// --- Pin (WAJIB sama dengan firmware utama) ---
#define PIN_A     27   // belum diketahui bawah/atas - itu yang dicari
#define PIN_B     14
#define WET_LEVEL HIGH // terendam = HIGH, kering = LOW (uji 3 titik)

// Definisi yang sedang dipakai firmware utama - nanti dicocokkan
#define KODE_BAWAH 14
#define KODE_ATAS  27

#define DEBOUNCE_MS 80
#define MAX_LOG     40
#define GUNCANG_BATAS 6   // lebih dari ini perubahan = kontak goyang

// --- LCD 20x4 (opsional: kalau tidak tersambung, jalan tanpa LCD) ---
#define SDA_PIN 21
#define SCL_PIN 22
#define LCD_COLS 20
#define LCD_ROWS 4
hd44780_I2Cexp lcd;
bool lcdOk = false;

// --- Riwayat ---
struct Transisi {
    unsigned long t;      // millis sejak boot
    uint8_t       pin;    // PIN_A / PIN_B
    bool          level;  // true = terendam
};
Transisi riwayat[MAX_LOG];
int      jmlRiwayat = 0;

bool          pernahTerendam[2]  = {false, false}; // [0]=GPIO27 [1]=GPIO14
unsigned long waktuPertama[2]    = {0, 0};         // saat pertama kali HIGH
int           jmlBerubah[2]      = {0, 0};         // statistik goyang
bool          ujiSelesai         = false;

bool          stb[2]             = {false, false}; // status stabil
unsigned long tBounce[2]         = {0, 0};
int           rawSebelum[2]      = {LOW, LOW};

unsigned long tCetakStatus       = 0;

// ------------------------------------------------------------
inline int idx(uint8_t pin) { return (pin == PIN_A) ? 0 : 1; }

// LCD: satu baris per pemanggilan (sengaja TANPA argumen default
// agar tidak bentrok dengan auto-prototype Arduino IDE)
void lcdBaris(int y, const char* teks) {
    if (!lcdOk || !teks) return;
    lcd.setCursor(0, y);
    lcd.print(teks);
}

void lcdLangkah() {
    if (!lcdOk) return;
    lcd.clear();
    lcdBaris(0, "UJI WATER LEVEL");
    lcdBaris(1, "kirim 'r' utk mulai");
    lcdBaris(2, "lalu naikkan air");
    lcdBaris(3, "115200 baud");
}

void lcdStatus() {
    if (!lcdOk) return;
    char b[21];
    lcd.clear();
    lcdBaris(0, "UJI WATER LEVEL");

    snprintf(b, sizeof(b), "GPIO%-2d : %s", PIN_A, stb[0] ? "TERENDAM" : "kering  ");
    lcdBaris(1, b);
    snprintf(b, sizeof(b), "GPIO%-2d : %s", PIN_B, stb[1] ? "TERENDAM" : "kering  ");
    lcdBaris(2, b);

    if (ujiSelesai)                     lcdBaris(3, "Selesai - kirim 't'");
    else if (!stb[0] && stb[1])         lcdBaris(3, "KOMBINASI MUSTAHIL");
    else if (!stb[1] && stb[0])         lcdBaris(3, "tunggu pin satunya..");
    else                                lcdBaris(3, "r=reset  t=hasil");
}

void instruksi() {
    Serial.println();
    Serial.println("==============================================");
    Serial.println(" UJI IDENTIFIKASI FLOAT SWITCH");
    Serial.println("==============================================");
    Serial.println(" CARA A (pakai air):");
    Serial.println("   1. kirim 'r' saat air di bawah kedua switch");
    Serial.println("   2. naikkan air PELAN-PELAN dari bawah");
    Serial.println("   3. pin yang HIGH PERTAMA = switch BAWAH");
    Serial.println();
    Serial.println(" CARA B (pakai tangan):");
    Serial.println("   1. kirim 'r'");
    Serial.println("   2. geser pelampung yang LEBIH RENDAH dulu");
    Serial.println("      (panjang, dekat kepala drat)");
    Serial.println("   3. baru geser pelampung yang lebih tinggi");
    Serial.println();
    Serial.println(" JANGAN mulai dari pelampung atas -> kesimpulan terbalik");
    Serial.println("----------------------------------------------");
    Serial.println(" Perintah: r=reset  h=riwayat  s=statistik");
    Serial.println("           i=instruksi  t=kesimpulan");
    Serial.println("==============================================");
    Serial.println();
}

void resetUji() {
    jmlRiwayat  = 0;
    ujiSelesai  = false;
    for (int i = 0; i < 2; i++) {
        pernahTerendam[i] = false;
        waktuPertama[i]   = 0;
        jmlBerubah[i]     = 0;
        tBounce[i]        = 0;
        rawSebelum[i]     = digitalRead(i == 0 ? PIN_A : PIN_B);
        stb[i]            = (rawSebelum[i] == WET_LEVEL);
    }
    Serial.printf("\n[r] Uji direset. Mulai dari posisi KOSONG/kering.\n");
    Serial.printf("    Status sekarang: GPIO%d=%s GPIO%d=%s\n",
                  PIN_A, stb[0] ? "terendam" : "kering",
                  PIN_B, stb[1] ? "terendam" : "kering");

    // Kalau masih ada yang terendam saat mulai, urutan perubahan nanti
    // tidak akan dimulai dari posisi terendah -> kesimpulan bisa terbalik.
    bool adaTerendam = stb[0] || stb[1];
    if (adaTerendam) {
        Serial.println("    *** PERINGATAN: masih ada switch terendam! ***");
        Serial.println("    Turunkan air / turunkan kedua pelampung dulu,");
        Serial.println("    baru kirim 'r' lagi supaya kesimpulan akurat.");
    }
    Serial.println();

    if (lcdOk) {
        lcd.clear();
        lcdBaris(0, adaTerendam ? "AWAS: masih ada"  : "Uji direset");
        lcdBaris(1, adaTerendam ? "switch terendam!" : "mulai dari KOSONG");
        lcdBaris(2, adaTerendam ? "turunkan air dulu" : "lalu naikkan air");
        lcdBaris(3, adaTerendam ? "kirim 'r' ulang"   : "atau geser pelmpg");
    }
}

void tampilkanKesimpulan();

void catat(uint8_t pin, bool level) {
    int i = idx(pin);
    jmlBerubah[i]++;

    if (level && !pernahTerendam[i]) {   // baru saja jadi terendam
        pernahTerendam[i] = true;
        waktuPertama[i]   = millis();
    }

    if (jmlRiwayat < MAX_LOG) {
        riwayat[jmlRiwayat].t     = millis();
        riwayat[jmlRiwayat].pin   = pin;
        riwayat[jmlRiwayat].level = level;
        jmlRiwayat++;
    }

    Serial.printf("[%6lu.%01lus] GPIO%d -> %s   (perubahan ke-%d)\n",
                  (unsigned long)(millis() / 1000),
                  (unsigned long)((millis() % 1000) / 100),
                  pin, level ? "TERENDAM" : "kering  ", jmlBerubah[i]);

    if (!ujiSelesai && pernahTerendam[0] && pernahTerendam[1]) {
        ujiSelesai = true;
        tampilkanKesimpulan();
    }
}

// ------------------------------------------------------------
void tampilkanKesimpulan() {
    bool a = pernahTerendam[0], b = pernahTerendam[1];

    Serial.println();
    Serial.println("==================================================");
    Serial.println(" KESIMPULAN UJI URUTAN");
    Serial.println("==================================================");

    if (!a && !b) {
        Serial.println(" Belum ada pin yang terendam - uji belum jalan.");
        Serial.println(" Jalankan CARA A atau CARA B di header sketch ini.");
        Serial.println("==================================================");
        return;
    }
    if (!a || !b) {
        uint8_t ada  = a ? PIN_A : PIN_B;
        uint8_t blm  = a ? PIN_B : PIN_A;
        Serial.printf(" Baru GPIO%d yang pernah terendam; GPIO%d belum.\n", ada, blm);
        Serial.println(" Naikkan air / geser pelampung satunya, lalu kirim 't'.");
        Serial.println("==================================================");
        return;
    }

    unsigned long ta = waktuPertama[0], tb = waktuPertama[1];

    Serial.printf(" 1) GPIO%d terendam PERTAMA pada %lu.%lus\n",
                  PIN_A, (unsigned long)(ta / 1000), (unsigned long)((ta % 1000) / 100));
    Serial.printf(" 2) GPIO%d terendam KEDUA   pada %lu.%lus\n",
                  PIN_B, (unsigned long)(tb / 1000), (unsigned long)((tb % 1000) / 100));
    Serial.println();
    Serial.println(" Pin yang menyala PERTAMA = posisi paling RENDAH");
    Serial.println(" -> itu switch BAWAH (level minimum)");
    Serial.println();

    bool       aBawah  = (ta <= tb);
    uint8_t    pinBawah = aBawah ? PIN_A : PIN_B;
    uint8_t    pinAtas  = aBawah ? PIN_B : PIN_A;

    Serial.printf(" >>> GPIO%d = switch BAWAH (level minimum)\n", pinBawah);
    Serial.printf(" >>> GPIO%d = switch ATAS  (level penuh)\n",   pinAtas);
    Serial.println();

    Serial.println(" Cocok dengan firmware utama:");
    Serial.printf("   firmware memakai: GPIO%d = bawah, GPIO%d = atas\n",
                  KODE_BAWAH, KODE_ATAS);
    bool cocok = (pinBawah == KODE_BAWAH && pinAtas == KODE_ATAS);
    if (cocok) {
        Serial.println("   => COCOK - definisi firmware sudah benar");
    } else {
        Serial.println("   => BERTENTANGAN - firmware yang perlu diperbaiki!");
    }
    Serial.println();
    Serial.println(" Catatan: kesimpulan ini berlaku bila air dinaikkan");
    Serial.println(" dari bawah, atau pelampung bawah digeser lebih dulu.");
    Serial.println("==================================================");
    Serial.println();

    if (lcdOk) {
        char b[21];
        lcd.clear();
        lcdBaris(0, "KESIMPULAN UJI");
        snprintf(b, sizeof(b), "GPIO%-2d = BAWAH", pinBawah);
        lcdBaris(1, b);
        snprintf(b, sizeof(b), "GPIO%-2d = ATAS",  pinAtas);
        lcdBaris(2, b);
        snprintf(b, sizeof(b), "Firmware: %s", cocok ? "COCOK" : "SALAH!!");
        lcdBaris(3, b);
    }
}

void tampilkanRiwayat() {
    Serial.printf("\n--- Riwayat (%d entri) ---\n", jmlRiwayat);
    if (jmlRiwayat == 0) {
        Serial.println(" (kosong - kirim 'r' lalu naikkan air)\n");
        return;
    }
    for (int i = 0; i < jmlRiwayat; i++) {
        Serial.printf("  %6lu.%01lus  GPIO%d -> %s\n",
                      (unsigned long)(riwayat[i].t / 1000),
                      (unsigned long)((riwayat[i].t % 1000) / 100),
                      riwayat[i].pin, riwayat[i].level ? "TERENDAM" : "kering  ");
    }
    Serial.println();
}

void tampilkanStatistik() {
    Serial.println("\n--- Statistik (sejak reset) ---");
    Serial.printf("  GPIO%-2d: %2d perubahan%s\n", PIN_A, jmlBerubah[0],
                  jmlBerubah[0] > GUNCANG_BATAS ? "   <== BANYAK, kontak goyang!" : "");
    Serial.printf("  GPIO%-2d: %2d perubahan%s\n", PIN_B, jmlBerubah[1],
                  jmlBerubah[1] > GUNCANG_BATAS ? "   <== BANYAK, kontak goyang!" : "");
    Serial.printf("  pernah terendam : GPIO%d=%s  GPIO%d=%s\n",
                  PIN_A, pernahTerendam[0] ? "YA" : "tidak",
                  PIN_B, pernahTerendam[1] ? "YA" : "tidak");
    Serial.printf("  kondisi sekarang: GPIO%d=%s  GPIO%d=%s%s\n",
                  PIN_A, stb[0] ? "terendam" : "kering ",
                  PIN_B, stb[1] ? "terendam" : "kering ",
                  (!stb[0] && stb[1]) ? "  <== MUSTAHIL (pemicu ERROR)" : "");
    Serial.println();
}

// ------------------------------------------------------------
void setup() {
    Serial.begin(115200);
    delay(300);

    pinMode(PIN_A, INPUT_PULLUP);
    pinMode(PIN_B, INPUT_PULLUP);

    // LCD opsional - kalau tidak ada, tetap jalan lewat Serial
    Wire.begin(SDA_PIN, SCL_PIN);
    if (lcd.begin(LCD_COLS, LCD_ROWS) == 0) {
        lcdOk = true;
        lcd.noAutoscroll();
    }

    Serial.println();
    Serial.println("=== UJI IDENTIFIKASI FLOAT SWITCH ===");
    Serial.printf("GPIO%d dan GPIO%d - tentukan mana bawah, mana atas\n", PIN_A, PIN_B);
    Serial.println(lcdOk ? "LCD terdeteksi - hasil juga tampil di LCD"
                         : "LCD tidak terdeteksi - pakai Serial saja");

    instruksi();

    rawSebelum[0] = digitalRead(PIN_A);
    rawSebelum[1] = digitalRead(PIN_B);
    stb[0] = (rawSebelum[0] == WET_LEVEL);
    stb[1] = (rawSebelum[1] == WET_LEVEL);

    lcdLangkah();
    lcdStatus();

    Serial.printf("Status awal: GPIO%d=%s GPIO%d=%s\n\n",
                  PIN_A, stb[0] ? "terendam" : "kering",
                  PIN_B, stb[1] ? "terendam" : "kering");
}

void loop() {
    unsigned long now = millis();

    // --- baca + debounce, catat tiap transisi yang stabil ---
    for (int i = 0; i < 2; i++) {
        uint8_t pin = (i == 0) ? PIN_A : PIN_B;
        int raw = digitalRead(pin);

        if (raw != rawSebelum[i]) {   // pin fisik baru berubah -> reset timer
            rawSebelum[i] = raw;
            tBounce[i]    = now;
        }
        if (now - tBounce[i] >= DEBOUNCE_MS) {
            bool terendam = (raw == WET_LEVEL);
            if (terendam != stb[i]) {
                stb[i] = terendam;
                catat(pin, terendam);
                lcdStatus();
            }
        }
    }

    // --- status LCD berkala selama uji belum selesai ---
    if (lcdOk && !ujiSelesai && now - tCetakStatus >= 2000) {
        tCetakStatus = now;
        lcdStatus();
    }

    // --- perintah serial ---
    if (Serial.available()) {
        char c = Serial.read();
        if (c == 'r' || c == 'R')      { resetUji(); lcdStatus(); }
        else if (c == 'h' || c == 'H') { tampilkanRiwayat(); }
        else if (c == 's' || c == 'S') { tampilkanStatistik(); }
        else if (c == 'i' || c == 'I') { instruksi(); }
        else if (c == 't' || c == 'T') { tampilkanKesimpulan(); }
        else if (c != '\n' && c != '\r') {
            Serial.println("Perintah: r=reset  h=riwayat  s=statistik  i=instruksi  t=kesimpulan");
        }
    }
}
