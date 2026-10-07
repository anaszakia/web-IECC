<p align="center">
  <a href="https://laravel.com" target="_blank">
    <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="350" alt="Laravel Logo">
  </a>
</p>

<h1 align="center">🚀 Laravel 13 RBAC & Object Storage Golden Base Project</h1>

<p align="center">
  <strong>Base starter kit aplikasi enterprise Laravel 13</strong> yang dilengkapi dengan Role-Based Access Control (RBAC), Granular Permissions, Dynamic Menu Management, Passkey Authentication, Google OAuth, S3/MinIO & Local Flexible Storage, serta optimasi Raw Query untuk skalabilitas jutaan data.
</p>

<p align="center">
  <em>⚡ Powered by <strong>ANAS ZAKIA ARDHAN</strong></em>
</p>

---

## 📑 Daftar Isi
- [Spesifikasi Sistem](#-spesifikasi-sistem)
- [Teknologi & Dependensi](#-teknologi--dependensi)
- [Fitur Utama](#-fitur-utama)
- [Arsitektur & Keamanan](#-arsitektur--keamanan)
- [Panduan Instalasi](#-panduan-instalasi)
- [Konfigurasi Environment (.env)](#-konfigurasi-environment-env)
- [Kredensial Default](#-kredensial-default)
- [Menjalankan Aplikasi](#-menjalankan-aplikasi)

---

## ⚙️ Spesifikasi Sistem

| Komponen | Spesifikasi Minimum / Rekomendasi |
| :--- | :--- |
| **PHP** | `^8.3` atau lebih baru |
| **Database** | MySQL `8.0+`, MariaDB `10.4+`, atau PostgreSQL |
| **Memory Limit** | Minimal `128 MB` (Disarankan `256 MB+`) |
| **Node.js** | `v18.x` atau `v20.x+` |
| **NPM** | `v9.x` atau `v10.x+` |
| **Composer** | `v2.x+` |
| **Cache & Session** | Redis Server `v6.0+` (Opsional: Database / File) |
| **Object Storage** | MinIO Server (Opsional: Local Disk Storage) |

---

## 🛠️ Teknologi & Dependensi

### Backend & Core
* **Framework**: Laravel 13.x
* **PHP SDK**: PHP 8.3+
* **Authentication**: 
  * Laravel Fortify & Session Guard
  * WebAuthn / Passkeys (`@laravel/passkeys`, biometric/FIDO2 support)
  * OAuth 2.0 Social Login via `laravel/socialite` (Google Login)
* **Object Storage Client**: AWS S3 SDK PHP (`aws/aws-sdk-php` & `league/flysystem-aws-s3-v3`)
* **In-Memory Cache & Session**: Predis (`predis/predis`)

### Frontend
* **Build Tool**: Vite 7.x (`@tailwindcss/vite`, `laravel-vite-plugin`)
* **Styling**: Bootstrap 5 + Tabler Icons + Tailwind CSS Utility Engine
* **Asset Bundler**: Concurrently untuk background processes

---

## ✨ Fitur Utama

1. 🔐 **Role-Based Access Control (RBAC)**:
   * Multi-role support per user (One-to-many & Many-to-many pivot).
   * Superadmin bypass & custom role assignments.
2. 🛡️ **Granular Route Permissions**:
   * Auto Permission Detection & Synchronization dari route (`permission:resource.action`).
   * Trait `HasAutoPermissions` untuk controller otomatis.
3. 🧭 **Dynamic Multi-Level Menu Management**:
   * Nested/Hierarchical Menu Tree (Parent & Children).
   * Role-to-Menu permission mapping.
4. 💾 **Flexible Storage Engine (MinIO / Local Public)**:
   * Pengaturan toggle storage melalui `.env` (`MINIO_ACTIVE=true|false`).
   * Fallback otomatis ke public storage (`storage/app/public`) jika MinIO dinonaktifkan.
   * Universal Storage Helpers (`storage_avatar`, `storage_url`, `storage_upload`, `storage_delete`).
5. ⚡ **Skalabilitas Jutaan Data (Lean Query Engine)**:
   * Query dioptimasi dengan Direct Database JOIN & specific column projection (mencegah memory leak / hydration overhead).
6. 🔑 **Modern Authentication**:
   * Email & Password dengan **Password Strength Meter** (Lemah, Sedang, Kuat, Sangat Kuat).
   * Password policy: Wajib huruf besar, kecil, angka, simbol, minimal 8 digit, dan anti-username/email match.
   * Google OAuth 2.0 & Passkeys (Biometrik / Fingerprint / FaceID).

---

## 🔒 Arsitektur & Keamanan

* **Anti-Brute Force (Login Throttling)**: Maksimal 5 kali percobaan login gagal dengan masa *lockout* akun selama 15 menit.
* **SQL Injection Protection**: Prepared Statements & PDO parameter binding di semua Model methods.
* **Security HTTP Headers**:
  * `X-Frame-Options: SAMEORIGIN` (Anti-Clickjacking).
  * `X-Content-Type-Options: nosniff` (Anti-MIME sniffing).
  * `X-XSS-Protection: 1; mode=block`.
  * `Referrer-Policy: strict-origin-when-cross-origin`.
  * `Permissions-Policy: camera=(), microphone=(), geolocation=()`.
* **Ghost Session & Session Fixation Protection**: Validasi status aktif akun secara realtime pada middleware.
* **Auto Session Logout**: Logout otomatis setelah 10 menit tidak aktif.

---

## 🚀 Panduan Instalasi

### 1. Clone Repositori
```bash
git clone https://github.com/anaszakia/laravel-RBAC.git
cd laravel-RBAC
```

### 2. Install Dependensi Composer & NPM
```bash
composer install
npm install
```

### 3. Setup File Environment
Salin file konfigurasi environment:
```bash
cp .env.example .env
```
Generate Encryption Application Key:
```bash
php artisan key:generate
```

### 4. Konfigurasi Database & Storage di `.env`
Buka file `.env` dan sesuaikan kredensial database Anda:
```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel_rbac
DB_USERNAME=root
DB_PASSWORD=
```

### 5. Jalankan Database Migration & Seeder
```bash
php artisan migrate --seed
```

### 6. Hubungkan Storage Link (Wajib untuk Local Storage)
```bash
php artisan storage:link
```

### 7. Compile Frontend Assets
```bash
npm run build
```

---

## ⚙️ Konfigurasi Environment (.env)

### Pengaturan Storage (MinIO vs Local)
```dotenv
# Aktifkan MinIO (Cloud S3 Compatible)
MINIO_ACTIVE=true
FILESYSTEM_DISK=minio

# Konfigurasi MinIO
AWS_ACCESS_KEY_ID=minioadmin
AWS_SECRET_ACCESS_KEY=minioadmin123
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=dev
AWS_ENDPOINT=http://127.0.0.1:9000
AWS_USE_PATH_STYLE_ENDPOINT=true

MINIO_BUCKET=laravel-rbac
MINIO_ENDPOINT=http://127.0.0.1:9000
MINIO_KEY=minioadmin
MINIO_SECRET=minioadmin
MINIO_REGION=us-east-1
MINIO_URL=http://127.0.0.1:9000/laravel-rbac
```

> **Catatan**: Jika ingin menggunakan local storage tanpa MinIO server, cukup atur:
> ```dotenv
> MINIO_ACTIVE=false
> FILESYSTEM_DISK=public
> ```

---

## 👤 Kredensial Default (Seeder)

Setelah menjalankan `php artisan db:seed`, Anda dapat masuk dengan akun SuperAdmin:

* **URL Login**: `http://localhost:8000/login`
* **Email**: `superadmin@gmail.com`
* **Password**: `12345678`
* **Role**: `SuperAdmin` (Memiliki seluruh hak akses menu & permission)

---

## ▶️ Menjalankan Aplikasi

Jalankan server aplikasi lokal:
```bash
php artisan serve
```
Atau jalankan seluruh service (Server, Queue, Logs, Vite) secara bersamaan:
```bash
composer run dev
```

Buka browser Anda dan akses: **`http://localhost:8000`**

---

## 📄 Lisensi
Project ini dilisensikan di bawah [MIT License](LICENSE).

---

<p align="center">
  Crafted with ❤️ by <strong>ANAS ZAKIA ARDHAN</strong>
</p>
