# Panduan Setup GitHub Actions Deployment (Docker)

File workflow: [`.github/workflows/deploy.yml`](file:///Users/macanas/project/web-IECC/.github/workflows/deploy.yml)

Workflow ini akan otomatis terpicu setiap kali Anda melakukan `git push` ke branch `main` (atau bisa di-trigger manual lewat menu Actions di GitHub).

---

## 1. Setup GitHub Secrets

Buka repository GitHub Anda -> **Settings** -> **Secrets and variables** -> **Actions** -> klik **New repository secret**.

Tambahkan secret berikut:

| Nama Secret | Deskripsi / Contoh Nilai |
|---|---|
| `SERVER_HOST` | IP Publik atau Domain VPS Anda (contoh: `123.45.67.89` atau `vps.domain.com`) |
| `SERVER_USER` | Username user SSH di VPS (contoh: `root` atau `ubuntu`) |
| `SERVER_SSH_KEY` | Private SSH Key server (`id_rsa` atau `id_ed25519`) dari laptop/server Anda |
| `SERVER_PORT` | *(Opsional)* Port SSH, default `22` jika tidak diisi |
| `SERVER_APP_DIR` | Path folder project di server (contoh: `/var/www/web-IECC` atau `/home/ubuntu/web-IECC`) |

---

## 2. Setup Awal di Server / VPS (Cukup Sekali)

1. **Clone repository pertama kali di VPS:**
   ```bash
   cd /var/www  # atau direktori yang Anda inginkan
   git clone <URL_REPO_GITHUB_ANDA> web-IECC
   cd web-IECC
   ```

2. **Buat file `.env` di VPS:**
   ```bash
   cp .env.example .env
   nano .env
   ```
   *Isi `APP_KEY`, database credentials, Reverb credentials, dan key lainnya sesuai konfigurasi server.*

3. **Pastikan Docker & Docker Compose terinstall di VPS.**

---

## 3. Cara Kerja Deployment

Setiap kali Anda push ke `main`:
1. GitHub Actions terhubung ke VPS secara aman via SSH.
2. Menjalankan `git reset --hard origin/main` untuk menarik update terbaru.
3. Menjalankan `docker compose up -d --build`.
4. Container otomatis melakukan:
   - Build Vite (Frontend assets)
   - Install Composer dependencies
   - Optimasi cache Laravel (`config:cache`, `route:cache`, `view:cache`)
   - Migrasi Database (`php artisan migrate --force`)
   - Otomatis menyalakan **Nginx**, **PHP-FPM**, **Queue Worker**, dan **Laravel Reverb**.
