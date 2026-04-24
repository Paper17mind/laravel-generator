# CodeGen v2 | Premium AI-Powered Project Generator

**CodeGen v2** adalah alat bantu pengembangan (developer tool) modern yang dirancang untuk mempercepat pembuatan struktur project Full-Stack. Sekarang dilengkapi dengan **Architect AI** yang membantu kamu merancang database secara instan.

## 🚀 Fitur Utama

- **Multi-Framework Backend**: Mendukung pengerjaan project menggunakan [Laravel](https://laravel.com), [AdonisJS](https://adonisjs.com), [Express.js](https://expressjs.com), dan [Go (Fiber/Gin)](https://go.dev).
- **Modern Frontend**: Mendukung [Vue 3](https://vuejs.org), [React](https://react.dev), dan [Svelte](https://svelte.dev).
- **Architect AI**: AI Agent terintegrasi (menggunakan Groq API) yang dapat memberikan saran skema tabel dan **langsung menerapkannya** ke project kamu.
- **Visual ERD Editor**: Kelola tabel dan relasi secara visual dengan diagram interaktif.
- **Code Preview**: Lihat hasil generate kode (Controller, Model, Migration) secara real-time sebelum di-save.

## 🤖 Mengaktifkan AI Assistant

Untuk menggunakan fitur AI, kamu perlu mendaftarkan API Key dari [Groq Cloud](https://console.groq.com/):

1. Buka file `dev/.env`.
2. Masukkan API Key kamu:
   ```env
   GROQ_API_KEY=gsk_xxxxxxx...
   ```
3. Klik ikon bot di pojok kanan bawah aplikasi untuk mulai bertanya.

## 📂 Struktur Project yang Dihasilkan

CodeGen v2 akan secara otomatis membuatkan:
- **Backend**: Controllers, Models, Migrations, dan Routes.
- **Frontend**: Components (Vue/React/Svelte) yang sudah terhubung ke API backend.
- **Infrastructure**: `docker-compose.yml` dan `Dockerfile` untuk deployment instan.

## 🛠️ Cara Penggunaan

1. **Buat Project**: Tambahkan project baru melalui sidebar.
2. **Rancang Tabel**: Gunakan **Visual ERD** atau minta bantuan **Architect AI**.
3. **Generate**: Klik tombol **Generate Project** di sidebar.
4. **Output**: File hasil generate dapat ditemukan di folder `public/{project_id}`.

---

*Dibuat dengan ❤️ untuk mempercepat workflow developer.*
