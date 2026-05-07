# Implementasi Visualisasi Lokasi Fisik di ArchiveDetailModal

## Tujuan
Mengimplementasikan visualisasi denah ruangan fisik menggunakan KonvaJS di dalam modal detail arsip ketika `viewMode` diubah ke mode lokasi. Visualisasi wajib menampilkan area ruangan, posisi lemari tempat arsip berada, dan indikator letak/bukaan **pintu lemari**. Layout modal juga harus disesuaikan agar canvas Konva tampil responsif dan memberikan *user experience* yang nyaman.

## Target File
1. `frontend/src/components/ArchiveDetailModal.vue` (Edit)
2. `frontend/src/components/LocationVisualizer.vue` (Buat Baru - Opsional tapi direkomendasikan untuk modularitas)

## Instruksi Pengerjaan (High-Level)

### 1. Pembuatan Visualizer Component (`LocationVisualizer.vue`)
- Buat komponen Vue baru yang menggunakan KonvaJS untuk rendering canvas.
- **Props yang dibutuhkan**: 
  - `floor` (untuk mendapatkan URL gambar dasar/denah).
  - `room` (untuk batasan koordinat polygon ruangan).
  - `cabinet` (untuk koordinat posisi lemari yang disorot).
- **Proses Rendering Canvas**:
  - Render gambar denah lantai (`floor`) sebagai layer paling dasar.
  - Gambar poligon ruangan (`room`) dengan *fill* transparan atau *stroke* agar terlihat batas ruangannya.
  - Gambar area lemari (`cabinet`) yang menampung arsip tersebut. Beri warna *highlight* yang kontras (misalnya biru atau hijau muda) agar mudah dikenali.
  - **Visualisasi Pintu Lemari**: Tambahkan indikator visual pada objek lemari untuk menandakan arah bukaan atau letak pintu lemari. Ini bisa berupa garis tebal berwarna (misal: garis merah di salah satu sisi polygon), icon kecil, atau busur (*arc*). Analisa array titik koordinat lemari untuk menempatkan indikator ini.

### 2. Integrasi ke Modal Detail (`ArchiveDetailModal.vue`)
- Import komponen `LocationVisualizer` ke dalam `ArchiveDetailModal.vue`.
- Temukan blok kode berikut di bagian **Location Mode**:
  ```html
  <div v-else-if="viewMode === 'location'" class="w-full h-full flex flex-col p-4 animate-fade-in">
      <div class="flex-1 bg-white border border-slate-200 rounded-lg overflow-hidden relative flex items-center justify-center">
          <!-- Ganti placeholder di bawah ini dengan komponen Visualizer -->
          <p class="text-slate-400 italic">[Visualisasi Denah: {{ archive.floor?.name }} > {{ archive.room?.name }}]</p>
          <!-- Future: Integrasi Konva.js Visualizer -->
      </div>
      <!-- ... -->
  </div>
  ```
- Inject komponen visualizer di sana dan passing *props* dari objek `archive` (`archive.floor`, `archive.room`, `archive.cabinet`).

### 3. Penyesuaian Layout & Experience (UX)
- **Responsive Stage**: Pastikan Konva Stage bisa mengkalkulasi proporsi lebarnya menyesuaikan dengan *container div* dari grid modal. Gunakan `ResizeObserver` atau *window resize listener* agar canvas tidak meluber/terpotong.
- **Auto-Fit & Scale**: Hitung ukuran koordinat ruangan/lemari, lalu kalibrasi `scale` dari stage agar titik fokus langsung berada di lemari yang bersangkutan, tanpa user harus mencari secara manual di map besar.
- **Interaktivitas Dasar**: Aktifkan fitur `draggable` (pan) dan `wheel` (zoom) pada layer/stage KonvaJS agar pengguna bisa memperbesar/memperkecil denah ruangan secara leluasa.
- Pastikan modal tetap terlihat estetik di layar desktop maupun layar yang lebih kecil tanpa adanya *scroll* tambahan yang tidak perlu di area luar canvas.
