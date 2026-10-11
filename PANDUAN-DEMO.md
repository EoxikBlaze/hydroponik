# PANDUAN DEMO — Website HarvestHouse

Akun demo sudah tersedia untuk presentasi. Data sensor yang tampil adalah **data real** dari alat di kebun (ESP32 terus mengirim tiap 30 detik).

## Akun Demo

| | |
|---|---|
| URL | https://harvesthouse.biz.id/login |
| Username | `demo` |
| Password | `demo123` |

Buat ulang akun (jika database di-migrate ulang):

```
php artisan migrate --force --no-interaction
php artisan db:seed --class=DemoUserSeeder --force --no-interaction
```

## Hak Akses Akun Demo

| Aksi | Demo | Admin |
|---|---|---|
| Lihat dashboard, monitoring, laporan (data real) | ✅ | ✅ |
| Lihat status tandon & kran (realtime) | ✅ | ✅ |
| **Tambah** data (meja, tanaman, siklus, semai, peremajaan, pendewasaan, panen) | ✅ sementara 1 jam | ✅ permanen |
| Edit / hapus data | ❌ | ✅ |
| Kelola user | ❌ | ✅ |
| Kontrol kran (buka/tutup, ganti mode) | ❌ | ✅ |

Catatan: data yang ditambah akun demo **otomatis terhapus maksimal 1 jam** (ditandai `expires_at`).
Pembersihan jalan otomatis: tiap request web + scheduler `demo:purge` tiap 5 menit.
Tombol edit/hapus otomatis tersembunyi untuk akun demo — pengunjung demo tidak akan melihatnya.

## Alur Demo (7 Hal yang Perlu Didemokan)

### 1. Login Akun Demo
- Buka website → login `demo` / `demo123`
- Tunjukkan badge **MODE DEMO** di pojok kanan atas + banner kuning penjelas mode demo

### 2. Dashboard — Data Real dari Kebun
- Tunjukkan kartu sensor: **Suhu Air Tandon, pH, TDS, Suhu Udara, Kelembaban, Level Air**
- Angka berubah real time (polling) — data asli dari ESP32 di greenhouse
- Tunjukkan status koneksi: **"Koneksi Aktif"** hijau + waktu terakhir data diterima

### 3. Monitoring — Grafik & Riwayat
- Buka menu **Monitoring**
- Tunjukkan grafik pH / TDS / suhu (24 jam terakhir)
- Tunjukkan deteksi anomali sensor (peringatan otomatis jika nilai tidak wajar)

### 4. Kontrol Air Tandon (tampilan saja untuk demo)
- Buka menu **Water Control**
- Tunjukkan visual level air tandon + status kran (BUKA/TUTUP) yang berubah realtime
- Jelaskan tombol kontrol sengaja **dinonaktifkan untuk akun demo** karena mengendalikan alat asli
- (Opsional) login `admin` untuk demo buka/tutup kran sungguhan

### 5. Tambah Data — Fitur Demo Sementara
- Buka menu **Meja** (atau Tanaman/Semai) → **Tambah**
- Isi data contoh → Simpan → data langsung muncul di tabel
- Jelaskan: *"Untuk demo, data ini otomatis hilang dalam 1 jam — tidak merusak data asli."*

### 6. Pembuktian Data Terhapus Otomatis
- Setelah tambah data, jalankan di server (opsional, kalau ada akses):
  `php artisan demo:purge`
- Refresh halaman → data demo tadi hilang, data asli tetap ada
- (Tanpa akses server: tunggu/jelaskan bahwa pembersihan jalan otomatis tiap 5 menit)

### 7. Laporan
- Buka menu **Laporan**
- Tunjukkan rekap semai → peremajaan → pendewasaan → panen per tanaman
- Tunjukkan filter periode (minggu/bulan/tahun) + rekap rata-rata sensor 6 jam terakhir

## Jawaban untuk Pertanyaan yang Sering Muncul

| Pertanyaan | Jawaban |
|---|---|
| *"Datanya asli?"* | Ya — dikirim ESP32 dari greenhouse tiap 30 detik, bukan data contoh |
| *"Kenapa tidak bisa edit/hapus?"* | Akun demo hanya untuk melihat + coba tambah; edit/hapus/kelola user/kontrol kran untuk admin |
| *"Kenapa data tadi hilang?"* | Fitur keamanan mode demo: data tambahan otomatis terhapus maksimal 1 jam |
| *"Kalau listrik/internet mati?"* | Alat punya mode offline (data lokal + LCD), otomatis sinkron saat online lagi |
