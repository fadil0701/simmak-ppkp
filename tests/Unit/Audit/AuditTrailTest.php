<?php

namespace Tests\Unit\Audit;

use App\Models\AuditTrail;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuditTrailTest extends TestCase
{
    /** @test */
    public function audit_trail_record_can_be_created(): void
    {
        $org = Organization::create([
            'code' => 'TEST-ORG',
            'name' => 'Test Organization',
            'org_type' => 'central',
            'is_active' => true,
        ]);

        $user = User::create([
            'organization_id' => $org->id,
            'name' => 'Test User',
            'email' => 'test@test.local',
            'password' => bcrypt('password'),
            'auth_provider' => 'local',
            'is_active' => true,
        ]);

        $trail = AuditTrail::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'event' => 'test.event',
            'auditable_type' => 'test',
            'old_values' => ['key' => 'old'],
            'new_values' => ['key' => 'new'],
        ]);

        $this->assertNotNull($trail->id);
        $this->assertEquals('test.event', $trail->event);
    }

    /** @test */
    public function audit_trail_record_cannot_be_updated(): void
    {
        $org = Organization::create([
            'code' => 'TEST-ORG-2',
            'name' => 'Test Organization 2',
            'org_type' => 'central',
            'is_active' => true,
        ]);

        $user = User::create([
            'organization_id' => $org->id,
            'name' => 'Test User 2',
            'email' => 'test2@test.local',
            'password' => bcrypt('password'),
            'auth_provider' => 'local',
            'is_active' => true,
        ]);

        $trail = AuditTrail::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'event' => 'test.event',
            'auditable_type' => 'test',
        ]);

        $this->expectException(MassAssignmentException::class);
        $trail->update(['event' => 'modified.event']);
    }

    /** @test */
    public function audit_trail_record_cannot_be_deleted(): void
    {
        $org = Organization::create([
            'code' => 'TEST-ORG-3',
            'name' => 'Test Organization 3',
            'org_type' => 'central',
            'is_active' => true,
        ]);

        $user = User::create([
            'organization_id' => $org->id,
            'name' => 'Test User 3',
            'email' => 'test3@test.local',
            'password' => bcrypt('password'),
            'auth_provider' => 'local',
            'is_active' => true,
        ]);

        $trail = AuditTrail::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'event' => 'test.event',
            'auditable_type' => 'test',
        ]);

        $this->expectException(MassAssignmentException::class);
        $trail->delete();
    }
}
