# Peningkatan Fitur Pindah Lokasi Fisik Arsip (MoveLocationDialog.vue)

## Tujuan
Memperbaiki user experience (UX) pada fitur "Pindah Lokasi Fisik Arsip" di dalam komponen `MoveLocationDialog.vue`. Ada dua peningkatan utama:
1. Menampilkan informasi lokasi fisik arsip yang **sekarang/sebelumnya** sebagai referensi bagi pengguna sebelum memindahkan arsip.
2. Menambahkan visualisasi interaktif berupa denah lantai (`FloorPlanViewer`) dan grid kabinet (`CabinetDoorGrid`) pada saat pengguna memilih lokasi baru, sama persis seperti fitur yang ada di `ArchiveUploadDialog.vue`.

## Target File
1. `frontend/src/components/MoveLocationDialog.vue` (Target Utama)

## Instruksi Pengerjaan (High-Level)

### 1. Menampilkan Lokasi Fisik Sebelumnya
- Di bagian atas form `MoveLocationDialog.vue` (misalnya di bawah atau di dalam kotak informasi nama arsip), tambahkan sebuah block UI untuk menampilkan **"Lokasi Saat Ini"**.
- Ambil data lokasi saat ini dari *props* `archive` yang dikirim ke komponen:
  - Lantai: `archive.floor?.name` atau `Belum ditentukan`
  - Ruangan: `archive.room?.name`
  - Lemari: `archive.cabinet?.name`
  - Slot/Rak: `archive.cabinet_slot?.name`
- Desain UI ini agar terlihat seperti *readonly info* (misal dengan icon atau badge) agar pengguna dapat dengan mudah membandingkan lokasi lama dan lokasi baru.

### 2. Menambahkan Visualisasi Lokasi (Denah & Lemari)
- Lakukan *import* komponen visualisasi yang sudah ada ke dalam `MoveLocationDialog.vue`:
  ```javascript
  import FloorPlanViewer from '@/components/FloorPlanViewer.vue'
  import CabinetDoorGrid from '@/components/CabinetDoorGrid.vue'
  ```
- Buat beberapa *computed properties* untuk mendapatkan objek data utuh dari referensi *id* yang **baru** dipilih pada form:
  - `selectedFloor` (cari dari array `floors` berdasarkan `form.new_floor_id`)
  - `selectedRoom` (cari dari array `rooms` berdasarkan `form.new_room_id`)
  - `selectedCabinet` (cari dari array `cabinets` berdasarkan `form.new_cabinet_id`)
  - `selectedRoomCoords` dan `selectedCabinetCoords` (parse JSON dari property `coordinates` milik `selectedRoom` dan `selectedCabinet` jika ada).
- Buat/salin fungsi helper `getFullImageUrl(path)` (bisa dicontoh dari `ArchiveUploadDialog.vue`) untuk merender URL gambar denah lantai (`selectedFloor.floor_plan_image`).
- Di dalam template form (di bawah *dropdown* pilihan lokasi atau dalam grid tersendiri), tambahkan struktur layout untuk visualisasi:
  - **Area Denah Lantai**: Jika `selectedFloor` memiliki `floor_plan_image`, render `<FloorPlanViewer>`. Berikan prop `imageUrl`, `highlightedRoomCoords`, dan `highlightedCabinetCoords`.
  - **Area Visual Kabinet**: Jika `selectedCabinet` dipilih, render `<CabinetDoorGrid>`. Berikan prop `doorCount` (default ke 1 jika kosong), list `slots`, `highlightedSlotId` (binding ke `form.new_cabinet_slot_id`), dan dengarkan event `@slot-click` agar saat slot visual diklik, dropdown slot-nya ikut berubah nilainya.
- Beri batasan tinggi pada visualisasi tersebut (misal `h-80` atau `h-[300px]`) dan buat layoutnya berdampingan di desktop (`md:col-span-6`) atau bertumpuk di mobile.

### 3. Testing & Validasi
- Pastikan setiap perubahan *dropdown* (misal ganti lantai) me-reset ruangan dan kabinet *sekaligus* meng-update tampilan denah.
- Cek interaksi pada `CabinetDoorGrid`, pastikan ketika sebuah slot di-klik, form.new_cabinet_slot_id berubah, dan sebaliknya (jika dari dropdown berubah, kotak di grid ikut ter-highlight).
- Cek tidak ada error console terkait "parsing JSON coordinates" saat `coordinates` kosong atau tidak valid.
