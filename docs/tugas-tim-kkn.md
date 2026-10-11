# Smart Hidroponik — Rincian Tugas per Orang (berbasis RAB Arutmin)
## Pelengkap `rencana-kkn.md` · Total RAB: Rp 19.456.650

Pengesahan RAB: **Nurul Yakin Qomarudin** (CD Officer) · **Sukri** (Admin & CDEA Superintendent).

📁 **Unggah semua hasil kerja ke Google Drive:** https://drive.google.com/drive/folders/1uG9EdUWQgqawiTA3DgQzH4FToJhBHjO8
(foto/video dokumentasi, diagram, mockup, tabel kalibrasi, laporan, LPJ — semua ke folder ini)

✅ **Checklist online (Notion):** halaman "Checklist Pekerjaan — KKN Smart Hidroponik" — database 52 tugas (klik tiap tugas untuk langkah pengerjaan + kriteria selesai) + halaman list tugas per minggu.

---

## 1. Struktur Tim & Wilayah Kerja

| Orang | Bidang | Cakupan | Item RAB yang jadi tanggung jawabnya |
|---|---|---|---|
| **Haqi** | IoT | Elektronik, firmware ESP32, sensor, aktuator, panel | ESP32 (2), semua sensor, water level, relay 8ch (2), power & proteksi, panel & instalasi, PCB & elektronik, perkabelan, LCD 20x4, pilot lamp, push button pendingin, cooling fan panel, tools solder |
| **Rivi** | Teknis Hidroponik | Air, pipa, pompa, nutrisi, tanaman | Peristaltik pump (4), Taffware aquarium pump (2), pompa celup (2), solenoid valve, selang silikon + PE, pipa & sambungan, atribut perairan, alat kalibrasi cairan (EC, aquades, pH buffer) |
| **Rizki** | Server | Server (hosting + Raspberry Pi), API, AI server | Raspberry Pi set, Access Point, kabel LAN + RJ45 (bersama Haqi pasang) |
| **Eka + Rizki** | Aplikasi & Website | Web, app, display UI | CCTV sensor (data tampil di web) |
| **Tim perempuan** *(dibagi 3 peran, lihat §5)* | Dokumentasi · Postingan · Riset | Konten IG, arsip foto/video, riset | — (tidak pegang barang) |

> Prinsip: barang dicek **2 orang** — penerima bidang + Rizki (koordinator cek out). Yang belum sampai langsung dibeli sebelum berangkat.

---

## 2. Pemetaan Item RAB → Penanggung Jawab Cek Out

### A. Controller & Network — **Rizki** (server) + **Haqi** (perangkat)
| Item | Vol | PJ cek |
|---|---|---|
| ESP32 DevKit V4 WROOM 32 | 2 unit | Haqi |
| Raspberry Pi Set | 1 set | Rizki |
| Access Point | 1 unit | Rizki |
| Kabel LAN 50 m | 50 m | Rizki |
| Konektor RJ45 | 4 unit | Rizki |
| CCTV Sensor | 1 unit | Eka + Rizki |

### B. Sensor — **Haqi**
| Item | Vol | Catatan |
|---|---|---|
| Sensor pH DFRobot | 1 | GPIO 33 |
| Sensor EC DFRobot | 1 | GPIO 34 |
| Sensor Suhu Air DS18B20 | 1 | GPIO 18 |
| Sensor Suhu & Humidity DFRobot | 1 | GPIO 4 |
| Water Level Sensor Aktuator | 2 | GPIO 27/14 — ⚠️ samakan definisi di kode dulu |

### C. Aktuator & Pompa — **Rivi** (uji fungsi hidrolik) + **Haqi** (wiring relay)
| Item | Vol | Catatan |
|---|---|---|
| Peristaltic Pump 12V | 4 | **[ASUMSI]** abimix A, abimix B, pH up, pH down — konfirmasi pembagian |
| Taffware Aquarium Pump 12V 30W | 2 | Pompa sirkulasi (1 utama + 1 cadangan) |
| Pompa Celup Mini (opsional) | 2 | Sirkulasi tambahan / kuras |
| Solenoid Valve 1/2 | 1 | Isi air otomatis (GPIO 25) |

### D. Alat Kalibrasi — **Haqi** (sensor) + **Rivi** (pompa)
EC Calibration set · Aquades 1 L · pH Buffer set.

### E. Power & Proteksi — **Haqi**
PSU 12V 20A · MCB 2P 6A · Fuse (8) · Fuse Holder (4) · Step Down LM2596 (12V→5V).

### F. Panel & Instalasi — **Haqi**
Box Panel · Din Rail (2) · Terminal Blok Din (10) · Busbar Ground · Grounding Rod · Stop Kontak Panel · Kabel Gland (10) · Kabel Duct (5) · Relay 8 Channel 5V (2).

### G. PCB & Elektronik — **Haqi**
PCB Matrix 9x15 (2) · Terminal PCB (20) · Spacer · Resistor · Kapasitor · Kawat Tembaga.

### H. Perkabelan — **Haqi**
Kabel Power 3 Pin · Perkabelan Umum · Heat Shrink · Waterproof Connector (352 pcs — sambungan tahan air dekat sistem air).

### I. Selang & Perpipaan — **Rivi**
Selang Silikon · Selang PE 50 m · Pipa 1" (10) · Pipa 1/2" (6) · Sambungan L 1" (10) · T 1" (6) · L 1/2" (4) · T 1/2" (2) · Male Connector Selang PE (5).

### J. Display & Panel Muka — **Haqi** (pasang) + **Eka & Rizki** (isi data)
LCD 20X4 · Pilot Lamp (4) · Push Button Pendingin (2) · Cooling Fan Panel · Atribut Perairan (7 pcs) **[ASUMSI: aksesoris instalasi perairan — konfirmasi isinya]**.

### K. Tools — **Haqi** (penanggung jawab)
Set Solder · Timah Solder · Helping Hand · Sekrup Baut set.

---

## 3. Rincian Tugas per Orang per Fasa

### 3.1 Haqi — IoT

**PRA-KKN**
1. Cek fisik semua item §2 B–K (sensor, aktuator, elektronik, panel, tools) — centang checklist.
2. Gambar **diagram wiring PCB** — pin resmi: pH 33 · EC 34 · DS18B20 18 · DHT 4 · solenoid 25 · buzzer 26 · level 27+14 · LCD 21/22 · RTC 16/17 · usulan pompa peristaltik A–D: **32, 13, 15, 2** **[ASUMSI — konfirmasi]**.
3. Rakit PCB Matrix + terminal + resistor/kapasitor; solder rapi, uji tiap jalur dengan multimeter.
4. Rakit panel: din rail, MCB, fuse holder, PSU, relay 8ch, LM2596, busbar + grounding rod, stop kontak, kabel duct, cooling fan, pilot lamp, push button pendingin.
5. Buat **layout 2D box panel** (ukur box, posisi tiap blok, jalur kabel gland).
6. Mockup tampilan LCD 20x4 (4 baris: pH/EC · suhu air/ruang · status pompa · level+jam).
7. Uji nyala semua komponen pakai sketch `esp32_test_komponen/` sebelum berangkat.

**Minggu 1**
1. Pasang elektronik pendukung kalibrasi (colok sensor ke ESP32 + laptop).
2. **Kalibrasi pH** (SOP B4: ENTERPH/CALPH/EXITPH), **kalibrasi EC** (SOP B5), **kalibrasi suhu** (SOP B6) — isi tabel hasil.
3. Bantu Rivi kalibrasi pompa (catat waktu pakai timer + hitung).

**Minggu 2**
1. Wiring pompa peristaltik ×4 + Taffware ×2 + solenoid ke relay 8ch (satu kanal satu aktuator, label tiap kabel).
2. Uji ON/OFF tiap aktuator dari ESP32 (pakai `test_solenoid` sebagai acuan).
3. Modifikasi firmware `esp32_hidroponik_terbaru`: tambah logika 4 peristaltik + kirim data ke REST API `https://harvesthouse.biz.id/api/*` (X-API-KEY).

**Minggu 3**
1. Integrasi end-to-end: sensor → ESP32 → API → web/app (uji bersama Eka & Rizki).
2. Pasang CCTV + pastikan stream/URL-nya bisa dipakai web (koordinasi Eka).
3. Uji koneksi via Access Point + LAN (Raspberry Pi sebagai server lokal **[ASUMSI]**).
4. Uji pulih otomatis setelah Wi-Fi putus + alarm buzzer level air.

**Minggu 4**
1. Pasang permanen semua sensor (pH & EC jangan kena pusaran pompa; DS18B20 terendam penuh; DHT teduh).
2. Rapikan panel final: gland, label kabel, penutup box.
3. Uji lapangan 3 hari (E2 `rencana-kkn.md`) — catat drift/putus koneksi.

**Minggu 5**
1. Uji tekan: mati listrik 10 menit, isi ulang air, semua aktuator.
2. Perbaikan firmware + siapkan foto panel/wiring untuk laporan.

### 3.2 Rivi — Teknis Hidroponik

**PRA-KKN**
1. Cek fisik item §2 C (pompa ×8 total) dan §2 I (pipa & selang) + atribut perairan.
2. Belajar sistem pengaliran (NFT/drip/DWC), hitungan debit & waktu sirkulasi, kemiringan parit.
3. Belajar pendingin air (target 18–22 °C) & pendingin ruangan.
4. Riset nutrisi ab mix per fase tanaman + riset penyakit (koordinasi tim perempuan, bahan AI).
5. Buat diagram blok hidrolik: tandon → pompa → filter → parit → balik (gambar 1 halaman).

**Minggu 1**
1. **Riset & pengukuran lokasi** hari 1–2: ukur area, tandon, sumber air, tekanan keran, stopkontak, cahaya.
2. **Pembersihan area** hari 2–3 (foto before/after untuk dokumentasi).
3. **Kalibrasi pompa sirkulasi** (SOP B3: 3× ulangan → L/menit) + **kalibrasi peristaltik** (SOP C3: 1 menit → ml) untuk keempat pompa.

**Minggu 2**
1. **Pemasangan sirkulasi**: pipa 1"/1/2" + sambungan L/T, uji kebocoran 30 menit.
2. **Pembuatan venturi**: pipa 25→15 mm + selang hisap, uji ml/menit terhisap.
3. Pasang selang PE 50 m (interface & monitoring) + selang silikon di jalur dosing.
4. Tentukan jadwal sirkulasi (mis. 15:15 menit) → serahkan angkanya ke Eka/Rizki untuk fitur jadwal.

**Minggu 3**
1. Uji dosing abimix nyata (larutan nutrisi) → validasi EC tercapai target ppm.
2. Tunjukkan parameter nutrisi (target pH/EC per fase) untuk dikunci di aplikasi.

**Minggu 4**
1. Instalasi permanen parit/tandon + atribut perairan.
2. Uji lapangan 3 hari: pH/EC/suhu vs batas normal, atasi penyimpangan hidrolik.
3. Observasi tanaman & gejala penyakit → data nyata untuk AI.

**Minggu 5**
1. Uji menyeluruh 3 hari nonstop (aspek hidrolik & nutrisi).
2. Susun rekomendasi nutrisi & penanganan penyakit → input ke AI server.
3. Foto instalasi air untuk laporan.

### 3.3 Rizki — Server *(+ aplikasi/website bersama Eka)*

**PRA-KKN**
1. **Konfigurasi server hosting** harvesthouse.biz.id: deploy `hydroponik-deploy`, trik Cron artisan (tanpa terminal), web root `public_html` Cara B, DB `harvesth_hydroponik` via `migrate --seed`, verifikasi login + API jalan.
2. **Konfigurasi Raspberry Pi** sebagai server/edge lokal di lokasi KKN **[ASUMSI peran — konfirmasi: fallback server lokal / menjalankan AI / NVR CCTV]**: install OS, akses via SSH over LAN, siapkan layanan.
3. Susun **kontrak API** (`/api/sensor`, `/api/sensor/history`, `/api/actuator`, `/api/ai/analyze`) + X-API-KEY — sepakati dengan Eka & Haqi akhir PRA.
4. Access Point + kabel LAN: skema jaringan lokasi (AP ↔ Raspberry Pi ↔ CCTV ↔ ESP32 Wi-Fi).

**Minggu 1**
1. Siapkan endpoint terima data sensor + tabel log di server produksi.
2. Halaman "Log kalibrasi" (input manual hasil B3–B6).

**Minggu 2**
1. Endpoint `/api/actuator` (kontrol pompa/dosing/solenoid) siap untuk firmware Haqi.
2. Uji terima-kirim data dummy dari lapangan.

**Minggu 3**
1. Tunjuk integrasi end-to-end: validasi data masuk, grafik riwayat, auto-recovery.
2. Monitoring 24 jam nonstop (cek grafik tidak bolong).

**Minggu 4**
1. Stabilkan server selama uji lapangan (log error, backup DB harian).
2. Siapkan dataset penyakit (A9) jadi DB seeder idempotent untuk AI.

**Minggu 5**
1. **AI server MVP** — **Opsi A rules-base Laravel (±2 hari)** atau Opsi B model foto **[pilih sebelum minggu 4]**: `POST /api/ai/analyze` → `{prediksi, penyebab, penanganan, saran_ph_ec, sumber}`.
2. Uji 10 foto nyata + catat akurasi.
3. Susun laporan akhir (Bagian F3 `rencana-kkn.md`).

### 3.4 Eka + Rizki — Aplikasi & Website

*(Prioritas Eka — Rizki back-end/server; pembagian halaman di bawah.)*

| Halaman | PJ utama | Fasa |
|---|---|---|
| Login (dev admin/@adminbersabda123 · demo demo/demo123) | Eka | PRA |
| Dashboard: 4 kartu angka (pH, EC, suhu air, suhu ruang) + status pompa & level | Eka | PRA mockup → M3 jadi |
| Grafik riwayat (filter tanggal) | Eka | M2–M3 |
| Kontrol: sirkulasi, dosing A/B (+2 peristaltik **[ASUMSI]**), solenoid + jadwal otomatis | Eka + Rizki | M2–M3 |
| Log kalibrasi | Rizki | M1 |
| Cek penyakit (unggah foto → hasil AI) | Eka + Rizki | M5 |
| Live view CCTV **[ASUMSI — kalau CCTV mau tampil]** | Eka | M3–M4 |
| Mockup display LCD + dashboard (Figma/HTML statis) | Eka | PRA |

Eka juga: aplikasi (mobile/PWA) memakai API yang sama, sinkron tampilan dengan LCD.

### 3.5 Tim Perempuan — Dokumentasi · Postingan · Riset *(dibagi 3 peran; kalau cuma 2 orang → riset digabung ke dokumentasi)*

| Peran | Tugas harian | Tugas mingguan |
|---|---|---|
| **Koordinator Dokumentasi** | Foto/video minimal 1 momen kerja nyata, nama file `tgl-kkn-aktivitas.jpg`, simpan di `~/Documents/hydroponik/docs/dokumentasi/` | Backup arsip + kompilasi video mingguan |
| **Koordinator Postingan IG** | 1 konten/story per hari (tanpa bolong) — ambil dari arsip dokumentasi | Feed tematik per fase (lihat A8 `rencana-kkn.md`) + template desain |
| **Peneliti** | Mencatat temuan lapangan (suhu, kelembapan, gejala tanaman) | Riset penyakit & pertanian (A9: 6 penyakit + foto + link sumber), riset konten "kunjungan", bahan AI untuk Rizki |

Pita kerja per fasa (semua peran):
- **PRA:** template feed/story, shot list "riset & kunjungan", teaser alat (unboxing cek barang bareng Haqi/Rivi).
- **M1:** konten kunjungan & riset lokasi (hari 1–3), edukasi kalibrasi (hari 4–6).
- **M2–3:** progres sirkulasi & venturi, demo data ke web (story).
- **M4–5:** uji lapangan, hasil akhir, kompilasi video + dokumen foto untuk laporan.

---

## 4. Timeline dengan Penanggung Jawab

| Fasa | Kegiatan | PJ |
|---|---|---|
| PRA | Cek out barang (23+ item RAB) | Semua (bidang masing-masing) + Rizki koordinator |
| PRA | Wiring PCB + rakit panel + layout box | Haqi |
| PRA | Diagram blok hidrolik + belajar hidroponik | Rivi |
| PRA | Konfigurasi server + Raspberry Pi + AP/LAN + kontrak API | Rizki |
| PRA | Web/app + mockup display | Eka + Rizki |
| PRA | Template konten + riset penyakit mulai | Tim perempuan |
| M1 | Riset & ukur lokasi, pembersihan | Rivi (+ tim perempuan dokumentasi) |
| M1 | Kalibrasi pompa & 4 peristaltik | Rivi (Haqi bantu catat) |
| M1 | Kalibrasi pH, EC, suhu | Haqi |
| M1 | Dokumentasi + posting harian | Tim perempuan |
| M2 | Sirkulasi + venturi + pasang selang/pipa | Rivi |
| M2 | Wiring aktuator ke relay, firmware aktuator | Haqi |
| M2 | API aktuator + halaman grafik | Rizki + Eka |
| M3 | Integrasi end-to-end → API (TARGET SELESAI) | Haqi + Rizki + Eka |
| M3 | Setting jadwal sirkulasi & dosing di aplikasi | Rivi (angka) → Eka (implementasi) |
| M4 | Pasang permanen sensor + panel final | Haqi |
| M4 | Instalasi permanen air + uji lapangan 3 hari | Rivi |
| M4 | Stabilkan server + backup | Rizki |
| M5 | AI server MVP + halaman cek penyakit | Rizki + Eka |
| M5 | Uji menyeluruh 3 hari nonstop | Semua |
| M5 | Laporan akhir + kompilasi dokumentasi | Rizki (teknis) + Tim perempuan (foto/konten) |

---

## 5. Catatan untuk Rapat Pembukaan (30 menit cukup)

1. Konfirmasi 4 pompa peristaltik dipakai untuk apa (usulan: abimix A/B + pH up/down).
2. Peran Raspberry Pi: server lokal cadangan, otak AI, atau NVR CCTV?
3. Venturi untuk aerasi atau injeksi nutrisi?
4. Fix pin 4 peristaltik di firmware (usulan 32/13/15/2) + perbaikan pin float switch (27/14 tidak konsisten antar sketch).
5. Isi "Atribut Perairan 7 pcs" apa saja (dari RAB tidak dirinci).
6. Pembagian peran tim perempuan (3 peran → siapa isinya).
