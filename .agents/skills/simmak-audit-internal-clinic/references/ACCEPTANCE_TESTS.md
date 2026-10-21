# Acceptance Tests — Release 1

## Functional
- [ ] Create program and audit schedule using configurable clinic master.
- [ ] Assign auditors and scope; conflict-of-interest declaration is captured.
- [ ] Run checklist against a specific instrument version and preserve that version.
- [ ] Add response, sampling note, evidence, finding, and recommendation.
- [ ] Create multiple CAPA actions for one finding, assign owner and due date, upload evidence.
- [ ] Verify effectiveness, request further action, close, and controlled reopen/amendment.
- [ ] Dashboard/report values derive from actual data and filters respect scope.
- [ ] PDF/Excel export has expected data, scope, date and authorized columns.

## Authorization and integrity
- [ ] Every protected action checks server-side permission and object scope.
- [ ] Cross-clinic access to records/evidence/download/export is denied.
- [ ] Client cannot forge role, clinic, status, approver, or finalization metadata.
- [ ] Self-approval/self-verification is blocked where required.
- [ ] Finalized records cannot be silently overwritten; amendment trail is complete.
- [ ] Audit trail captures critical actions without secrets or excess sensitive data.

## Security/privacy
- [ ] Run applicable tests from `SECURITY_TEST_PLAN.md` and record untested controls.
- [ ] Evidence is private and downloads are authorized.
- [ ] Sensitive data is minimized/redacted in UI, notification, logs and exports.
- [ ] Upload validation, export formula handling, session/CSRF/rate-limit and error redaction reviewed.
- [ ] Secrets/dependency scans performed if available; limitations documented.

## Regression/release
- [ ] Baseline tests recorded before implementation.
- [ ] Migrations tested on isolated clean DB and representative upgrade copy where available.
- [ ] No reset/destructive operation or unrelated business logic changes.
- [ ] Responsive UI, keyboard navigation, empty/error/loading states checked.
- [ ] Git diff reviewed; release notes, rollback and unresolved decisions documented.
