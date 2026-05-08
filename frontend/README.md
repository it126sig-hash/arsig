# ARSIG Frontend (Web & Mobile)

Frontend ARSIG dibangun menggunakan **Vue 3** dan **PrimeVue**, yang di-wrap menggunakan **Capacitor** untuk distribusi sebagai aplikasi Android native.

## 🛠 Tech Stack

- **Framework**: Vue 3 (Vite)
- **UI Framework**: PrimeVue 4+
- **State Management**: Pinia
- **HTTP Client**: Axios (dengan auto-refresh JWT interceptor)
- **Mobile Engine**: Capacitor 6
- **Canvas Library**: Konva.js (untuk map lokasi fisik)

## 🚀 Pengembangan

### Prasyarat
- Node.js LTS
- Android Studio (untuk development mobile)

### Setup Awal
1. **Masuk ke direktori frontend**:
   ```bash
   cd frontend
   ```

2. **Install dependensi**:
   ```bash
   npm install
   ```

3. **Setup environment**:
   Buat file `.env`:
   ```env
   VITE_API_BASE_URL=https://your-api-domain.com/api/v1
   ```

4. **Jalankan development server**:
   ```bash
   npm run dev
   ```

### Build & Mobile (Android)

1. **Build web assets**:
   ```bash
   npm run build
   ```

2. **Sinkronisasi ke Android project**:
   ```bash
   npx cap sync android
   ```

3. **Buka di Android Studio**:
   ```bash
   npx cap open android
   ```

## 📱 Fitur Native (Capacitor)

- **Push Notifications**: Terintegrasi dengan Firebase Cloud Messaging (FCM).
- **Camera**: Digunakan untuk memfoto dokumen fisik saat proses upload.
- **Filesystem**: Menyimpan hasil download arsip langsung ke folder `Downloads` perangkat.
- **File Opener**: Membuka dokumen setelah berhasil diunduh.

## 🏗 Struktur Folder Utama

- `src/views/`: Halaman aplikasi (Login, Search, Explorer, Map).
- `src/stores/`: Pinia stores (Auth, Archive).
- `src/components/`: Komponen Vue reusable.
- `android/`: Project Android native (dihasilkan oleh Capacitor).
