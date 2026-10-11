# Rencana KKN — Sistem Hidroponik IoT (HarvestHouse)
## Versi Rincian: PRA-KKN → Minggu 5

Label: **[ASUMSI]** = perlu konfirmasi Rizki/ketua tim · **[BELUM]** = belum ada desain final.

---

# BAGIAN A — PRA-KKN (sebelum berangkat)

## A1. Inventaris & Cek Out Alat

Cek fisik satu per satu. Kolom "Kurang" = beli SEBELUM berangkat (minggu terakhir PRA).

| # | Alat | Spesifikasi acuan | Fungsi | Jml | Sampai [ ] | Kurang [ ] |
|---|---|---|---|---|---|---|
| 1 | ESP32 DOIT 38-pin | devkit, kabel USB data | Otak sistem | 2 (1 cadangan) | [ ] | [ ] |
| 2 | Sensor pH DFRobot SEN0161-V2 | Gravity, analog mV | Ukur pH nutrisi | 1 | [ ] | [ ] |
| 3 | Sensor EC/TDS | analog, pin 34 | Ukur ppm nutrisi | 1 | [ ] | [ ] |
| 4 | DS18B20 (suhu air) | waterproof, OneWire | Suhu air nutrisi | 2 (1 cadangan) | [ ] | [ ] |
| 5 | DHT22 (suhu & lembap ruang) | digital pin 4 | Monitoring ruang | 1 | [ ] | [ ] |
| 6 | LCD I2C 20x4 | I2C 0x27 | Display lokal | 1 | [ ] | [ ] |
| 7 | RTC DS3231 | I2C kedua (16/17) | Waktu offline | 1 | [ ] | [ ] |
| 8 | Float switch | pelampung atas + bawah | Level tandon | 2 | [ ] | [ ] |
| 9 | Relay/SSR | 1–4 channel, aktif HIGH | Solenoid + pompa | 1 modul | [ ] | [ ] |
| 10 | Solenoid valve | 12V, sesuai pipa | Isi air otomatis | 1 | [ ] | [ ] |
| 11 | Pompa sirkulasi air | submersible 12V/AC | Sirkulasi nutrisi | 1 | [ ] | [ ] |
| 12 | Pompa dosing abimix A + B | DC kecil / peristaltik | Dosing nutrisi A & B | 2 | [ ] | [ ] |
| 13 | Buzzer aktif | pin 26 | Alarm | 1 | [ ] | [ ] |
| 14 | Bahan venturi | pipa 25→15→25 mm + lem | Injeksi/aerasi **[ASUMSI]** | 1 set | [ ] | [ ] |
| 15 | Pendingin air | coil/chiller/es siram **[ASUMSI]** | Turunkan suhu air | 1 | [ ] | [ ] |
| 16 | Kipas / exhaust ruang | 12V/AC | Pendingin ruangan | 1 | [ ] | [ ] |
| 17 | Power supply 12V + 5V (buck) | ≥3A | Daya pompa + board | 1 set | [ ] | [ ] |
| 18 | PCB perfboard + terminal + kabel | 1 set | Perakitan | 1 set | [ ] | [ ] |
| 19 | Box + dudukan | tahan cipratan | Rumah elektronik | 1 | [ ] | [ ] |
| 20 | Solder, timah, multimeter | 1 set | Perakitan & uji | 1 set | [ ] | [ ] |
| 21 | Pipa, selang, seal tape, klem | secukupnya | Instalasi air | - | [ ] | [ ] |
| 22 | Larutan kalibrasi | pH 4.00, 6.86, 1413 µS | Kalibrasi sensor | 3 botol | [ ] | [ ] |
| 23 | Gelas ukur / timbangan | 100–1000 ml | Kalibrasi pompa & abimix | 1 | [ ] | [ ] |

## A2. Diagram Wiring PCB (data pin dari kode firmware)

Pinout resmi (dari `esp32_hidroponik_terbaru` & `esp32_test_komponen`) — jangan diubah saat menggambar:

| GPIO | Fungsi | Tipe sinyal | Catatan kabel ke PCB |
|---|---|---|---|
| 33 | pH SEN0161-V2 | Analog (ADC1) | Kabel shielded, jangan sejajar kabel AC |
| 34 | TDS/EC | Analog (ADC1) | Sama — bebas gangguan |
| 18 | DS18B20 (OneWire) | Digital + pullup | Resistor 4.7k data–VCC |
| 4 | DHT22 | Digital | Resistor 10k pullup |
| 25 | SSR solenoid valve | OUTPUT, aktif HIGH | Relay CH1 |
| 26 | Buzzer aktif | OUTPUT | |
| 27 + 14 | Float switch atas & bawah | INPUT_PULLUP | ⚠️ lihat catatan di bawah |
| 21 / 22 | SDA1 / SCL1 — LCD 20x4 | I2C | |
| 16 / 17 | SDA2 / SCL2 — RTC DS3231 | I2C | Jangan tertukar dengan LCD |
| **32 + 13** | **Pompa abimix A & B** | OUTPUT → relay | **[ASUMSI] belum ada di firmware** — pin bebas usulan, konfirmasi dulu |
| **15** | **Pompa sirkulasi** | OUTPUT → relay | **[ASUMSI]** — atau langsung listrik tanpa kontrol |

⚠️ **Temuan yang harus dibereskan sebelum gambar wiring final:** definisi float switch tidak konsisten antar sketch.
`full_rest_api`: LOW=27, HIGH=14 · `terbaru` & `test_float_switch`: LOW=14, HIGH=27. **Samakan dulu di kode, baru gambar kabelnya.**

Langkah membuat dokumen:
1. Buka Figma / draw.io / kertas grid — gambar kotak ESP32 + komponen mengelilingi.
2. Tarik garis sesuai tabel di atas, tulis nomor GPIO di setiap garis.
3. Tambahkan blok power: 12V (pompa, solenoid, kipas) vs 5V (ESP32, sensor) — pisahkan ground.
4. Judul + versi + tanggal, ekspor PDF → print 2 lembar untuk dibawa.

## A3. Layout 2D Dalam Box

1. Ukur box (P×L×T), buat skala kertas grid 1 kotak = 1 cm.
2. Tempatkan dari yang panas/berat: power supply di pojok (ventilasi), ESP32 + PCB di tengah, kabel gland di sisi bawah-samping.
3. Sisakan jalur kabel ±3 cm antar blok; kabel sensor jangan berimpit kabel power.
4. Lubang yang wajib digambar: kabel gland (sensor + power), ventilasi 2 sisi berhadapan, jalur pipa/pendingin **[ASUMSI]**.
5. Tandai ukuran tiap komponen. Gambar wajib memuat: dimensi box, posisi semua blok, jalur kabel, lubang.
6. Ekspor → print 1 lembar sebagai template bor/dudukan.

## A4. Rancangan Sistem (diagram blok)

Gambar 1 halaman berisi alur ini:

```
[Tandon nutrisi A/B] --pompa abimix--> [Pipa sirkulasi + venturi] --> [Tangki/parit tanaman]
        ^                                        |
   solenoid (isi air)                     [SENSOR: pH 33, EC 34, suhu air 18, suhu ruang 4]
                                                  |
                                            [ESP32 + PCB + LCD 20x4]
                                                  |  REST + header X-API-KEY
                                                  v
                            [API https://harvesthouse.biz.id/api/*]  (server harvesth, DB harvesth_)
                                                  |
                                    [Website + Aplikasi (Eka)] + [AI server (minggu 5)]
```

Aturan teknis yang sudah dipakai (JANGAN diubah):
- Firmware **REST-only** ke `https://harvesthouse.biz.id/api/*` + header `X-API-KEY` (default `HARVEST123`). MQTT/HiveMQ dihapus permanen — jangan dikembalikan. Notifikasi WA/Fonnte juga sudah dihapus total.
- Kalibrasi pH via Serial (ENTERPH/CALPH/EXITPH), nilai di EEPROM (`EEPROM.begin(64)` sebelum `ph.begin()`).

## A5. Materi Belajar Hidroponik (checklist penguasaan)

### A5.1 Sistem Pengaliran Air
- [ ] Perbandingan NFT vs drip vs DWC — ikuti tipe instalasi di lokasi KKN **[ASUMSI]**.
- [ ] Hitungan kasar: volume parit = P × L × tinggi air; waktu sirkulasi = volume ÷ debit pompa (pakai hasil kalibrasi B3).
- [ ] Kemiringan parit ideal ± 1–3% **[ASUMSI — sesuaikan lapangan]**.
- [ ] Jadwal sirkulasi (mis. 15 menit nyala : 15 menit mati) + risiko akar kering / kelebihan air.

### A5.2 Pendingin Air
- [ ] Target suhu air ± 18–22 °C; di atas 26 °C risiko busuk akar (Pythium) **[ASUMSI angka]** — validasi di riset A9.
- [ ] Metode: coil + chiller/es, pergantian air, bayangan atap — pilih sesuai alat A1 #15.
- [ ] Beban es kasar: kg es/jam ≈ (debit air × ΔT) ÷ 80.

### A5.3 Pendingin Ruangan
- [ ] Exhaust + kipas: efek ke suhu & kelembapan (monitor DHT22).
- [ ] Naungan/shading & dampak suhu ruang ke suhu air.

### A5.4 Nutrisi & Penyakit
- [ ] Dosis ab mix per fase tanaman (target ppm/EC) → dasar kalibrasi abimix.
- [ ] Pahami daftar penyakit A9 (gejala + penanganan awal).

## A6. Website & Aplikasi — PJ: **Eka** (prioritas)

### A6.1 Konfigurasi Server (kondisi nyata — ikuti persis)
1. Panel hosting = BEKANTANJANTAN (server.bekantanjantan.co.id, user `harvesth`) — **tidak ada terminal**.
2. Jalankan artisan via **Cron Jobs sekali-jalan**:
   `php /home/harvesth/domains/harvesthouse.biz.id/hydroponik-deploy/artisan <perintah> --no-interaction >> <log di folder domain> 2>&1`
   → cek log via File Manager → hapus cron.
3. DB MySQL `harvesth_hydroponik`; semua nama DB/username diawali `harvesth_`. Skema via `migrate --seed` (JANGAN import dump SQLite). PHP 8.3.
4. Web root = `public_html` (Cara B: salin isi `public/` + ubah 3 path `index.php` ke `__DIR__.'/../hydroponik-deploy/...'`).
5. Paket deploy: `~/Documents/hydroponik-deploy(.zip)` (rsync dari repo, exclude vendor/node_modules/.git/tests, `composer install --no-dev` di staging).
6. Verifikasi akhir: login web jalan, 1 data sensor dari ESP32 masuk ke API, grafik tampil.

### A6.2 Daftar Halaman (mockup dulu, kerjakan berurutan)

| # | Halaman | Isi |
|---|---|---|
| 1 | Login | dev: admin/@adminbersabda123 · demo: demo/demo123 |
| 2 | Dashboard | Angka besar pH / EC / suhu air / suhu ruang + status pompa & level air |
| 3 | Grafik riwayat | Filter tanggal, grafik 4 parameter |
| 4 | Kontrol | Tombol pompa sirkulasi, dosing A/B, solenoid + jadwal otomatis |
| 5 | Log kalibrasi | Tabel hasil kalibrasi minggu 1–2 (input manual) |
| 6 | Cek penyakit | Unggah foto → hasil AI (minggu 5) |

### A6.3 Kontrak API (Eka + Rizki sepakati sebelum minggu 2 berakhir)
- `POST /api/sensor` — kirim pH, EC, suhu air, suhu ruang, level air (header X-API-KEY).
- `GET /api/sensor/latest` · `GET /api/sensor/history?from=&to=`
- `POST /api/actuator` — aksi pompa/dosing/solenoid.
- `POST /api/ai/analyze` — foto → prediksi penyakit (minggu 5).

## A7. Mockup Tampilan Display (cukup gambar, belum perlu jadi)

**LCD 20x4 (4 baris × 20 karakter):**
```
Baris 1: pH 6.10  EC 1150ppm
Baris 2: Air 24.3C  Ruang 29.1C
Baris 3: Sirk:ON  A:OK B:OK
Baris 4: Level:CUKUP   14:32
```
**Mockup dashboard web/app:** 4 kartu angka besar di atas, grafik di bawah, 3 tombol kontrol (sirkulasi / dosing / isi air) di panel kanan.
Deliverable PRA: gambar mockup (Figma / HTML statis / sketsa rapi) — bukan kode final.

## A8. Konten Instagram — PJ: tim perempuan

**Pilar konten:** (1) Edukasi hidroponik · (2) Behind the scene riset & kunjungan · (3) Progres alat harian.

| Fase | Konten wajib |
|---|---|
| PRA-KKN | Unboxing/cek alat, "kenalan sama alat", teaser riset penyakit |
| Minggu 1 | Kunjungan & riset lokasi (foto kerja tim), tips hidroponik 1 slide |
| Minggu 2–3 | Progres sirkulasi & venturi, demo data ke web (story) |
| Minggu 4–5 | Uji lapangan, hasil akhir, kompilasi video |

Aturan harian: minimal **1 foto/video + 1 story tiap hari** (sinkron dengan dokumentasi B1).
Shot list "riset & kunjungan": (a) tim memegang alat, (b) proses ukur/bongkar, (c) lokasi luas, (d) close-up tanaman/sakit, (e) candid diskusi.

## A9. Riset Penyakit & Pertanian → bahan AI server

Template per penyakit (isi kolom + kumpulkan foto + simpan link sumber):

| Penyakit | Gejala visual | Penyebab | Faktor pemicu | Penanganan | Foto [ ] |
|---|---|---|---|---|---|
| Busuk akar (Pythium) | Akar cokelat/lembek, bau | Jamur | Suhu air >25 °C | Turunkan suhu, fungisida | [ ] |
| Layu Fusarium | Layu satu sisi, xylem cokelat | Jamur | Lembap tinggi | Cabut, sanitasi | [ ] |
| Klorosis (daun kuning) | Daun menguning | Kurang N/Fe, pH salah | pH/EC meleset | Koreksi nutrisi | [ ] |
| Bercak daun | Bercak cokelat/merah | Jamur/bakteri | Lembap | Buang daun, sirkulasi | [ ] |
| Kutu daun / trip | Bintik, daun keriting | Hama | Ruang tertutup | Semprot organik | [ ] |
| Lumut/alga | Hijau di parit & akar | Cahaya + nutrisi | Cahaya kena pipa | Tutup parit | [ ] |

Sumber: jurnal/BBPP/penyuluhan pertanian — simpan link tiap baris (dipakai sitasi laporan).

---

# BAGIAN B — KKN MINGGU 1: Riset Lokasi, Dokumentasi Harian, Kalibrasi

## B1. Jadwal Hari-per-Hari

| Hari | Pagi | Siang | Sore | Posting IG |
|---|---|---|---|---|
| 1 | Kunjungan & riset lokasi: ukur area, tandon, sumber air, stopkontak, cahaya | Foto + sketsa ukuran | Rapat hasil ukur | Konten kunjungan |
| 2 | Rencana pembersihan area + alat kebersihan | Eksekusi pembersihan | Foto before/after | Tips + progres |
| 3 | Pembersihan lanjutan + penataan tandon/parit | Uji pasang kasar pompa & pipa | Cek kebocoran | Behind the scene |
| 4 | **Kalibrasi pompa** (SOP B3) | Catat hasil, ulangi | Belajar pengaliran (A5.1) | Edukasi |
| 5 | **Kalibrasi pH** (SOP B4) | **Kalibrasi EC** (SOP B5) | Isi tabel | Progres kalibrasi |
| 6 | **Kalibrasi suhu** (SOP B6) | Uji baca sensor bersamaan | Koreksi nilai | Edukasi suhu air |
| 7 | Rangkum hasil kalibrasi → dokumen | Backup foto/video minggu ini | Rencana minggu 2 | Kompilasi story |

Aturan dokumentasi (tanpa bolong):
1. Minimal 1 foto/video kerja nyata per hari (bukan stok lama).
2. Simpan sebagai `tgl-kkn-aktivitas.jpg` di `~/Documents/hydroponik/docs/dokumentasi/`.
3. 1 posting IG (feed/story) per hari — isi dari A8.

## B2. Riset & Pengukuran Lokasi — checklist ukur
- [ ] Dimensi area instalasi (P×L) + foto tiap sudut.
- [ ] Jarak sumber air ke instalasi, jenis keran, tekanan (kuat/sedang/lemah).
- [ ] Jumlah & posisi stopkontak (pompa + pendingin).
- [ ] Suhu & kelembapan ruang jam 08.00 / 12.00 / 16.00 (DHT22).
- [ ] Kondisi cahaya (kena matahari pagi langsung?) — kaitan lumut A9.
- [ ] Rencana bersih: alat, target selesai, foto before/after.

## B3. SOP Kalibrasi Pompa (target: L/menit)
1. Siapkan gelas ukur 1 L + timer.
2. Celupkan pompa di wadah penuh air, selang keluar ke gelas ukur.
3. Nyalakan; saat volume menyentuh tanda → catat waktu. Ulangi **3 kali**.
4. Debit = volume ÷ menit tiap percobaan; pakai **rata-rata**.

| Percobaan | Lama nyala | Volume | Debit (L/menit) |
|---|---|---|---|
| 1 | ... | ... | ... |
| 2 | ... | ... | ... |
| 3 | ... | ... | ... |
| **Rata-rata** | | | **...** |

Dipakai untuk: hitung waktu sirkulasi (A5.1) + dasar uji venturi (C2).

## B4. SOP Kalibrasi pH (SEN0161-V2)
> Aturan firmware: voltage mV = ADC×3300/4095; `EEPROM.begin(64)` sebelum `ph.begin()`. Pakai sketch `esp32_test_komponen/kalibrasi_ph`.

1. Bilas probe air suling → celup larutan pH 6.86, tunggu ±1 menit stabil.
2. Serial Monitor (115200) → ketik `ENTERPH` → tunggu → ketik `CALPH`.
3. Bilas → celup larutan pH 4.00 → tunggu stabil → `CALPH` → `EXITPH` (tersimpan ke EEPROM).
4. Uji ulang kedua larutan + sampel nutrisi:

| Larutan | Nilai baku | Pembacaan | Selisih |
|---|---|---|---|
| 6.86 | 6.86 | ... | ... |
| 4.00 | 4.00 | ... | ... |
| Sampel nutrisi | (alat manual) | ... | ... |

Batas lolos: selisih ≤ 0,2 pH.

## B5. SOP Kalibrasi EC/TDS
1. Pakai sketch `esp32_test_komponen/kalibrasi_tds`.
2. Celup larutan baku 1413 µS (±800–900 ppm TDS), tunggu stabil.
3. Sesuaikan faktor kalibrasi di sketch hingga bacaan ≈ 1413 µS → catat faktornya.
4. Uji larutan nutrisi, bandingkan dengan alat TDS portable bila ada.

| Larutan | Nilai baku | Pembacaan | Faktor koreksi |
|---|---|---|---|
| 1413 µS | 1413 | ... | ... |
| Nutrisi target | ... | ... | ... |

Batas lolos: selisih ≤ 5%.

## B6. SOP Kalibrasi Suhu (DS18B20 + DHT22)
1. Bandingkan DS18B20 vs termometer manual di 3 kondisi: air suhu ruang, air dingin (es), air hangat.
2. DHT22 vs termometer ruang jam 08.00 & 14.00.
3. Catat offset tetap bila selisih konsisten (dikompensasi di kode).

| Sensor | Kondisi | Baku | Pembacaan | Offset |
|---|---|---|---|---|
| DS18B20 | air ruang | ... | ... | ... |
| DS18B20 | air dingin | ... | ... | ... |
| DHT22 | ruang pagi | ... | ... | ... |

Batas lolos: DS18B20 ≤ 0,5 °C · DHT22 ≤ 1 °C.

---

# BAGIAN C — KKN MINGGU 2: Sirkulasi, Venturi, Abimix, Mulai IoT

## C1. Pemasangan Sirkulasi (langkah)
1. Susun pipa sesuai A4: tandon → pompa → filter → parit → balik tandon.
2. Rakit sambungan + seal tape, pasang klem.
3. Pompa terendam; kabel naik ke atas (sambungan jangan terendam).
4. Uji kebocoran 30 menit tanpa listrik (isi penuh).
5. Nyalakan pompa → catat debit aktual di instalasi (bandingkan B3).
6. Tentukan jadwal sirkulasi (mis. 15:15 menit) → untuk diatur di web/app.

## C2. Pembuatan Venturi (langkah)
Bahan: pipa induk 25 mm, leher 15 mm (panjang ± 4–6 cm), sambungan hisap 5–6 mm, lem pipa. **[ASUMSI ukuran]**
1. Potong pipa induk, buat penyempitan (leher) di tengah.
2. Bor lubang di leher, pasang pipa hisap ke wadah nutrisi/udara.
3. Lem rapat, kering 24 jam.
4. Uji dengan debit pompa hasil B3: rendam selang hisap di gelas ukur, hitung ml/menit terhisap.

| Debit aliran | Lama uji | Volume terhisap | Status |
|---|---|---|---|
| ... L/menit | 1 menit | ... ml | berhasil/kurang |

Fungsi venturi **[ASUMSI — konfirmasi: injeksi nutrisi (abimix) atau aerasi]**.

## C3. SOP Kalibrasi Abimix (pompa dosing A & B) — target ml/menit
1. Selang hisap di wadah air bersih (bukan nutrisi asli).
2. Nyalakan pompa A **tepat 1 menit**, tampung di gelas ukur ml → catat.
3. Ulangi 3 kali → rata-rata → ulangi untuk pompa B.
4. Dosis: ml nutrisi dibutuhkan ÷ debit = detik pompa nyala.

| Pompa | Ulangi | 1 menit → volume |
|---|---|---|
| A | 1 / 2 / 3 | ... / ... / ... ml → rata-rata **... ml/menit** |
| B | 1 / 2 / 3 | ... / ... / ... ml → rata-rata **... ml/menit** |

Batas lolos: variasi antar ulangan ≤ 10% (lebih → ganti selang/peristaltik).

## C4. Mulai Integrasi IoT (lanjut minggu 3)
1. Kontrak API dengan Eka (A6.3) — **deadline akhir minggu 2**.
2. Modifikasi `esp32_hidroponik_terbaru`: tambah pin & logika pompa abimix (A2) + kirim data ke API.
3. Uji kirim data lapangan → `https://harvesthouse.biz.id/api/sensor` (cek X-API-KEY).

---

# BAGIAN D — KKN MINGGU 3: Integrasi IoT → API (TARGET SELESAI)

Checklist demo end-to-end:
- [ ] 4 sensor (pH, EC, suhu air, suhu ruang) + level air terkirim berkala ke API.
- [ ] Data real-time tampil di dashboard web & app (Eka).
- [ ] Grafik riwayat terisi data lapangan.
- [ ] Tombol kontrol (sirkulasi / dosing A/B / solenoid) diterima ESP32 → aktuator bergerak.
- [ ] Buzzer berbunyi bila level air kritis (float switch) — uji manual.
- [ ] LCD 20x4 menampilkan data sama dengan web (opsional).
- [ ] Koneksi pulih otomatis setelah Wi-Fi putus (uji: matikan AP 2 menit).

Uji lanjutan: kirim data **24 jam nonstop**, cek grafik tidak bolong.

---

# BAGIAN E — KKN MINGGU 4: Pemasangan Permanen & Pengujian

> Rencana asli terpotong ("pengujian apakah ada yang…") — dilanjutkan **[ASUMSI]**: *yang bermasalah / tidak akurat / lepas*.

## E1. Pemasangan Permanen
1. Pasang permanen semua sensor: pH & EC di parit tandon (jangan kena pusaran pompa), DS18B20 terendam penuh, DHT22 di tempat teduh berudara.
2. Rapikan kabel + box (pakai template A3); dudukan tiap sensor supaya tidak lepas.
3. Label nama kabel di kedua ujung.

## E2. Protokol Uji Lapangan 3 Hari
Cek pagi & sore — foto + isi tabel:

| Parameter | Batas normal | Aksi bila meleset |
|---|---|---|
| pH | 5,5–6,5 **[ASUMSI]** | koreksi kalibrasi / dosing |
| EC | sesuai fase tanaman | tambah/kurang abimix |
| Suhu air | 18–26 °C | nyalakan pendingin air |
| Suhu ruang | < 32 °C **[ASUMSI]** | nyalakan kipas |
| Koneksi API | tidak bolong > 10 menit | cek Wi-Fi & log ESP32 |
| Fisik sensor | tidak lepas/berlumut | pasang ulang |

Hari 3 — uji tekan: matikan listrik 10 menit (cek pemulihan), isi ulang air (cek solenoid + float switch).

## E3. Troubleshooting Cepat

| Gejala | Kemungkinan | Perbaikan |
|---|---|---|
| pH melonjak acak | Probe kering/berlumut, kabel tak shielded | Rendam ulang, bersih, reroute kabel |
| EC turun terus | Dosing kurang | Cek pompa abimix (kalibrasi ulang C3) |
| Pompa tidak konsisten | Selang bocor/kemasukan udara | Ganti selang, cek hisapan |
| API bolong | Wi-Fi / jam RTC salah | Cek koneksi, sinkron RTC |
| Buzzer terus | Float switch salah posisi | Lihat catatan pin 27/14 (A2) |

---

# BAGIAN F — KKN MINGGU 5: Finalisasi, AI Server, Laporan **[BELUM]**

## F1. Uji Menyeluruh
1. Jalankan sistem penuh **3 hari nonstop** — semua fungsi Bagian D aktif.
2. Isi tabel rencana vs realisasi 5 minggu (untuk laporan).

## F2. AI Server — MVP **[BELUM — perlu diputuskan]**

**[ASUMSI]** Fungsi: foto daun → prediksi penyakit (database A9) + saran penanganan + saran pH/EC.

Opsi arsitektur — **pilih salah satu sebelum minggu 4**:
- **Opsi A (cepat):** rules-base di Laravel — user centang gejala + unggah foto (lampiran) → sistem cocokkan ke tabel A9. Tanpa ML, muat ±2 hari kerja.
- **Opsi B (berat):** endpoint terpisah + model klasifikasi foto (dataset A9). Butuh layanan eksternal/GPU + ≥ 1 minggu.

Rancangan API: `POST /api/ai/analyze` → input foto/gejala → output `{prediksi, penyebab, penanganan, saran_ph_ec, sumber}`.

Langkah MVP:
1. Susun dataset A9 jadi JSON/DB seeder (idempotent, seperti seeder yang ada).
2. Buat endpoint + halaman "Cek Penyakit" (A6.2 #6).
3. Uji 10 foto nyata lapangan, catat akurasi.

## F3. Laporan Akhir (outline)
1. Pendahuluan & tujuan · 2. Rencana kerja (dokumen ini) · 3. Realisasi minggu 1–5 · 4. Hasil kalibrasi (semua tabel) · 5. Sistem: diagram blok, wiring, foto alat · 6. Web/app & AI server · 7. Konten IG & dokumentasi · 8. Kendala & solusi · 9. Kesimpulan · Lampiran: foto harian, link sumber A9.

---

# LAMPIRAN

## Pembagian Tugas (final — detail lengkap di `tugas-tim-kkn.md`)

| Orang | Tugas |
|---|---|
| Haqi | IoT: firmware ESP32, wiring PCB, panel & elektronik, sensor + aktuator, kalibrasi sensor |
| Rivi | Teknis hidroponik: sirkulasi, venturi, pipa/selang, kalibrasi pompa & abimix, nutrisi & tanaman |
| Rizki | Server (hosting + Raspberry Pi + jaringan), API, AI server |
| Eka + Rizki | Aplikasi & website (Eka prioritas tampilan) |
| Tim perempuan | Nana (sekretaris): postingan IG, arsip & notulen · Nova (bendahara): dana RAB, pembelian, LPJ · Azizah (dokumentasi): foto/video harian |
| Bersama | Riset (nutrisi & penyakit) dibagi Rivi + Nana + Nova + Azizah |

> RAB Arutmin (total Rp 19.456.650) + pemetaan item → PJ ada di `tugas-tim-kkn.md`.
> 📁 Unggah hasil kerja ke Google Drive: https://drive.google.com/drive/folders/1uG9EdUWQgqawiTA3DgQzH4FToJhBHjO8
> ✅ Checklist online di Notion: "Checklist Pekerjaan — KKN Smart Hidroponik" (52 tugas + halaman list per minggu).

## Keputusan yang ditunggu
1. AI server: Opsi A (rules-base) atau Opsi B (model foto)?
2. Venturi: aerasi atau injeksi nutrisi abimix?
3. Konfirmasi pin abimix A/B (usulan 32 & 13) dan pompa sirkulasi (usulan 15 / langsung listrik).
4. Perbaikan konsistensi pin float switch (27/14) di kode sebelum gambar wiring final.
