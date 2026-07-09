# Integrasi Frontend — Blade + Tailwind (Mockup SIMMAK)

UI mengacu pada mockup di folder `mockups/` dan spesifikasi `docs/UIUX.md` — **bukan** template Material Dashboard React.

## Referensi Desain

| Mockup | Halaman |
|--------|---------|
| `mockups/dashboard.png` | Dashboard Eksekutif |
| `mockups/clinic-dashboard.png` | Dashboard Klinik (Fase 1b) |
| `mockups/accreditation-dashboard.png` | Dashboard Akreditasi (Fase 5) |

## Stack Frontend

| Komponen | Teknologi |
|----------|-----------|
| Template | Blade (`resources/views/`) |
| Styling | Tailwind CSS v4 + Vite |
| Interaktivitas | Livewire (modul berikutnya) |
| API mobile | Laravel Sanctum `/api/v1/*` |

## Struktur View

```
resources/views/
├── layouts/simmak.blade.php    # Layout utama (sidebar navy + header)
├── partials/                   # Sidebar, header, footer, icons
├── auth/login.blade.php        # Halaman login
├── dashboard/index.blade.php   # Executive dashboard (mockup)
└── dms/                        # Modul DMS
```

## Menjalankan

```bash
npm install && npm run build   # build CSS (sekali / setelah ubah style)
php artisan serve
# Buka http://127.0.0.1:8000/login
```

Development dengan hot reload CSS:
```bash
npm run dev    # terminal terpisah opsional
php artisan serve
```

## Halaman Tersedia

| Route | Status |
|-------|--------|
| `/login` | ✅ |
| `/dashboard` | ✅ Executive dashboard (mockup) |
| `/dms` | ✅ Daftar & buat dokumen |
| Akreditasi, CAPA, dll. | 🔜 Fase berikutnya |
