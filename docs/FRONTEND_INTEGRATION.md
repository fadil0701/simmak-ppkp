# Integrasi Frontend — React Material Dashboard

Template: `react-dashboard-material-1.0.0/` (Material Dashboard React v1.9.0)

## Tujuan

Mengganti UI Blade sementara dengan React SPA berbasis Material-UI untuk:
- Halaman **Login / Landing**
- **Executive Dashboard**
- **Dashboard Klinik** & **Dashboard Akreditasi**
- Semua modul bisnis (DMS, Audit, CAPA, dll.)

## Struktur Template

```
react-dashboard-material-1.0.0/
├── src/
│   ├── layouts/Admin.js          # Layout utama (sidebar + navbar)
│   ├── views/Dashboard/          # Dashboard default — adaptasi SIMMAK
│   ├── components/
│   │   ├── Sidebar/              # Menu navigasi modul
│   │   ├── Navbars/              # Header + user menu
│   │   ├── Card/                 # Widget statistik
│   │   └── CustomInput/          # Form input — untuk login
│   └── routes.js                 # Definisi route React
└── package.json
```

## Rencana Integrasi (Fase 1b)

| Step | Task | Estimasi |
|------|------|----------|
| 1 | Install Sanctum + API `/api/v1/auth/login` | 2 hari |
| 2 | Buat halaman `/login` (Material card + CustomInput) | 2 hari |
| 3 | Auth guard React (token di localStorage) | 1 hari |
| 4 | Adaptasi `Dashboard.js` → widget SIMMAK (readiness, CAPA, risiko) | 3 hari |
| 5 | Sidebar routes: DMS, Audit, CAPA, Risiko, Akreditasi, Mutu | 2 hari |
| 6 | Proxy API di development (`package.json` proxy) | 0.5 hari |
| 7 | Build production → serve via Nginx atau Laravel `public/spa/` | 1 hari |

## Kustomisasi Branding SIMMAK

| Elemen | Nilai |
|--------|-------|
| Warna primary | `#0d7377` (teal healthcare) |
| Logo | PPKP DKI Jakarta |
| Sidebar background | `sidebar-2.jpg` → ganti asset custom |
| Font | Roboto (default Material-UI) |

## API Contract (Auth)

```
POST /api/v1/auth/login
Body: { "email": "...", "password": "...", "auth_provider": "local" }
Response: { "token": "...", "user": { ... }, "clinics": [...] }

GET /api/v1/dashboard/executive
Headers: Authorization: Bearer {token}
Response: { "readiness": 78, "open_findings": 24, ... }
```

## Menjalankan Development

```bash
# Terminal 1 — Laravel API
php artisan serve

# Terminal 2 — React
cd react-dashboard-material-1.0.0
npm install
npm start
# Buka http://localhost:3000
```

## Catatan Teknis

- Template menggunakan React 16 + Material-UI v4 — pertimbangkan upgrade di Fase 2
- Template tidak memiliki halaman login bawaan — dibuat custom
- CORS harus dikonfigurasi di Laravel untuk `localhost:3000`
- Referensi visual widget: `mockups/dashboard.png`, `mockups/clinic-dashboard.png`
