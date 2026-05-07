# Planning Pengerjaan Perbaikan Modul Arsip & OTP

Dokumen ini berisi rencana implementasi untuk perbaikan bug request OTP dan penyesuaian fitur edit arsip sesuai dengan instruksi yang diberikan.

## 1. Perbaikan Bug Request OTP ke PIC Tidak Masuk ke Table
**Masalah:** 
Saat ini method `requestOtp` di `ArchiveController` hanya berupa mock/dummy response (`OTP has been sent...`) dan belum menyimpan data permintaan ke dalam tabel `archive_download_requests`.

**Rencana Implementasi:**
- Buka file `app/Http/Controllers/ArchiveController.php`.
- Modifikasi method `requestOtp(Archive $archive)` agar:
  1. Mendapatkan `user_id` dari user yang sedang login (`auth()->id()`).
  2. Melakukan insert ke tabel `archive_download_requests` menggunakan model `ArchiveDownloadRequest`.
  3. Set `status` menjadi `pending`.
  4. Return response sukses setelah data berhasil disimpan.

## 2. Validasi Mencegah Duplikasi Request OTP Aktif
**Masalah:** 
User dapat mengirimkan request OTP berulang kali, yang akan menyebabkan penumpukan request di sisi PIC (Person In Charge) meskipun OTP sebelumnya belum expired atau request sebelumnya belum diproses.

**Rencana Implementasi:**
- Di dalam method `requestOtp` (`ArchiveController.php`), tambahkan pengecekan ke database sebelum membuat record baru.
- Query untuk mengecek apakah user login sudah memiliki record di `archive_download_requests` untuk `$archive->id` yang sama, dengan kondisi:
  - `status` = 'pending' (Request belum di-approve/reject) 
  - ATAU (`status` = 'approved' DAN `otp_expires_at` > `now()`) (OTP masih aktif dan belum expired).
- Jika ditemukan record dengan kondisi di atas, kembalikan `errorResponse` (HTTP 400/422) dengan pesan bahwa "Permintaan OTP sedang diproses atau OTP sebelumnya masih aktif".

## 3. Tambahkan Opsi "Ubah Archive" pada Tabel PIC
**Masalah:** 
Di tabel arsip yang dilihat oleh PIC (Action Menu/Options button), opsi untuk "Ubah Archive" belum tersedia, berbeda dengan apa yang bisa dilihat/diakses melalui menu File Explorer biasa.

**Rencana Implementasi:**
- Temukan komponen tabel terkait (misalnya di `HomeView.vue`, `RequestApprovalView.vue`, atau view di mana PIC melihat daftar arsip).
- Tambahkan item baru "Ubah Archive" (Edit Archive) dengan icon pencil pada `actionMenuItems` (menu dropdown/ellipsis).
- Pastikan opsi ini memanggil fungsi untuk membuka modal `ArchiveEditDialog.vue` sambil passing data `archive` yang dipilih.
- Pastikan juga logic `v-if` atau `disabled` disesuaikan agar tombol Edit hanya muncul/aktif jika user yang sedang login adalah PIC dari arsip tersebut atau memiliki hak akses yang sesuai.

## 4. Nonaktifkan Update Lokasi Fisik Saat "Ubah Archive"
**Masalah:** 
Saat ini, ketika user atau PIC mengubah data arsip (Edit Archive), field untuk lokasi fisik (Lantai, Ruangan, Lemari, Laci) masih bisa diubah. 

**Rencana Implementasi:**
- **Frontend:** Buka `frontend/src/components/ArchiveEditDialog.vue`. Tambahkan atribut `disabled` pada seluruh input/dropdown yang berkaitan dengan lokasi fisik (Floor, Room, Cabinet, Slot), atau jadikan bagian tersebut sebagai informasi statis (read-only) saat mode edit.
- **Backend (Opsional/Validasi):** Buka `app/Http/Requests/UpdateArchiveRequest.php` dan pastikan field relasi lokasi (`floor_id`, `room_id`, dll.) dihapus dari array `rules()` atau minimal tidak diproses saat melakukan `update` di `ArchiveService.php` agar sistem menolak perubahan lokasi fisik.

## 5. Pindah Lokasi Arsip Dibuatkan Tombol Terpisah (Tiket Lain)
**Informasi / Konteks:** 
Perubahan lokasi fisik arsip (memindahkan arsip antar lemari/ruangan) **TIDAK** boleh dilakukan melalui fitur Edit Metadata ("Ubah Archive").
- **Tindakan saat ini:** Memastikan Poin 4 diimplementasikan dengan benar.
- **Tindakan selanjutnya:** Pembuatan tombol dan modal khusus "Pindah Lokasi Fisik" pada menu Options (sudah ada placeholder `Pindah Lokasi File Fisik` di `HomeView.vue`) akan diimplementasikan dalam task/tiket terpisah. Tidak ada action code yang diperlukan untuk poin ini di tahap ini.
