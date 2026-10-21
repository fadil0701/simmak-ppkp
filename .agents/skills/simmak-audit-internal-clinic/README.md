# SIMMAK Audit Internal Klinik — AI Agent Skill

Skill pack untuk membantu coding agent merencanakan, mendesain, mengimplementasikan, dan menguji modul Audit Internal Klinik SIMMAK PPKP DKI Jakarta.

## Isi paket
- `SKILL.md` — instruksi utama untuk agent.
- `references/PRD.md` — kebutuhan produk.
- `references/IMPLEMENTATION_PLAN.md` — fase implementasi dan gate.
- `references/ERD.md` — model data logis awal.
- `references/PERMISSION_MATRIX.md` — matriks akses awal.
- `references/UI_UX_SPEC.md` — arahan desain UI/UX.
- `references/WORKFLOWS_AND_STATES.md` — status dan alur bisnis.
- `references/ACCEPTANCE_TESTS.md` — checklist penerimaan.
- `references/REGULATORY_REGISTER.md` — protokol verifikasi regulasi.
- `references/DECISION_LOG_TEMPLATE.md` — template pencatatan keputusan.
- `references/SECURITY_BASELINE.md` — baseline keamanan aplikasi, infrastruktur, dan perlindungan data.
- `references/SECURITY_THREAT_MODEL.md` — template threat modeling, trust boundaries, abuse cases, dan mitigasi.
- `references/SECURITY_TEST_PLAN.md` — pengujian keamanan dan bukti penerimaan.
- `references/SECURITY_REVIEW_AGENT_PROMPT.md` — prompt khusus untuk agen security review read-only.

## Cara memasang
### Opsi A — Repository-level skill
1. Ekstrak folder `simmak-audit-internal-agent-skill-security`.
2. Salin folder tersebut ke lokasi skill yang didukung coding agent/repository.
3. Untuk pola Agent Skills yang memakai folder `.agents/skills/`, tempatkan sebagai:
   `.agents/skills/simmak-audit-internal-clinic/`
4. Pastikan `SKILL.md` tetap berada di root folder skill dan `references/` berada satu tingkat di bawahnya.
5. Buka repository SIMMAK dan minta agent menggunakan skill `simmak-audit-internal-clinic`.

### Opsi B — Cursor atau OpenCode
Lokasi penemuan skill dapat berbeda menurut versi dan konfigurasi agent. Gunakan struktur Agent Skills yang didukung versi yang dipakai; jika perlu, salin `SKILL.md` menjadi aturan proyek yang dibaca otomatis dan simpan seluruh file `references/` di repository.
Jangan menganggap skill aktif sebelum agent mengonfirmasi bahwa file instruksi telah dibaca.

## Prompt aktivasi singkat
“Gunakan skill `simmak-audit-internal-clinic`. Mulai dari Gate 0: periksa repository dan lingkungan, catat Git status, inventarisasi Laravel/PostgreSQL/RBAC/clinic master, jalankan baseline tests tanpa reset database, lalu bandingkan kondisi aktual dengan PRD, ERD, permission matrix, workflow, dan UI/UX spec. Buat laporan gap dan rencana implementasi yang aman. Sertakan threat model awal, klasifikasi data, security gap assessment, dan rencana pengujian berdasarkan `references/SECURITY_BASELINE.md`, `references/SECURITY_THREAT_MODEL.md`, serta `references/SECURITY_TEST_PLAN.md`. Jangan melakukan perubahan destruktif atau menyentuh business logic yang tidak relevan.”

## Prompt untuk pemeriksaan keamanan
“Gunakan `references/SECURITY_REVIEW_AGENT_PROMPT.md` bersama skill `simmak-audit-internal-clinic`. Mulai dengan read-only security gap assessment. Petakan klasifikasi data, data flow, trust boundaries, dan ancaman lintas-klinik. Periksa RBAC/object-level authorization, workflow, bukti audit, ekspor, kredensial, log, backup, dependensi, dan konfigurasi deployment berdasarkan security baseline dan test plan. Jangan mengubah file atau menjalankan tes invasif sampai baseline dan cakupan dilaporkan.”

## Catatan
Dokumen adalah baseline rancangan, bukan pengesahan kebijakan internal PPKP atau verifikasi final regulasi Kemenkes. Konfirmasi kewenangan, workflow, sumber regulasi, instrumen, retensi, dan kebijakan risiko sebelum produksi.
