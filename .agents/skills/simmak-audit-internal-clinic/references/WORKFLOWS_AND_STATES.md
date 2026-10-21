# Workflows and States — Draft

All status transitions must be enforced server-side through explicit transition rules, not arbitrary client-provided status values. Exact labels and approval authorities require owner confirmation.

## Audit lifecycle
`Draft → Planned/Submitted → Approved → Assigned → In Progress → Review → Finalized → Closed`
- Rejection returns to a defined prior state with reason.
- Finalized/closed audits cannot be silently edited; use controlled amendment/reopen with actor, timestamp, reason, before/after and approval.

## Finding lifecycle
`Draft → Submitted → Reviewed → Confirmed → CAPA Required / Accepted Risk / Not Substantiated → Closed`
- Use only the paths approved by policy. Accepted risk requires documented authority and review date; not substantiated requires rationale.

## CAPA lifecycle
`Draft → Awaiting Approval → In Progress → Evidence Submitted → Awaiting Verification → Effective → Closed`
- Alternative outcome: `Needs Further Action` returns to In Progress with reason and new due date.
- Overdue is preferably a derived indicator, not a destructive status rewrite.
- One finding may have multiple actions, owners, and evidence items.

## Verification and separation of duties
- Verifier must be authorized, assigned, and independent where required.
- Block self-verification if the policy requires independence.
- Record verification method, evidence reviewed, outcome, reviewer, timestamp, and rationale.

## Approval event
Every approval/rejection/reopen/amendment records entity, transition, actor, timestamp, reason, result, and request context. Avoid logging secrets or unnecessary personal data.

## Notifications
Notify only intended recipients; include minimum necessary detail. Link back to an authenticated page rather than attaching sensitive evidence by default.
