# Catatan Cross-Check — Dokumen Perancangan IoT Hidroponik v1.0 (3 Okt 2026)

Dibandingkan dengan: RAB Arutmin, `rencana-kkn.md`, `tugas-tim-kkn.md`.
Status: isi dokumen sudah dipecah jadi **7 tugas baru** di Notion (total kini 59 tugas).

## Ringkasan isi dokumen
- **Sistem:** NFT & DFT, satu tandon nutrisi untuk semua unit, kontrol EC + pH otomatis.
- **Greenhouse:** 3 meja NFT + 2 DFT (dinding + luar), total 1.426 lubang (NFT 780 + DFT 646), pipa 4 m, kemiringan 2–3°, jarak tandon ±5 m.
- **Aktuator:** dosing A, dosing B, pH Up, pH Down, pompa pengaduk, pompa sirkulasi utama (nyala terus), solenoid, aerator (nyala terus).
- **Sensor:** pH, EC, suhu air (kompensasi EC), suhu & lembap udara, 3 float switch (tengah = pemicu isi, atas = penghenti, dasar = proteksi pompa kering).
- **Jalur sampel:** 2 pipa berkelok (pH & EC, suhu digabung di EC), probe di bagian aliran naik, titik ambil/jauh dari titik dosing.
- **Alur kontrol 7 tahap:** Isi air → baca/evaluasi (median) → dosing bergantian → pengaduk + delay → retry maks 5 → monitoring 30 menit → alert + LOCK (reset oleh petani).
- **Fail-safe:** pompa dosing OFF saat boot, batas ON maks + watchdog, counter/LOCK di flash, proteksi pompa kering, anti-sifon, dosing EC dulu baru pH, pemulihan listrik mati tanpa mengulang dosing terputus.

## ⚠️ Data yang perlu disamakan (tugas "Verifikasi spesifikasi greenhouse" di Notion)
| # | Masalah | Di dokumen perancangan | Di RAB / rencana lama | Tindakan |
|---|---|---|---|---|
| 1 | Volume tandon | ±3000 L (bagian 1.1) vs **4000 L** (bagian 4) | — | Samakan angka di v1.1 |
| 2 | Float switch | 3 (tengah/atas/dasar) | RAB **2 unit** | Beli 1 lagi atau setuju dasar opsional |
| 3 | Pompa pengaduk & aerator | Wajib ada | Tidak eksplisit di RAB (ada pompa celup mini ×2 opsional, Taffware ×2) | Konfirmasi alokasi unit |
| 4 | Pompa sirkulasi | **Menyala terus**, dimatikan petani | Rencana lama: jadwal 15:15 menit | Pakai aturan dokumen, coret jadwal |
| 5 | Sensor opsional (DO, lux, arus pompa) | Menyusul bila perlu | Tidak ada di RAB | Pastikan ditunda |
| 6 | Notifikasi petani | "Notifikasi dikirim ke petani" | WA/Fonnte sudah dihapus dari codebase | Pastikan lewat web/app + buzzer |

## Tugas baru di Notion (dari dokumen ini)
| Fasa | Tugas | PJ |
|---|---|---|
| PRA-KKN | Verifikasi spesifikasi greenhouse: samakan data dengan RAB | Rivi, Rizki, Nova |
| Minggu 2 | Jalur sampel sensor (2 pipa berkelok + pompa sampel) | Haqi, Rivi |
| Minggu 2 | Pompa pengaduk + aerator: peran, posisi, wiring | Haqi, Rivi |
| Minggu 3 | Logika kontrol 7 tahap di firmware (Tahap 1–7) | Haqi |
| Minggu 3 | Aturan batas & fail-safe firmware | Haqi, Rizki |
| Minggu 3 | Alert ke petani + mode LOCK & reset di web/app | Rizki, Eka |
| Minggu 4 | Uji alur kerja 7 tahap (simulasi lapangan) | Haqi, Rivi |

## Catatan teknis untuk Haqi (firmware)
- Dosis dinyatakan dalam **ΔEC** (bukan mL) → konversi ke durasi peristaltik pakai kalibrasi ml/menit (Rivi, minggu 1).
- "Tunggu baca" jalur sampel ± 1–2 menit (2–3 × waktu tempuh air) ≠ "delay pencampuran" (puluhan menit–jam, = volume sistem ÷ debit sirkulasi). Jangan tertukar.
- Dosing setelah pengisian air = kejadian khusus: bebas jeda minimum & tidak dihitung batas harian (tapi ΔEC maks, retry 5, timeout tetap berlaku).
- Sensor **hanya membaca (log)** selama dosing/pengadukan/solenoid aktif; keputusan hanya saat semua proses selesai.
- Nilai "…" di dokumen diisi setelah kalibrasi + uji lapangan → dokumen naik v1.1 (tugas Minggu 4).
