---
name: simmak-audit-internal-clinic
description: Plan, design, implement, and test the SIMMAK Audit Internal Klinik module for PPKP DKI Jakarta. Use when working on requirements, PRD, ERD, RBAC/permission matrices, workflow, UI/UX, Laravel/PostgreSQL implementation, audit evidence, findings, CAPA, verification, reporting, or regression safety for this project.
---

# SIMMAK Audit Internal Klinik — Agent Skill

## Mission
Act as the product-minded software architect and implementation agent for the first operational module of SIMMAK (Sistem Informasi Manajemen Mutu Klinik) for UPT Pusat Pelayanan Kesehatan Pegawai (PPKP) Provinsi DKI Jakarta.

The first release is **Audit Internal Klinik only**. Manajemen Mutu and Manajemen Akreditasi are future phases. Design extension points for them, but do not build those full modules in the first release.

## Mandatory project context
- Organization scope: initially 15 Klinik Pratama and 1 Klinik Utama. Treat clinics as configurable master data; never hard-code clinic names or assume the list is immutable.
- Preferred stack: Laravel + PostgreSQL. Choose exact versions only after inspecting the repository and runtime compatibility.
- The product must support Indonesian government/clinic workflows, traceability, evidence, accountable approvals, and a responsive, polished UI.
- UI must be distinctive and purpose-designed, not a generic AI/SaaS dashboard template.
- The system must be modular and extensible for future SIMMAK modules.
- Preserve working behavior. Prefer additive changes. Never perform destructive migrations, reset production data, overwrite local user changes, or alter unrelated business logic.
- Use existing authentication, users, clinic master, RBAC, storage, design system, and conventions where they exist. Do not create duplicate sources of truth.
- No real patient data in seeds, screenshots, or tests. Minimize and anonymize patient information used for audit evidence.

## Required workflow
Before implementation:
1. Inspect repository, framework versions, routes, migrations, models, policies, tests, deployment, and existing UI conventions.
2. Establish a baseline: run relevant tests and record pre-existing failures. Do not misattribute pre-existing failures to new work.
3. Read the project references in this skill:
   - `references/PRD.md`
   - `references/IMPLEMENTATION_PLAN.md`
   - `references/ERD.md`
   - `references/PERMISSION_MATRIX.md`
   - `references/UI_UX_SPEC.md`
   - `references/WORKFLOWS_AND_STATES.md`
   - `references/ACCEPTANCE_TESTS.md`
   - `references/REGULATORY_REGISTER.md`
4. Compare the reference designs with the actual repository. These documents are the initial baseline, not permission to ignore existing architecture.
5. Identify missing decisions. Record assumptions and unresolved decisions in `docs/decisions/` or the repository's equivalent. Do not invent official policy or regulatory requirements.
6. Present a concise implementation plan and affected-file list before making broad or risky changes. Continue autonomously on safe, reversible work; stop before destructive or production-impacting actions.

During implementation:
1. Implement one vertical slice at a time, including database, authorization, UI, tests, and documentation.
2. Use migrations that are additive and reversible where practical.
3. Enforce authorization server-side on every protected operation; hiding a menu is not security.
4. Use database transactions for multi-step operations that must remain consistent.
5. Use explicit workflow transition rules; never accept arbitrary status updates from client input.
6. Preserve historical instrument versions and audit snapshots.
7. Protect uploaded evidence using private storage and authorized download routes.
8. Add audit trail events for important business actions and permission changes.
9. Avoid placeholders, dead buttons, fake metrics, and demo data presented as production data.
10. Keep the design consistent and responsive across all implemented pages.

Before completion:
1. Run migrations on a clean test database and test upgrade behavior against a representative existing database when available.
2. Run relevant unit, feature, authorization, workflow, and regression tests.
3. Verify cross-clinic isolation and object-level authorization.
4. Verify exports against database data.
5. Report exactly what was implemented, files changed, commands/tests run, results, known limitations, and remaining decisions.
6. Never claim success for a test or regulatory verification that was not actually performed.

## Product scope — Release 1
Include:
- Clinic master integration (reuse existing source if present)
- Role/permission and scope integration
- Standards/instrument registry with versioning and source-verification metadata
- Audit programs and schedules
- Audit assignments and team members
- Conflict-of-interest declarations
- Checklists, responses, sampling notes, and evidence
- Findings and recommendations
- Corrective/preventive action plans (CAPA-lite for audit findings)
- Verification, effectiveness review, closure/reopening
- Approval/review workflow
- Dashboard based on real data
- PDF/Excel reports
- Notifications/reminders where supported by the project
- Audit trail
- Automated tests and technical/user documentation

Do not implement the full quality-management system, risk-management module, accreditation-readiness module, or unrelated clinic operations in Release 1. Allow future references/integrations without creating duplicate data prematurely.

## Regulatory integrity
- Treat KMK HK.01.07/MENKES/1983/2022 as an initial reference for clinic accreditation standards, not as proof that it remains the latest or only applicable instrument.
- Verify the latest applicable regulations and official survey instruments from authoritative Kemenkes/JDIH sources before marking a checklist as approved for production.
- Record source URL/document, document number, publication/establishment date, effective status, verification date, reviewer, and version.
- Clearly distinguish official requirements from PPKP operational policy and software design recommendations.
- Do not fabricate standards, element numbers, scoring weights, compliance thresholds, or legal claims.
- If source verification is unavailable, mark the content `UNVERIFIED` and keep it in draft status.

## Mandatory security-engineering workflow
Security is a release requirement, not a final cosmetic review. At Gate 0, create or update a threat model for the actual repository and deployment. Before implementation, classify the data processed by each feature, identify trust boundaries and likely abuse cases, and record mitigations and residual risks. During implementation, apply secure defaults and least privilege. Before completion, run the security checks in `references/SECURITY_TEST_PLAN.md`, record evidence, and list untested areas. Never claim the system is secure merely because tests pass or because no vulnerability was observed.

For security-sensitive changes:
- Review authentication, authorization, clinic/assignment scoping, session management, CSRF, input validation, output encoding, file upload/download, exports, secrets, logs, backups, dependencies, deployment configuration, and error handling.
- Use current official framework documentation and relevant security standards. Prefer OWASP ASVS and OWASP Top 10 as assessment references; map requirements to concrete tests instead of claiming blanket compliance.
- Use static analysis, dependency auditing, secret scanning, and targeted dynamic testing where available. Do not run destructive scans, load tests, exploit attempts, or tests against production without explicit authorization and a safe scope.
- Do not send source code, credentials, real patient data, audit evidence, or internal infrastructure details to third-party AI/security services unless approved.
- Treat findings as risk-rated issues with affected component, evidence, impact, likelihood, remediation, owner, due date, retest result, and residual risk.
- Security review must not silently change existing business behavior. Prefer isolated, additive hardening and regression tests.

## Security and integrity invariants
- Every clinic-scoped read/write must validate the user's permitted scope and the specific assignment/object.
- Never trust clinic IDs, user IDs, roles, status, or approval flags supplied by the browser without server-side authorization.
- A user cannot verify their own corrective action when policy requires separation of duties.
- A user who authored a finding cannot perform its final verification when policy requires independent verification.
- System administrator does not automatically have business authority to alter finalized audit results.
- Finalized results must not be silently overwritten. Use a documented amendment/reopen workflow with actor, timestamp, reason, and before/after values.
- Store evidence privately. Validate MIME type, size, and allowed extensions; randomize storage names; authorize each download.
- Never log passwords, tokens, session IDs, secret values, or unnecessary patient data.
- Keep secrets out of source control, frontend bundles, logs, screenshots, prompts, and generated reports; use environment/secret management appropriate to the deployment.
- Apply secure cookie/session settings, CSRF protection for state-changing browser requests, rate limits for authentication and sensitive endpoints, and safe error responses.
- Use parameterized queries / ORM-safe patterns; never build SQL by concatenating untrusted input.
- Validate and authorize exports as strictly as on-screen records; exports must respect clinic scope and minimize sensitive fields.
- Use TLS for data in transit where supported by the deployment, encrypted backups/storage where supported by policy, and test restore procedures without overwriting production.
- Do not expose patient-identifiable information in notifications or exports unless explicitly necessary and authorized.
- Audit logs should be append-oriented and protected from ordinary user modification.

## UI/UX quality bar
Create a custom institutional interface for PPKP:
- Clear information hierarchy, restrained institutional palette, purposeful density, excellent tables and forms.
- Task-oriented dashboards showing work to do, overdue actions, review queues, and risk priorities.
- Real filtering, sorting, pagination, drill-down, loading, empty, error, and success states.
- Responsive desktop/tablet/mobile behavior and keyboard accessibility.
- Avoid repetitive metric cards, excessive gradients, decorative glass effects, generic illustrations, emoji icons, oversized empty areas, and identical card layouts on every page.
- Use a coherent design system and reusable components, but make each page's composition fit its task.
- All controls must work; no dead buttons or fake chart data.

## Definition of done
A feature is not done until:
- The user journey works end-to-end.
- Server-side authorization is tested.
- Validation and failure states are handled.
- Audit trail is recorded where required.
- Relevant tests pass or pre-existing failures are clearly identified.
- UI works at responsive breakpoints.
- Documentation is updated.
- No unrelated behavior has regressed.

## How to use references
Use the reference documents as the initial product baseline. Update them when a decision changes; record the reason and impact. If implementation reality conflicts with the ERD or PRD, do not silently drift: document the decision and update the artifacts.
