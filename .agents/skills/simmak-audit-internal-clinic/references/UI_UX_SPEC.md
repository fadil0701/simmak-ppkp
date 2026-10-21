# UI/UX Specification

## Design direction
Institutional, trustworthy, distinctive, purpose-built for PPKP; task-oriented, compact but readable. Do not use generic AI/SaaS template composition, repeated metric cards, excessive gradients/glass effects, decorative illustrations, or fake data.

## Information architecture
- Ringkasan kerja / dashboard.
- Program & Jadwal Audit.
- Penugasan & Tim Auditor.
- Pelaksanaan Audit / Instrumen.
- Temuan & Rekomendasi.
- CAPA & Verifikasi.
- Instrumen & Versi Standar.
- Laporan & Ekspor.
- Administrasi/konfigurasi only for authorized users.

## Page composition
- Dashboard: tasks due, overdue CAPA, pending reviews, audits by status, risk priority, recent activity; only real database values.
- Audit detail: clear stepper/status timeline, scope and assigned team, checklist progress, findings, evidence, approvals.
- Finding detail: criteria, evidence, risk/priority, root-cause analysis, CAPA actions, responsible person and due date.
- CAPA workbench: actionable list with clinic, owner, due date, days overdue, verification status, filters.
- Instrument registry: source, version, effective date, verification status, previous versions; never silently replace used versions.
- Reports: visible scope, filters, generated timestamp, data classification, authorized download.

## Interaction and accessibility
- Responsive desktop/tablet/mobile; keyboard navigation and visible focus.
- Semantic labels, accessible contrast, status not conveyed by color alone.
- Real search/filter/sort/pagination, validation, loading/empty/error/success states.
- Confirm high-impact actions; show reason fields for reopen/amendment/rejection.
- Clear workflow status and next action; prevent dead buttons and misleading success states.
- Use concise Indonesian language consistent with government workflows.

## Security UX
- Hide unauthorized actions for usability but enforce every rule server-side.
- Do not reveal cross-clinic object existence through different errors.
- Warn before exporting sensitive data and show scope/fields included.
- Do not show raw stack traces, secrets, or patient data in notifications.
- Evidence preview/download is controlled and records access where required.
