<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_application_redirects_unauthenticated_user(): void
    {
        $response = $this->get('/');

        $response->assertStatus(302);
    }

    public function test_the_application_returns_a_successful_response_for_authenticated_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/');

        $response->assertRedirect('/dashboard');
    }
}
