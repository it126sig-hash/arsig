# SKILL: Konva.js Interactive Map

## CONTEXT
Membangun visualisasi interaktif dari lokasi penyimpanan fisik arsip (Lantai -> Ruang -> Lemari) menggunakan library Konva.js di frontend.

## RULES & CONSTRAINTS
1. **Data Binding:** Koordinat objek (ruang/lemari) dibaca dan disimpan dalam format JSON ke database. Pastikan mapping sinkron dengan rendering Konva.js.
2. **Update Trigger (Kritis):** Jika entitas `floors.floor_plan_image` diperbarui, backend WAJIB mengeset flag `needs_coordinate_review = true` pada semua tabel `rooms` dan `cabinets` yang terelasi dengan lantai tersebut.
3. **Interaktivitas:** Peta harus mendukung aksi klik untuk menavigasi dari level Lantai hingga melihat daftar *slot* arsip dalam Lemari.