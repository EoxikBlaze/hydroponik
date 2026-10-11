# PANDUAN LCD & KALIBRASI (tanpa komputer)

LCD 20×4 berganti 3 halaman otomatis. Tombol kalibrasi = **GPIO32 ↔ GND**.

## Tampilan LCD (3 halaman bergilir)

**Halaman 1 — Sensor**
```
Udara : 30.2°C
Lembap: 55.3%
Air   : 26.8°C
pH    : 6.8
```

**Halaman 2 — Status air & relay**
```
TDS: 512 ppm
Level: SEDANG        <- KURANG / SEDANG / PENUH / ERROR!!
RELAY: OFF           <- ON (isi) / OFF / LOCKED!! / EMERGENCY / OFFLINE-OF
State: IDLE          <- IDLE / FILLING / FULL / SEDANG / EMERGCY! / OFFLINE / MANUAL
```

**Halaman 3 — Bantuan tombol**
```
Tombol kalibrasi:
Pendek = tes buzzer
1 bip  = lepas: pH
2 bip  = lepas: TDS
```

## Alur tombol (GPIO32)

| Aksi di layar normal | Hasil |
|---|---|
| Tekan **pendek** (< 1,5 detik) lalu lepas | Tes buzzer (harus bunyi 2×) |
| Tahan sampai **1 bip** (±1,5 dtk) lalu lepas | Masuk **kalibrasi pH 3 titik** |
| Tahan sampai **2 bip** (±4,5 dtk) lalu lepas | Masuk **kalibrasi TDS 1 titik** |

Di dalam mode kalibrasi:

| Aksi | Hasil |
|---|---|
| Tekan **pendek** | Simpan titik yang sedang diukur |
| Tahan **2 detik** | Batal (kalibrasi lama tetap dipakai) |
| Diam **3 menit** | Batal otomatis |

## Kalibrasi pH 3 titik (buffer 4.01 / 6.86 / 9.18)

1. Siapkan 3 larutan buffer pH **4.01, 6.86, 9.18** + gelas kecil + air bilas.
2. Tahan tombol sampai **1 bip**, lepas → LCD: `KALIBRASI pH 1/3` + `Celup ke pH 4.01`.
3. Celup probe pH ke buffer **4.01**, aduk pelan, tunggu baris `Ukur:` stabil ±10 detik.
4. Tekan **pendek** → bip sukses, otomatis lanjut `KALIBRASI pH 2/3`.
5. Bilas probe, ulangi untuk **6.86** lalu **9.18** (langkah 3–4).
6. Setelah titik ke-3: LCD `pH TERSIMPAN!` + nilai slope/offset baru.
   Rumus: `pH benar = slope × pH terukur + offset` (tersimpan di EEPROM).
7. Verifikasi: celup ke salah satu buffer → LCD harus menunjukkan ±0,05 dari nilai buffer.

Gagal? LCD menampilkan `KALIBRASI GAGAL` bila 3 titik tidak membentuk garis lurus
(bacaan tidak stabil / salah larutan) — ulangi dari langkah 2.

## Kalibrasi TDS 1 titik

1. Siapkan larutan standar TDS dengan nilai pasti (default kode = **1382 ppm**;
   kalau larutanmu berbeda, ubah `TDS_STANDARD_PPM` di firmware).
2. Tahan tombol sampai **2 bip**, lepas → LCD: `KALIBRASI TDS` + `harus 1382 ppm`.
3. Celup probe TDS, aduk pelan, tunggu `Ukur:` stabil.
4. Tekan **pendek** → LCD `TDS TERSIMPAN!` + faktor baru (tersimpan di EEPROM).
5. Verifikasi: celup lagi ke larutan yang sama → pembacaan harus mendekati nilai std.

## Catatan penting

- Saat masuk mode kalibrasi, **pengisian air otomatis dihentikan** (probe keluar dari air).
- Batal = **tidak ada yang berubah** — kalibrasi lama tetap dipakai. Nilai baru tersimpan
  hanya setelah layar `TERSIMPAN!` + 2 bip panjang.
- Data kalibrasi awet di EEPROM (bukan flash) — aman saat firmware di-upload ulang.
- Alternatif via Serial Monitor 115200 (untuk lab):
  - TDS: `TDSCAL <ppm>` (mis. `TDSCAL 500`), `TDSRESET`, `TDSSTATUS`
  - pH: `ENTERPH` / `CALPH` / `EXITPH` (alat DFRobot)
- Nilai default hasil uji lab: pH `slope 1.009338, offset -1.115993`; TDS faktor `1.252`.
