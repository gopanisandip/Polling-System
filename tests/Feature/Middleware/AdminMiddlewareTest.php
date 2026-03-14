<?php

namespace Tests\Feature\Middleware;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminMiddlewareTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * only admin users can access admin routes
     */
    public function test_only_admin_can_access_admin_routes(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get('/admin/polls')
            ->assertStatus(200);
    }
}
