# Entity Relationship Diagram — SIMMAK Audit Internal

## 1. Gambaran Umum

ERD berikut menggambarkan hubungan antar entitas dalam modul Audit Internal Klinik. Semua primary key menggunakan UUID. Semua tabel transaksional memiliki `organization_id` untuk multi-tenansi, `created_by`/`updated_by` untuk audit trail aktor, `timestamps`, dan `soft_deletes`.

## 2. Daftar Entitas

### Master Data
| Tabel | Deskripsi |
|-------|-----------|
| `organizations` | Organisasi induk (PPKP DKI Jakarta) |
| `clinics` | Klinik (15 Pratama + 1 Utama), milik organization |
| `users` | Pengguna sistem, terkait organization dan clinics via pivot |
| `user_clinic_scopes` | Pivot user-clinic dengan is_default |

### Regulasi dan Standar
| Tabel | Deskripsi |
|-------|-----------|
| `regulatory_sources` | Sumber regulasi (KMK, dll.) dengan status verifikasi |
| `audit_standards` | Standar audit, terkait regulatory source |
| `instrument_versions` | Versi instrumen (draft/review/approved/active/archived) |
| `standard_sections` | Hierarki area penilaian (self-referencing via parent_id) |
| `standard_criteria` | Kriteria/indikator penilaian per section |

### Audit Program dan Penugasan
| Tabel | Deskripsi |
|-------|-----------|
| `audit_programs` | Program audit tahunan |
| `audit_schedules` | Jadwal audit per program |
| `audit_assignments` | Penugasan audit per klinik |
| `audit_team_members` | Anggota tim auditor + deklarasi independensi/konflik |

### Pelaksanaan Audit
| Tabel | Deskripsi |
|-------|-----------|
| `audit_executions` | Eksekusi audit (terkait instrument version) |
| `checklist_responses` | Jawaban checklist per kriteria |
| `evidence` | Berkas bukti (private storage) |

### Temuan dan CAPA
| Tabel | Deskripsi |
|-------|-----------|
| `audit_findings` | Temuan audit (kode unik per org per tahun) |
| `audit_finding_comments` | Komentar pada temuan |
| `finding_evidence` | Hubungan temuan dengan bukti |
| `capa_cases` | Kasus CAPA (terkait finding) |
| `cap_a_root_cause_analyses` | Analisis akar masalah |
| `capa_actions` | Tindakan korektif/pencegahan |
| `capa_action_evidences` | Bukti pelaksanaan CAPA |
| `capa_verifications` | Verifikasi efektivitas CAPA |

### Persetujuan dan Audit Trail
| Tabel | Deskripsi |
|-------|-----------|
| `audit_approvals` | Persetujuan workflow |
| `approval_events` | Event persetujuan (setuju/tolak/kembalikan) |
| `audit_activities` | Log aktivitas umum |
| `audit_trails` | Audit trail terstruktur (tidak dapat diubah) |
| `audit_notifications` | Notifikasi sistem |

## 3. Relasi Utama (Foreign Key)

```
clinics.organization_id → organizations.id
users.organization_id → organizations.id
user_clinic_scopes.user_id → users.id
user_clinic_scopes.clinic_id → clinics.id

audit_standards.organization_id → organizations.id
audit_standards.source_document_id → regulatory_sources.id

instrument_versions.audit_standard_id → audit_standards.id

standard_sections.instrument_version_id → instrument_versions.id
standard_sections.parent_id → standard_sections.id (self)

standard_criteria.standard_section_id → standard_sections.id

audit_programs.organization_id → organizations.id
audit_programs.fiscal_year_id → fiscal_years.id

audit_assignments.organization_id → organizations.id
audit_assignments.audit_program_id → audit_programs.id
audit_assignments.clinic_id → clinics.id
audit_assignments.lead_auditor_id → users.id

audit_team_members.audit_assignment_id → audit_assignments.id
audit_team_members.user_id → users.id

audit_executions.organization_id → organizations.id
audit_executions.audit_program_id → audit_programs.id
audit_executions.audit_assignment_id → audit_assignments.id
audit_executions.clinic_id → clinics.id
audit_executions.instrument_version_id → instrument_versions.id

checklist_responses.organization_id → organizations.id
checklist_responses.audit_execution_id → audit_executions.id
checklist_responses.standard_criterion_id → standard_criteria.id
checklist_responses.assessed_by → users.id

evidence.organization_id → organizations.id
evidence.clinic_id → clinics.id
evidence.audit_execution_id → audit_executions.id
evidence.uploaded_by → users.id

audit_findings.organization_id → organizations.id
audit_findings.audit_schedule_id → audit_schedules.id
audit_findings.audit_execution_id → audit_executions.id
audit_findings.clinic_id → clinics.id
audit_findings.standard_criterion_id → standard_criteria.id
audit_findings.assigned_to → users.id
audit_findings.owner_user_id → users.id
audit_findings.reviewed_by → users.id

capa_cases.organization_id → organizations.id
capa_cases.clinic_id → clinics.id
capa_cases.owner_id → users.id

audit_approvals (relasi polymorphic ke audit_program, audit_execution, audit_finding, capa_case)
approval_events.audit_approval_id → audit_approvals.id
approval_events.user_id → users.id

audit_trails.organization_id → organizations.id
audit_trails.user_id → users.id
```

## 4. Unique Constraints

| Tabel | Kolom | Keterangan |
|-------|-------|------------|
| `regulatory_sources` | `document_number` | Unique global |
| `audit_standards` | `organization_id, code` | Unique per organisasi |
| `instrument_versions` | `audit_standard_id, version_label` | Unique per standar |
| `standard_sections` | `instrument_version_id, code` | Unique per versi |
| `standard_criteria` | `standard_section_id, code` | Unique per section |
| `audit_programs` | `organization_id, program_code` | Unique per organisasi |
| `audit_assignments` | `organization_id, assignment_code` | Unique per organisasi |
| `audit_executions` | `organization_id, execution_code` | Unique per organisasi |
| `checklist_responses` | `audit_execution_id, standard_criterion_id` | Satu jawaban per kriteria per eksekusi |

## 5. Index

Index dibangun pada semua foreign key, kolom status, dan kolom yang sering difilter (clinic_id, status, response_value, verification_status, effective_from/effective_until).

## 6. Catatan Desain

- Metadata instrumen dipisahkan dari hasil audit aktual (standard_* vs checklist_responses/audit_findings).
- Riwayat versi instrumen tersimpan untuk menjaga data historis tetap dapat dibaca.
- Instrumen yang telah digunakan dalam audit tidak berubah secara retroaktif saat versi baru diterbitkan.
- Setiap audit terhubung dengan versi instrumen yang digunakan saat audit berlangsung via `audit_executions.instrument_version_id`.