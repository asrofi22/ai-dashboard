<div align="center">

# 🤖 AI Dashboard — Data Intelligence Platform

**Platform cerdas berbasis AI untuk manajemen data, ETL pipeline, dan analitik bisnis**

[![Laravel](https://img.shields.io/badge/Laravel-13.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![Livewire](https://img.shields.io/badge/Livewire-4.x-FB70A9?style=for-the-badge&logo=livewire&logoColor=white)](https://livewire.laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![Tailwind CSS](https://img.shields.io/badge/TailwindCSS-4.x-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)](https://tailwindcss.com)
[![Gemini AI](https://img.shields.io/badge/Gemini_AI-2.5_Flash-4285F4?style=for-the-badge&logo=google&logoColor=white)](https://deepmind.google/technologies/gemini/)
[![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)](LICENSE)

</div>

---

## 📖 Deskripsi Proyek

**AI Dashboard** adalah platform komprehensif untuk tim Data Engineering dan Data Analyst yang mengintegrasikan kecerdasan buatan (Google Gemini 2.5 Flash) ke dalam seluruh alur kerja data — mulai dari deteksi duplikat, eksplorasi data warehouse, pembuatan pipeline ETL, hingga analitik konversasional berbasis bahasa alami.

Dibangun dengan **Laravel 13 + Livewire 4** untuk pengalaman reaktif tanpa perlu JavaScript yang kompleks, dengan antarmuka yang modern dan responsif menggunakan Tailwind CSS 4.

---

## ✨ Fitur Utama

### 🔍 **Manajemen Kualitas Data**
- **Upload Manager** — Unggah file Excel/CSV dan proses data secara otomatis
- **Duplikat Kandidat** — Deteksi dan review duplikat data menggunakan algoritma fuzzy matching + validasi AI
- **Dashboard Analitik** — Ringkasan statistik kualitas data secara real-time dengan Chart.js

### 🧠 **AI-Powered Features (Google Gemini 2.5 Flash)**
- **SQL Assistant** — Terjemahkan bahasa alami ke query SQL PostgreSQL secara instan
- **Conversational Analytics** — Analitik berbasis percakapan menggunakan AI
- **ETL Failure Analysis** — Analisis otomatis penyebab kegagalan pipeline ETL
- **Data Quality Recommendations** — Rekomendasi perbaikan kualitas data berbasis AI
- **Table Catalog Generation** — Dokumentasi otomatis untuk tabel data warehouse
- **Pipeline AI Generator** — Generate pipeline ETL lengkap dari deskripsi bahasa alami

### 🏗️ **ETL Studio (Pipeline Builder)**
- **Studio Connections** — Manajemen koneksi ke berbagai sumber data (PostgreSQL, MySQL, dll.)
- **Studio Pipelines** — Visual pipeline builder dengan drag-and-drop step Pentaho-style
- **Studio Runs** — Monitoring eksekusi pipeline secara real-time
- **Studio Schedules** — Penjadwalan otomatis pipeline dengan cron expression
- **Studio Assistant** — AI assistant untuk membantu desain pipeline ETL
- **Airflow DAG Generator** — Generate Apache Airflow DAG dari konfigurasi pipeline

### 🗄️ **Data Warehouse**
- **Warehouse Explorer** — Jelajahi struktur tabel dan skema data warehouse
- **ETL Monitoring** — Monitor status job ETL dan analisis kegagalan dengan AI

---

## 🛠️ Tech Stack

| Layer | Teknologi |
|---|---|
| **Backend Framework** | Laravel 13.x (PHP 8.3+) |
| **Frontend Reactivity** | Livewire 4.x (SSR-first, no Vue/React) |
| **CSS Framework** | Tailwind CSS 4.x |
| **Build Tool** | Vite 8.x |
| **AI Engine** | Google Gemini 2.5 Flash API |
| **Database Default** | SQLite (konfigurasi MySQL/PostgreSQL tersedia) |
| **Job Queue** | Laravel Queue (database driver) |
| **Export** | Maatwebsite/Laravel Excel |
| **Charts** | Chart.js 4.x |
| **Workflow Engine** | Apache Airflow DAG (export feature) |

---

## 📋 Persyaratan Sistem

- **PHP** >= 8.3
- **Composer** >= 2.x
- **Node.js** >= 18.x & **npm** >= 9.x
- **SQLite** (default) atau **PostgreSQL** / **MySQL**
- **Google Gemini API Key** (untuk fitur AI)

---

## 🚀 Instalasi & Setup

### 1. Clone Repositori

```bash
git clone https://github.com/asrofi22/ai-dashboard.git
cd ai-dashboard
```

### 2. Install Dependensi PHP

```bash
composer install
```

### 3. Konfigurasi Environment

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Konfigurasi File `.env`

Buka file `.env` dan sesuaikan konfigurasi berikut:

```env
APP_NAME="AI Dashboard"
APP_URL=http://localhost:8000

# Database (SQLite default, atau ganti ke PostgreSQL/MySQL)
DB_CONNECTION=sqlite
# DB_HOST=127.0.0.1
# DB_PORT=5432
# DB_DATABASE=ai_dashboard
# DB_USERNAME=your_username
# DB_PASSWORD=your_password

# Google Gemini AI API Key (WAJIB untuk fitur AI)
GEMINI_API_KEY=your_gemini_api_key_here
```

> 💡 **Dapatkan Gemini API Key** gratis di [Google AI Studio](https://aistudio.google.com/app/apikey)

### 5. Jalankan Migrasi Database

```bash
php artisan migrate
```

### 6. Install Dependensi Node.js

```bash
npm install
```

### 7. Setup Otomatis (Alternatif Langkah 2-6)

Gunakan script setup yang sudah dikonfigurasi:

```bash
composer setup
```

---

## ▶️ Menjalankan Aplikasi

### Mode Development (Rekomendasi)

Jalankan semua service sekaligus (server, queue, logs, vite):

```bash
composer dev
```

Atau jalankan secara terpisah:

```bash
# Terminal 1: Laravel Server
php artisan serve

# Terminal 2: Vite Dev Server
npm run dev

# Terminal 3: Queue Worker (untuk background jobs)
php artisan queue:listen --tries=1 --timeout=0
```

Aplikasi akan berjalan di: **http://localhost:8000**

### Build Production

```bash
npm run build
```

---

## 🗺️ Navigasi Aplikasi

| URL | Halaman | Deskripsi |
|---|---|---|
| `/` | Dashboard | Ringkasan KPI & statistik kualitas data |
| `/upload` | Upload Manager | Upload & proses file Excel/CSV |
| `/duplicates` | Duplikat Kandidat | Review & validasi duplikat data |
| `/warehouse` | Warehouse Explorer | Eksplorasi tabel data warehouse |
| `/sql-assistant` | SQL Assistant | Query builder berbasis AI |
| `/etl-monitoring` | ETL Monitoring | Monitor job ETL & analisis error |
| `/analytics` | Analitik Konversasional | Chat analytics berbasis AI |
| `/studio/connections` | Studio Connections | Manajemen koneksi database |
| `/studio/pipelines` | Studio Pipelines | Visual ETL pipeline builder |
| `/studio/runs` | Studio Runs | History & status eksekusi pipeline |
| `/studio/schedules` | Studio Schedules | Penjadwalan pipeline otomatis |
| `/studio/assistant` | Studio Assistant | AI assistant untuk desain pipeline |
| `/studio/monitoring` | Studio Monitoring | Dashboard monitoring studio |

---

## 🏛️ Arsitektur Proyek

```
ai-dashboard/
├── app/
│   ├── Actions/              # Single-action classes
│   ├── DTOs/                 # Data Transfer Objects
│   ├── Http/
│   │   └── Controllers/      # HTTP Controllers (AirflowDagController, dll.)
│   ├── Jobs/                 # Background jobs (Laravel Queue)
│   ├── Livewire/             # Livewire components (13 komponen)
│   │   ├── DashboardAnalytics.php
│   │   ├── StudioPipelines.php      # Pipeline builder (core)
│   │   ├── SqlAssistant.php
│   │   ├── ConversationalAnalytics.php
│   │   └── ...
│   ├── Models/               # Eloquent models (18 model)
│   ├── Repositories/         # Repository pattern
│   └── Services/             # Business logic services
│       ├── GeminiService.php         # Core AI service (Gemini 2.5 Flash)
│       ├── PipelineExecutorService.php
│       ├── AirflowDagGeneratorService.php
│       ├── DuplicateDetectionService.php
│       ├── ImportService.php
│       └── CleaningService.php
├── database/
│   ├── migrations/           # 13 migrasi database
│   └── seeders/
├── resources/
│   ├── css/                  # Tailwind CSS
│   ├── js/                   # JavaScript & Chart.js
│   └── views/
│       ├── welcome.blade.php         # Layout utama (SPA-style)
│       └── livewire/                 # 13 Livewire views
├── routes/
│   └── web.php               # Route definitions
└── backend/                  # Backend services (Python, opsional)
```

---

## 🤖 Integrasi AI (Google Gemini)

`GeminiService` adalah jantung dari platform ini, menyediakan berbagai kemampuan AI:

| Method | Fungsi |
|---|---|
| `validateDuplicate()` | Validasi semantik apakah dua proyek merupakan duplikat |
| `translateNaturalQueryToSql()` | Terjemahkan bahasa alami → SQL PostgreSQL |
| `explainSqlQuery()` | Jelaskan SQL query dalam bahasa sederhana |
| `analyzeEtlFailure()` | Analisis root cause kegagalan ETL pipeline |
| `generateDqRecommendations()` | Rekomendasi perbaikan kualitas data |
| `generateEtlPipeline()` | Generate ETL pipeline + Python script dari prompt |
| `generateEtlStudioPipeline()` | Generate studio pipeline dengan analisis schema context |
| `generateTableCatalog()` | Dokumentasi otomatis tabel data warehouse |

> ⚡ Semua method memiliki **fallback logic** — jika API tidak tersedia atau rate limited, sistem tetap berfungsi dengan respons default yang relevan.

---

## 🗃️ Database Schema

Skema database mencakup tabel-tabel berikut:

- **`imported_projects`** — Data proyek yang diimport
- **`import_logs`** — Log proses import file
- **`duplicate_candidates`** — Kandidat duplikat yang terdeteksi
- **`duplicate_review_history`** — History review duplikat
- **`ai_validation_logs`** — Log validasi AI
- **`data_quality_recommendations`** — Rekomendasi kualitas data
- **`warehouse_tables` & `warehouse_columns`** — Katalog data warehouse
- **`etl_connections`** — Koneksi database ETL
- **`etl_pipelines` & `etl_job_runs`** — Pipeline & history eksekusi
- **`studio_pipelines`** — Konfigurasi visual pipeline builder
- **`studio_pipeline_runs`** — History run studio pipeline
- **`studio_pipeline_versions`** — Versioning pipeline
- **`studio_reusable_templates`** — Template pipeline yang bisa digunakan ulang
- **`source_connections`** — Konfigurasi koneksi sumber data
- **`query_history`** — History query SQL assistant

---

## 🧪 Testing

```bash
# Jalankan semua test
composer test

# Atau langsung dengan PHPUnit
php artisan test
```

---

## 📦 Perintah Artisan Berguna

```bash
# Bersihkan semua cache
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear

# Jalankan ulang migrasi dengan fresh data
php artisan migrate:fresh --seed

# Monitor log secara real-time (Pail)
php artisan pail

# Generate DAG Airflow dari pipeline
php artisan tinker
```

---

## 🔧 Konfigurasi Tambahan

### Konfigurasi Gemini API

Tambahkan konfigurasi di `config/services.php`:

```php
'gemini' => [
    'key' => env('GEMINI_API_KEY'),
],
```

### Menggunakan PostgreSQL / MySQL

Update `.env`:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=ai_dashboard
DB_USERNAME=postgres
DB_PASSWORD=secret
```

Kemudian jalankan:

```bash
php artisan migrate
```

---

## 🤝 Kontribusi

1. Fork repositori ini
2. Buat branch fitur baru: `git checkout -b feature/nama-fitur`
3. Commit perubahan: `git commit -m 'feat: tambah fitur X'`
4. Push ke branch: `git push origin feature/nama-fitur`
5. Buat Pull Request

---

## 📄 Lisensi

Proyek ini dilisensikan di bawah [MIT License](LICENSE).

---

<div align="center">

**Dibuat dengan ❤️ menggunakan Laravel + Livewire + Google Gemini AI**

</div>
