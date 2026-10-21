<?php

/**
 * Verification script for Wave 2 changes.
 * Run: docker compose exec app php scripts/verify_wave2.php
 */

use App\Models\AuditFinding;
use App\Models\ChecklistResponse;
use App\Models\ChecklistResponseRevision;
use App\Models\InstrumentVersion;
use App\Services\AuditExecutionService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$checks = [];

// 1. checklist_response_revisions table
$checks['checklist_response_revisions table'] = Schema::hasTable('checklist_response_revisions')
    ? 'OK' : 'MISSING';

// 2. audit_findings escalation columns
foreach (['is_escalated', 'escalated_at', 'escalated_by', 'escalation_reason'] as $col) {
    $checks["audit_findings.{$col}"] = Schema::hasColumn('audit_findings', $col) ? 'OK' : 'MISSING';
}

// 3. audit.finding.escalate permission
$checks['audit.finding.escalate permission'] = Permission::where('name', 'audit.finding.escalate')->exists()
    ? 'OK' : 'MISSING';

// 4. All audit.finding.* permissions
$findingPerms = Permission::where('name', 'like', 'audit.finding.%')->pluck('name')->toArray();
$checks['audit.finding.* permissions'] = implode(', ', $findingPerms);

// 5. Roles with audit.finding.escalate
$roles = Role::whereHas('permissions', function ($q) {
    $q->where('name', 'audit.finding.escalate');
})->pluck('name')->toArray();
$checks['Roles with audit.finding.escalate'] = implode(', ', $roles) ?: 'NONE';

// 6. Model methods
$iv = new InstrumentVersion;
$checks['InstrumentVersion::isLocked()'] = method_exists($iv, 'isLocked') ? 'OK' : 'MISSING';
$checks['InstrumentVersion::executions()'] = method_exists($iv, 'executions') ? 'OK' : 'MISSING';

$af = new AuditFinding;
$checks['AuditFinding::isEscalated()'] = method_exists($af, 'isEscalated') ? 'OK' : 'MISSING';
$checks['AuditFinding::escalatedBy()'] = method_exists($af, 'escalatedBy') ? 'OK' : 'MISSING';
$checks['AuditFinding::scopeCriticalOpen()'] = method_exists($af, 'scopeCriticalOpen') ? 'OK' : 'MISSING';

$cr = new ChecklistResponse;
$checks['ChecklistResponse::revisions()'] = method_exists($cr, 'revisions') ? 'OK' : 'MISSING';

$checks['ChecklistResponseRevision model'] = class_exists(ChecklistResponseRevision::class) ? 'OK' : 'MISSING';

$svc = new AuditExecutionService;
$checks['AuditExecutionService::validateCompleteness()'] = method_exists($svc, 'validateCompleteness') ? 'OK' : 'MISSING';
$checks['AuditExecutionService::getIncompleteCriteria()'] = method_exists($svc, 'getIncompleteCriteria') ? 'OK' : 'MISSING';

// 7. Routes
$routes = app('router')->getRoutes();
$routeNames = [];
foreach ($routes as $route) {
    $name = $route->getName();
    if ($name && in_array($name, ['findings.escalate', 'audit.versions.sections.store', 'audit.sections.criteria.store'])) {
        $routeNames[] = $name;
    }
}
$checks['New routes registered'] = implode(', ', $routeNames) ?: 'NONE';

echo '=== Wave 2 Verification ==='.PHP_EOL;
foreach ($checks as $name => $status) {
    $icon = $status === 'OK' ? '[+]' : ($status === 'MISSING' || $status === 'NONE' ? '[!]' : '[i]');
    echo "{$icon} {$name}: {$status}".PHP_EOL;
}
echo '=== Done ==='.PHP_EOL;
