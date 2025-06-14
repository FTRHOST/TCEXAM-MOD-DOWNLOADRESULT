# 🚀 Modul Unduh Hasil Ujian untuk TCExam

[![Tampilan Modul](https://github.com/FTRHOST/TCEXAM-MOD-DOWNLOADRESULT/blob/main/doc/feature.png?raw=true "Tampilan Modul Unduh Hasil Ujian")](https://github.com/FTRHOST/TCEXAM-MOD-DOWNLOADRESULT/blob/main/doc/feature.png?raw=true)

Sebuah modul tambahan untuk aplikasi ujian **TCExam** yang memungkinkan pengunduhan hasil ujian dengan antarmuka yang lebih modern dan ramah pengguna. Modul ini terintegrasi melalui API sederhana dan menggunakan React.js untuk bagian antarmuka (frontend).

## ✨ Fitur Utama

-   📥 **Unduh Fleksibel**: Unduh hasil ujian berdasarkan **Kelas**, **Grup**, atau **Modul Ujian** tertentu.
-   🎨 **Antarmuka Modern**: Dibangun dengan React.js untuk pengalaman pengguna yang lebih baik.
-   🔌 **Integrasi API**: Terhubung langsung ke database TCExam melalui API untuk data yang real-time.
-   ⚙️ **Instalasi Mudah**: Petunjuk langkah demi langkah yang jelas untuk pemasangan backend dan frontend.
-   🔐 **Keamanan Dasar**: Dilengkapi dengan otorisasi API Key untuk melindungi akses data.

## 📚 Daftar Isi

-   [Prasyarat](#-prasyarat)
-   [Instalasi](#-instalasi)
    -   [Bagian 1: Pemasangan API di TCExam (Backend)](#bagian-1-pemasangan-api-di-tcexam-backend)
    -   [Bagian 2: Konfigurasi Antarmuka (Frontend)](#bagian-2-konfigurasi-antarmuka-frontend)
-   [Cara Penggunaan](#-cara-penggunaan)
-   [Kontribusi](#-kontribusi)
-   [Lisensi](#-lisensi)
-   [Ucapan Terima Kasih](#-ucapan-terima-kasih)

## 📋 Prasyarat

Sebelum memulai, pastikan Anda telah memiliki:

1.  Instalasi **TCExam** yang sudah berjalan.
2.  Akses ke server file dan database TCExam.
3.  **Git** terinstall di sistem Anda.
4.  **Node.js** dan **npm** (atau yarn) terinstall untuk bagian frontend.

## 🛠️ Instalasi

Proses instalasi dibagi menjadi dua bagian: pemasangan API pada TCExam (backend) dan konfigurasi antarmuka pengguna (frontend).

### Bagian 1: Pemasangan API di TCExam (Backend)

Langkah ini bertujuan untuk menambahkan sebuah endpoint API ke dalam instalasi TCExam Anda.

1.  **Clone Repositori** 📂
    Buka terminal atau command prompt di server Anda, lalu clone repositori ini:
    ```shell
    git clone https://github.com/FTRHOST/TCEXAM-MOD-DOWNLOADRESULT.git
    ```

2.  **Salin File Modul** 🔄
    Masuk ke direktori `TCEXAM-MOD-DOWNLOADRESULT/inject/`. Salin folder `admin` dan `shared` ke dalam direktori root instalasi TCExam Anda. Ini akan menggabungkan file-file yang diperlukan tanpa menimpa file inti lainnya.

3.  **Patch Konfigurasi TCExam** 📝
    Buka file `/shared/config/tce_paths.php` pada instalasi TCExam Anda. Tambahkan kode berikut tepat **di atas** baris `// DOCUMENT_ROOT fix for IIS Webserver`.

    ```php
    // File: /shared/config/tce_paths.php

    // ==== START MOD: TCExam Download Result API ====
    // Baris ini diperlukan untuk mendefinisikan path ke kode admin
    // yang akan digunakan oleh API.
    define('K_PATH_ADMIN_CODE', K_PATH_MAIN.'admin/code/');
    // ==== END MOD ====

    // DOCUMENT_ROOT fix for IIS Webserver
    if (!isset($_SERVER['DOCUMENT_ROOT']) OR (empty($_SERVER['DOCUMENT_ROOT']))) {
    ```

4.  **(Opsional tapi Sangat Direkomendasikan) Ganti API Key** 🔑
    Untuk alasan keamanan, ganti API Key default. Buka file `/admin/code/api.php` yang baru saja Anda salin dan ubah `SECRET_API_KEY`.

    ```php
    // File: /admin/code/api.php

    // API Key authorization check
    $api_key = isset($_GET['api_key']) ? sanitize_input($_GET['api_key']) : '';
    
    // ⚠️ GANTI DENGAN KUNCI RAHASIA YANG KUAT!
    $SECRET_API_KEY = 'GANTI_DENGAN_API_KEY_RAHASIA_ANDA'; 

    if ($api_key !== $SECRET_API_KEY) {
        send_json_response(['error' => 'Invalid API Key'], 401);
        exit;
    }
    ```

5.  **Verifikasi Pemasangan** ✅
    Buka browser dan akses URL API Anda: `https://namawebtes.com/admin/code/api.php`. Jika Anda melihat pesan JSON seperti di bawah ini, artinya API sudah berhasil terpasang!
    ```json
    {"error":"Invalid API Key"}
    ```
    Pesan ini muncul karena Anda belum menyertakan API Key pada URL, yang menandakan sistem keamanan berfungsi.

### Bagian 2: Konfigurasi Antarmuka (Frontend)

Sekarang kita akan menyiapkan halaman unduh yang akan digunakan oleh pengguna.

1.  **Konfigurasi API Endpoint** ⚙️
    Masuk ke direktori repositori yang telah di-clone, lalu buka file `src/services/api.js`. Sesuaikan `API_URL` dan `API_KEY` dengan konfigurasi Anda.

    ```javascript
    // File: src/services/api.js

    // Sesuaikan dengan URL root instalasi TCExam Anda
    const API_URL = 'https://namawebtes.com/'; 

    // Gunakan API Key yang SAMA dengan yang Anda atur di backend
    const API_KEY = 'GANTI_DENGAN_API_KEY_RAHASIA_ANDA'; 
    ```

2.  **Install Dependencies** 📦
    Di dalam direktori root frontend (misal: `TCEXAM-MOD-DOWNLOADRESULT/`), jalankan perintah berikut untuk menginstall semua paket yang dibutuhkan:
    ```shell
    npm install
    ```

3.  **Build Aplikasi** 🏗️
    Setelah instalasi selesai, build aplikasi React untuk produksi:
    ```shell
    npm run build
    ```
    Perintah ini akan membuat folder `build` yang berisi semua file statis (HTML, CSS, JS) yang siap untuk di-deploy.

4.  **Deploy Frontend** 🚀
    Salin seluruh isi dari folder `build` ke direktori di web server Anda. Anda bisa menempatkannya di subdomain (misal: `download.namawebtes.com`) atau subfolder (misal: `namawebtes.com/download-nilai`).

## 🎈 Cara Penggunaan

1.  Akses URL tempat Anda men-deploy frontend (misalnya `https://namawebtes.com/download-nilai`).
2.  Gunakan filter yang tersedia untuk memilih data berdasarkan **Kelas**, **Grup**, atau **Modul**.
3.  Klik tombol "Unduh Hasil" untuk mendapatkan file hasil ujian dalam format yang sesuai.

## 🙌 Kontribusi

Kontribusi Anda sangat kami hargai! Jika Anda ingin membantu mengembangkan proyek ini, silakan:

1.  **Fork** repositori ini.
2.  Buat **Branch** baru (`git checkout -b fitur/NamaFiturBaru`).
3.  **Commit** perubahan Anda (`git commit -m 'Menambahkan fitur XYZ'`).
4.  **Push** ke branch tersebut (`git push origin fitur/NamaFiturBaru`).
5.  Buka **Pull Request**.

## 📜 Lisensi

Proyek ini dilisensikan di bawah [Lisensi MIT](LICENSE).

## 🙏 Ucapan Terima Kasih

-   Terima kasih kepada **FTRHOST** atas pengembangan awal modul ini.
-   Dukungan dari **MA NU 01 Banyuputih dan MANSABA MEDIA** yang telah menginspirasi dan mendukung proyek ini.
