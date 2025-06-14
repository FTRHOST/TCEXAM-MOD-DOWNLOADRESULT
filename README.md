# Tcexam Mod Result Download (user friendly)


[![Feature](https://github.com/FTRHOST/TCEXAM-MOD-DOWNLOADRESULT/blob/main/doc/feature.png?raw=true "Feature")](https://github.com/FTRHOST/TCEXAM-MOD-DOWNLOADRESULT/blob/main/doc/feature.png?raw=true "Feature")


**Table of Contents**

[TOC]

##Cara menginstall API di tcexam
Aplikasi ini terintegrasi dengan API (Application Programming Interface) pada aplikasi ujian Tcexam yang terhubung langsung dengan database. Ikuti langkah berikut untuk menjalankan fitur API pada tcexam.
Cara pemasangan:
1. Git Clone projek ini
```shell
git clone https://github.com/FTRHOST/TCEXAM-MOD-DOWNLOADRESULT.git
```
2. Masuk ke folder inject copy folder admin dan shared ke dalam folder root tcexam.
3. Buka file shared/config/tce_paths.php dan paste kode berikut diatas
// DOCUMENT_ROOT fix for IIS Webserver

```php
//====START MOD====
// Tambahkan baris kode ini untuk patch tce api download result
// pada file /shared/config/tce_paths.php

/**
 * Full path to admin code directory.
 */
define('K_PATH_ADMIN_CODE', K_PATH_MAIN.'admin/code/');

//======KODE END MOD

// DOCUMENT_ROOT fix for IIS Webserver
```

4. Setelah itu cek pada link https://namawebtes.com/admin/code/api.php
kalau muncul keterangan JSON seperti ini, itu tandanya sudah berhasil
```json
{"error":"Invalid API Key"}
```
5. (Opsional) Edit API key yang anda mau, buka file /admin/code/api.php dan edit pada bagian ini dan ganti API key default yaitu "hahay"

```php
// API Key authorization check
$api_key = isset($_GET['api_key']) ? sanitize_input($_GET['api_key']) : '';
$SECRET_API_KEY = 'hahay'; // GANTI DENGAN KUNCI RAHASIA YANG KUAT!

if ($api_key !== $SECRET_API_KEY) {
    send_json_response(['error' => 'Invalid API Key'], 401);
    exit;
}
```

##Langkah frontend atau Landing Page Download
Pada langkah ini kita menggunakan framework javascript untuk menjalankan page download yang user friendly berikut cara pemasanganya:
1. Setelah selesai pemasangan API pada tcexam, langkah berikutnya adalah mengkonfigurasi config pada frontend.
2. Buka file konfigurasi di src/services/api.js
```php
//silahkan sesuaikan dengan url api kalian
const API_URL= 'https://namawebtes.com/';
const API_KEY = 'hahay'; // GANTI DENGAN KUNCI RAHASIA YANG SAMA DENGAN BACKEND!
```
3. Selanjutnya install module reactnya
```shell
npm i
```
4. Selanjutnya build aplikasi
```shell
npm run build
```
5. Selanjutnya jalankan dist yang telah dibuild oleh projectnya dengan webserver.

###terima kasih untuk supportnya by ftrhost and MA NU 01 Banyuputih

###End