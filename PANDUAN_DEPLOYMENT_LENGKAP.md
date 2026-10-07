# 🚀 Panduan Deployment Lengkap Laravel (Docker + GitHub Actions + Auto .env)

Dengan konfigurasi ini, **Anda TIDAK PERLU membuat atau mengedit `.env` di server secara manual**. Semua konfigurasi `.env` disimpan aman di **GitHub Actions Secrets** dan akan otomatis ditulis ke server setiap kali deploy!

---

## 📑 Daftar Isi
1. [Persiapan Server VPS (Sekali Saja)](#1-persiapan-server-vps-sekali-saja)
2. [Setup SSH Key untuk GitHub Actions](#2-setup-ssh-key-untuk-github-actions)
3. [Setup GitHub Secrets (Termasuk Isi .env)](#3-setup-github-secrets-termasuk-isi-env)
4. [Jalankan Deployment (Otomatis)](#4-jalankan-deployment-otomatis)
5. [Konfigurasi Domain & SSL Let's Encrypt (HTTPS/WSS)](#5-konfigurasi-domain--ssl-lets-encrypt-httpswss)
6. [Perintah Maintenance & Monitoring](#6-perintah-maintenance--monitoring)

---

## 1. Persiapan Server VPS (Sekali Saja)

Login ke VPS Anda via SSH:
```bash
ssh root@IP_SERVER_ANDA
```

### A. Install Docker & Docker Compose
```bash
# Update sistem
sudo apt update && sudo apt upgrade -y

# Install Docker otomatis
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Verifikasi
docker --version
docker compose version
```

### B. Konfigurasi Firewall
```bash
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw allow 8080/tcp
sudo ufw enable
```

---

## 2. Setup SSH Key untuk GitHub Actions

Jalankan di server VPS:

1. **Generate SSH Key:**
   ```bash
   ssh-keygen -t ed25519 -C "github-actions-deploy"
   ```
   *(Tekan `Enter` 3x sampai selesai)*

2. **Daftarkan Public Key:**
   ```bash
   cat ~/.ssh/id_ed25519.pub >> ~/.ssh/authorized_keys
   chmod 600 ~/.ssh/authorized_keys
   ```

3. **Tampilkan Private Key untuk disalin ke GitHub:**
   ```bash
   cat ~/.ssh/id_ed25519
   ```
   *(Salin semua teks dari `-----BEGIN OPENSSH PRIVATE KEY-----` sampai `-----END OPENSSH PRIVATE KEY-----`)*

---

## 3. Setup GitHub Secrets (Termasuk Isi `.env`)

Buka repository Anda di **GitHub** -> **Settings** -> **Secrets and variables** -> **Actions** -> klik **New repository secret**.

Tambahkan 5 Secret berikut:

### 1. `SERVER_HOST`
> IP Public VPS Anda (contoh: `103.12.34.56` atau domain server)

### 2. `SERVER_USER`
> Username SSH (contoh: `root` atau `ubuntu`)

### 3. `SERVER_SSH_KEY`
> Isi teks Private Key yang disalin pada **Langkah 2** (`cat ~/.ssh/id_ed25519`)

### 4. `SERVER_APP_DIR`
> Path folder di server, contoh: `/var/www/web-IECC`

### 5. `LARAVEL_ENV` 🌟 *(Isi File .env Anda)*
> Salin seluruh isi `.env` production Anda langsung ke secret ini:

```dotenv
APP_NAME=IECC
APP_ENV=production
APP_KEY=base64:MASUKKAN_APP_KEY_ANDA_DISINI
APP_DEBUG=false
APP_URL=https://domain-anda.com

# Database Docker
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=web_iecc
DB_USERNAME=postgres
DB_PASSWORD=password_db_rahasia

# Redis & Queue
QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=redis
REDIS_HOST=redis
REDIS_PORT=6379

# Laravel Reverb (WebSocket)
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=100001
REVERB_APP_KEY=iecc-reverb-key
REVERB_APP_SECRET=iecc-reverb-secret
REVERB_HOST=domain-anda.com
REVERB_PORT=443
REVERB_SCHEME=https

REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080

# Layanan Tambahan (Gemini, Google OAuth, S3/MinIO, dll)
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=https://domain-anda.com/auth/google/callback
GEMINI_API_KEY=
```

---

## 4. Jalankan Deployment (Otomatis)

Setelah semua secret diisi:
1. Setiap kali Anda melakukan `git push origin main`, GitHub Actions akan otomatis:
   - Terhubung ke server.
   - Mengambil kode terbaru.
   - **Menuliskan file `.env` otomatis dari secret `LARAVEL_ENV`**.
   - Build dan jalankan Docker (`docker compose up -d --build`).
   - Menjalankan migrasi database, compile asset, dan menyalakan **Nginx**, **PHP-FPM**, **Queue Worker**, dan **Laravel Reverb**.

2. Anda juga bisa men-trigger deploy secara manual:
   - Buka tab **Actions** di GitHub -> Pilih **Build & Deploy Production** -> Klik tombol **Run workflow**.

---

## 5. Konfigurasi Domain & SSL Let's Encrypt (HTTPS/WSS)

Agar web dan websocket bisa diakses via `https://` dan `wss://`:

1. Di server VPS host, install Nginx & Certbot:
   ```bash
   sudo apt install -y nginx certbot python3-certbot-nginx
   ```

2. Buat konfigurasi `/etc/nginx/sites-available/iecc`:
   ```nginx
   server {
       server_name domain-anda.com;

       location / {
           proxy_pass http://127.0.0.1:80;
           proxy_set_header Host $host;
           proxy_set_header X-Real-IP $remote_addr;
           proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
           proxy_set_header X-Forwarded-Proto $scheme;
       }

       # Reverse Proxy untuk WebSocket Reverb
       location /app {
           proxy_pass http://127.0.0.1:8080;
           proxy_http_version 1.1;
           proxy_set_header Upgrade $http_upgrade;
           proxy_set_header Connection "Upgrade";
           proxy_set_header Host $host;
           proxy_read_timeout 60;
           proxy_connect_timeout 60;
       }
   }
   ```

3. Aktifkan dan pasang SSL gratis:
   ```bash
   sudo ln -s /etc/nginx/sites-available/iecc /etc/nginx/sites-enabled/
   sudo nginx -t
   sudo systemctl reload nginx
   sudo certbot --nginx -d domain-anda.com
   ```

---

## 6. Perintah Maintenance & Monitoring

Jalankan perintah berikut di folder `/var/www/web-IECC` pada server jika ingin memantau status aplikasi:

- **Cek Status Service:**
  ```bash
  docker compose ps
  ```

- **Melihat Live Log (Web, Queue, & Reverb):**
  ```bash
  docker compose logs -f app
  ```

- **Masuk ke Shell Container:**
  ```bash
  docker compose exec app sh
  ```

- **Jalankan Perintah Artisan Manual:**
  ```bash
  docker compose exec app php artisan migrate --status
  ```
