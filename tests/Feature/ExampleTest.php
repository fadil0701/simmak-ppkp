<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_redirects_guests_to_login(): void
    {
        $response = $this->get('/');

        // routes/web.php: root tidak menyajikan halaman publik, tamu diarahkan ke login.
        $response->assertRedirect('/login');
    }
}
