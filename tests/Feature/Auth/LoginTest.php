<?php

namespace Tests\Feature\Auth;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'organization_id' => Organization::create([
                'code' => 'PPKP-DKI',
                'name' => 'PPKP DKI',
                'org_type' => 'central',
                'is_active' => true,
            ])->id,
            'name' => 'Petugas',
            'email' => 'petugas@test.local',
            'password' => bcrypt('SandiKuat123'),
            'auth_provider' => 'local',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function user_can_login_with_correct_credentials(): void
    {
        $response = $this->post('/login', [
            'email' => 'petugas@test.local',
            'password' => 'SandiKuat123',
        ]);

        $response->assertStatus(302);
        $this->assertAuthenticatedAs($this->user);
    }

    /** @test */
    public function wrong_password_shows_helpful_message(): void
    {
        $response = $this->from('/login')->post('/login', [
            'email' => 'petugas@test.local',
            'password' => 'salah',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('Sandi salah', session('errors')->first('email'));
    }

    /** @test */
    public function unknown_account_shows_helpful_message(): void
    {
        $response = $this->from('/login')->post('/login', [
            'email' => 'tidak-ada@test.local',
            'password' => 'apa saja',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('Akun tidak ditemukan', session('errors')->first('email'));
    }

    /** @test */
    public function inactive_user_cannot_login(): void
    {
        $this->user->update(['is_active' => false]);

        $response = $this->from('/login')->post('/login', [
            'email' => 'petugas@test.local',
            'password' => 'SandiKuat123',
        ]);

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    /** @test */
    public function user_can_logout(): void
    {
        $this->actingAs($this->user);

        $response = $this->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }
}
