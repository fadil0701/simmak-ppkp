# Permission Matrix — Initial Draft

Actual role names must map to existing RBAC. A role alone is insufficient: combine permission + clinic scope + assignment scope + workflow state + separation-of-duties.

| Capability | System admin | Audit manager | Auditor/team lead | Clinic PIC | Verifier | Leadership/read-only |
|---|---|---|---|---|---|---|
| Manage technical configuration | Yes, technical only | No | No | No | No | No |
| Manage audit program | Configurable | Yes | View assigned | View own clinic | View assigned | View authorized summary |
| Assign audit team | Technical only if explicitly authorized | Yes | No | No | No | View |
| Execute checklist | No by default | If assigned | Assigned audits only | Respond to assigned requests only | Review as assigned | No |
| View finding | Authorized scope only | Program scope | Assigned audit | Own clinic finding | Assigned verification | Summary/authorized scope |
| Propose finding | No by default | If assigned | Assigned audit | No | No | No |
| Create/update CAPA | No by default | Oversight | No unless separately assigned | Own clinic + assigned CAPA | No | View summary |
| Verify CAPA effectiveness | No by default | If formally assigned and independent | Only if independent | No self-verification | Assigned independent verification | View result |
| Approve/finalize | No automatic business authority | According to workflow | Only delegated authority | No | According to workflow | Only explicit permission |
| Export reports | No automatic access | Authorized scope | Assigned scope | Own clinic scope | Assigned scope | Authorized summary |
| Change finalized result | No silent edits | Controlled amendment only | No | No | No | No |
| Manage users/roles | Existing policy only | No | No | No | No | No |

## Mandatory authorization tests
- Cross-clinic IDOR/BOLA denied across show/update/delete/download/export/search/API.
- Changing request `clinic_id`, role, status, approver, `created_by` or privileged flags cannot grant access.
- Users with multiple roles receive only the union of explicit permissions, constrained by scopes and workflow; no implicit global scope.
- Admin technical access is separated from business approval authority.
- Self-approval/self-verification is blocked where separation of duties applies.
- Permission changes and critical access decisions are audited.
