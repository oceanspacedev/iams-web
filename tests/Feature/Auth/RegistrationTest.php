<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'phone' => '081234567890',
            'email' => 'test@example.com',
            'requested_role' => 'auditor',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        // Pendaftaran mandiri menunggu persetujuan admin: user belum login dan belum aktif
        $this->assertGuest();
        $response->assertRedirect(route('register.pending', absolute: false));
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'approval_status' => 'pending',
            'is_active' => false,
        ]);
    }
}
