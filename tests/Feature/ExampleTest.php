<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        // Root redirects to /consultorio which requires auth
        $response = $this->get('/');

        // Without auth, it should redirect
        $response->assertStatus(302);

        // With auth, it should work
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/consultorio');
        $response->assertStatus(200);
    }
}
