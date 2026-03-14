<?php

namespace Tests\Feature\Admin;

use App\Models\Poll;
use App\Models\PollOption;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PollControllerTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    /**
     * admin can create a poll with options
     */
    public function test_admin_can_create_poll(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/polls', [
            'title' => 'My Test Poll',
            'options' => ['Option A', 'Option B'],
        ]);

        $poll = Poll::where('title', 'My Test Poll')->first();
        $response->assertRedirect(route('admin.polls.show', $poll));
        $this->assertCount(2, $poll->options);
    }

    /**
     * admin can view their own poll
     */
    public function test_admin_can_view_own_poll(): void
    {
        $poll = $this->createPollForAdmin();

        $this->actingAs($this->admin)->get("/admin/polls/" . $poll->id)
            ->assertStatus(200);
    }

    /**
     * admin cannot view another admin's poll
     */
    public function test_admin_cannot_view_others_poll(): void
    {
        $otherPoll = Poll::factory()->create();

        $this->actingAs($this->admin)->get("/admin/polls/" . $otherPoll->id)
            ->assertStatus(403);
    }

    /**
     * admin can update their own poll
     */
    public function test_admin_can_update_own_poll(): void
    {
        $poll = $this->createPollForAdmin();

        $this->actingAs($this->admin)->put("/admin/polls/" . $poll->id, [
            'title' => 'Updated Title',
        ]);

        $this->assertDatabaseHas('polls', ['id' => $poll->id, 'title' => 'Updated Title']);
    }

    /**
     * admin cannot update another admin's poll
     */
    public function test_admin_cannot_update_others_poll(): void
    {
        $otherPoll = Poll::factory()->create();

        $this->actingAs($this->admin)->put("/admin/polls/" . $otherPoll->id, [
            'title' => 'Hacked',
        ])->assertStatus(403);
    }

    /**
     * admin can delete their own poll
     */
    public function test_admin_can_delete_own_poll(): void
    {
        $poll = $this->createPollForAdmin();

        $this->actingAs($this->admin)->delete("/admin/polls/" . $poll->id)
            ->assertRedirect(route('admin.polls.index'));

        $this->assertDatabaseMissing('polls', ['id' => $poll->id]);
    }

    /**
     * admin cannot delete another admin's poll
     */
    public function test_admin_cannot_delete_others_poll(): void
    {
        $otherPoll = Poll::factory()->create();

        $this->actingAs($this->admin)->delete("/admin/polls/" . $otherPoll->id)
            ->assertStatus(403);
    }

    private function createPollForAdmin(array $attrs = [], int $optionCount = 3): Poll
    {
        $poll = Poll::factory()->create(array_merge(['user_id' => $this->admin->id], $attrs));
        PollOption::factory()->count($optionCount)->create(['poll_id' => $poll->id]);
        return $poll->load('options');
    }
}
