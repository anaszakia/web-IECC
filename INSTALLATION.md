# 📦 Panduan Instalasi Laravel 13 Base Project

Dokumen ini mencakup langkah-langkah lengkap untuk menginstal dan mengkonfigurasi sistem, termasuk Redis dan MinIO.

## 📋 Prasyarat Sistem

- **PHP**: 8.3 atau lebih tinggi
- **Composer**: ^2.0
- **MySQL/MariaDB**: Aktif dan berjalan
- **Redis**: Untuk session dan cache
- **MinIO**: Untuk object storage
- **Node.js**: ^20.0 (untuk asset compilation)

---

## 🚀 Tahap 1: Setup Project

### 1.1 Clone Repository

```bash
cd e:\laragon\www
git clone <repository-url> laravel13-base-project
cd laravel13-base-project
```

### 1.2 Install PHP Dependencies

```bash
composer install
```

### 1.3 Install Node Dependencies

```bash
npm install
```

### 1.4 Copy Environment File

```bash
# Copy dari .env.example ke .env
cp .env.example .env
```

### 1.5 Generate Application Key

```bash
php artisan key:generate
```

---

## 🗄️ Tahap 2: Setup Database (MySQL)

### 2.1 Buat Database Baru

```bash
# Buka MySQL client
mysql -u root

# Jalankan perintah berikut
CREATE DATABASE l13_base_pro;
EXIT;
```

### 2.2 Konfigurasi Database di `.env`

Edit file `.env` dan sesuaikan konfigurasi database:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=l13_base_pro
DB_USERNAME=root
DB_PASSWORD=
```

### 2.3 Run Database Migrations

```bash
php artisan migrate
```

### 2.4 Seed Database (Opsional)

Untuk mengisi data default (roles, permissions, menus):

```bash
php artisan db:seed
```

---

## 🚀 Tahap 3: Setup Redis

Redis digunakan untuk session storage dan caching. Jika menggunakan Laragon, Redis sudah tersedia.

### 3.1 Pastikan Redis Aktif (Laragon)

1. Buka **Laragon Dashboard**
2. Klik menu **Tools** → **Redis**
3. Pastikan Redis sedang running (status hijau)

Atau melalui command line:

```bash
# Check status Redis
redis-cli ping

# Response yang diharapkan: PONG
```

### 3.2 Konfigurasi Redis di `.env`

```env
SESSION_DRIVER=redis
SESSION_LIFETIME=10

CACHE_STORE=redis

REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_PREFIX=laravel13_
```

### 3.3 Test Koneksi Redis

```bash
php artisan tinker
```

Dalam Tinker shell, jalankan:

```php
>>> Cache::put('test_key', 'test_value', 3600)
>>> Cache::get('test_key')
=> "test_value"
```

---

## 💾 Tahap 4: Setup MinIO

MinIO adalah object storage yang compatible dengan S3. Gunakan untuk menyimpan file uploads.

### 4.1 Install & Start MinIO

#### Opsi A: Menggunakan Laragon

1. Buka **Laragon Dashboard**
2. Klik menu **Tools** → **MinIO**
3. Klik **Start** untuk menjalankan MinIO
4. Default access key: `minioadmin`
5. Default secret key: `minioadmin123`

#### Opsi B: Menggunakan Docker

```bash
docker run -d \
  -p 9000:9000 \
  -p 9001:9001 \
  -e MINIO_ROOT_USER=minioadmin \
  -e MINIO_ROOT_PASSWORD=minioadmin123 \
  minio/minio server /mnt/data --console-address ":9001"
```

#### Opsi C: Manual Installation

Download dari https://min.io/download#/linux dan jalankan:

```bash
# Linux/Mac
./minio server /mnt/data --console-address ":9001"

# Windows
minio.exe server C:\minio\data --console-address ":9001"
```

### 4.2 Buat Bucket

Akses MinIO Console:
- **URL**: http://localhost:9001
- **Username**: minioadmin
- **Password**: minioadmin123

Langkah-langkah:
1. Login ke MinIO Console
2. Klik **Buckets** → **Create Bucket**
3. Nama bucket: `dev`
4. Klik **Create**
5. Klik bucket `dev` → **Edit policy**
6. Pilih **Public** untuk akses publik
7. Klik **Save**

### 4.3 Konfigurasi MinIO di `.env`

Edit file `.env`:

```env
# MinIO Configuration
FILESYSTEM_DISK=minio

# AWS SDK Configuration (untuk S3-compatible)
AWS_ACCESS_KEY_ID=minioadmin
AWS_SECRET_ACCESS_KEY=minioadmin123
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=dev
AWS_ENDPOINT=http://127.0.0.1:9000
AWS_USE_PATH_STYLE_ENDPOINT=true

# MinIO Specific (legacy, tapi tetap ada untuk backward compatibility)
MINIO_BUCKET=dev
MINIO_ENDPOINT=http://127.0.0.1:9000
MINIO_KEY=minioadmin
MINIO_SECRET=minioadmin123
MINIO_REGION=us-east-1
MINIO_URL=http://127.0.0.1:9000/dev
```

### 4.4 Test Koneksi MinIO

```bash
php artisan tinker
```

Dalam Tiniker shell:

```php
>>> Storage::disk('minio')->put('test.txt', 'Hello MinIO')
>>> Storage::disk('minio')->url('test.txt')
=> "http://127.0.0.1:9000/dev/test.txt"
```

---

## 📤 Tahap 5: File Upload dengan MinIO

Sistem ini menyediakan `MinioService` untuk memudahkan upload file.

### 5.1 Penggunaan di Controller

```php
<?php

namespace App\Http\Controllers;

use App\Services\MinioService;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(private MinioService $minio)
    {
    }

    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|max:2048'
        ]);

        // Upload file
        $path = $this->minio->upload(
            $request->file('avatar'),
            'avatars'
        );

        // Save path ke database
        auth()->user()->update(['avatar_path' => $path]);

        return back()->with('success', 'Avatar updated');
    }
}
```

### 5.2 Menampilkan File

Di Blade template:

```blade
<img src="{{ Storage::disk('minio')->url(auth()->user()->avatar_path) }}" 
     alt="Avatar">
```

---

## 🔐 Tahap 6: Setup Keamanan & Environment

### 6.1 Update `.env` untuk Production

```env
# Security
APP_ENV=production
APP_DEBUG=false

# Session
SESSION_ENCRYPT=true
SESSION_PATH=/
SESSION_DOMAIN=.yourdomain.com

# CORS (jika ada API)
APP_URL=https://yourdomain.com

# Mail (update dengan email service)
MAIL_MAILER=smtp
MAIL_SCHEME=tls
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2465
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
```

### 6.2 Clear Cache

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 🛠️ Tahap 7: Asset Compilation

### 7.1 Development Mode

```bash
npm run dev
```

Buka tab terminal baru dan jalankan Laravel server:

```bash
php artisan serve
```

### 7.2 Production Build

```bash
npm run build
```

---

## ✅ Verifikasi Instalasi

Pastikan semua komponen berjalan:

```bash
# Check PHP version
php -v

# Check composer packages
composer show | grep -E "(laravel|redis|minio|aws)"

# Check Laravel configuration
php artisan config:show

# Check database connection
php artisan migrate:status

# Check Redis
redis-cli ping

# Check MinIO (akses browser)
# http://localhost:9001
```

---

## 📝 Database Models & Migrations

Sistem ini sudah memiliki struktur database berikut:

### Users Table
- `id`, `name`, `email`, `password`
- `avatar` - path avatar di MinIO
- `role_id` - foreign key ke roles table
- Timestamps

### Roles Table
- `id`, `name`, `description`
- Timestamps

### Permissions Table
- `id`, `name`, `description`
- Timestamps

### Menus Table
- `id`, `name`, `url`, `icon`
- `order`, `parent_id`
- Timestamps

### Relations
- User has one Role
- Role has many Permissions
- Menu has many Roles

---

## 🐛 Troubleshooting

### 1. **Redis Connection Error**

```
Error: Could not connect to Redis
```

**Solusi:**
```bash
# Check Redis status
redis-cli ping

# Restart Redis (Laragon)
# Klik Tools > Redis > Stop, lalu Start
```

### 2. **MinIO Connection Error**

```
Error: Unable to connect to MinIO endpoint
```

**Solusi:**
```bash
# Check MinIO adalah running
# Akses http://localhost:9001

# Verify endpoint di .env
AWS_ENDPOINT=http://127.0.0.1:9000
```

### 3. **Database Migration Error**

```
Error: SQLSTATE[HY000]
```

**Solusi:**
```bash
# Check database credentials di .env
# Pastikan MySQL sedang running
# Re-run migrations
php artisan migrate:refresh --seed
```

### 4. **Storage Path Error**

```
Error: Storage path not found
```

**Solusi:**
```bash
# Create storage symlink
php artisan storage:link

# Check permissions
chmod -R 775 storage bootstrap/cache
```

---

## 📚 Resources Tambahan

- [Laravel Documentation](https://laravel.com/docs)
- [MinIO Documentation](https://min.io/docs/minio/)
- [Redis Documentation](https://redis.io/docs/)
- [AWS SDK PHP](https://docs.aws.amazon.com/sdk-for-php/)

---

## 🤝 Support

Untuk bantuan atau pertanyaan, silakan hubungi tim development.

---

**Last Updated**: May 11, 2026

**Version**: 1.0.0

---
