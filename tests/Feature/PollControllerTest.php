<?php

namespace Tests\Feature;

use App\Events\VoteRecorded;
use App\Jobs\UpdatePendingVotes;
use App\Models\Poll;
use App\Models\PollOption;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PollControllerTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * guest user can successfully vote on active poll
     */
    public function test_guest_can_vote(): void
    {
        Event::fake([VoteRecorded::class]);
        Queue::fake();

        $poll = $this->createPollWithOptions();
        $option = $poll->options->first();

        $this->postJson("/poll/" . $poll->id . "/vote", ['option_id' => $option->id])
            ->assertStatus(200)
            ->assertJson(['success' => true, 'voted_option_id' => $option->id]);
    }

    /**
     * logged user can successfully vote on active poll
     */
    public function test_authenticated_user_can_vote(): void
    {
        Event::fake([VoteRecorded::class]);
        Queue::fake();

        $user = User::factory()->create();
        $poll = $this->createPollWithOptions();
        $option = $poll->options->first();

        $this->actingAs($user)
            ->postJson("/poll/" . $poll->id . "/vote", ['option_id' => $option->id])
            ->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /**
     * guest user cannot vote duplicate on same poll
     */
    public function test_guest_cannot_vote_twice(): void
    {
        Event::fake([VoteRecorded::class]);
        Queue::fake();

        $poll = $this->createPollWithOptions();
        $option = $poll->options->first();

        $this->postJson("/poll/" . $poll->id . "/vote", ['option_id' => $option->id]);

        $this->postJson("/poll/" . $poll->id . "/vote", ['option_id' => $option->id])
            ->assertStatus(422)
            ->assertJson(['message' => 'Already voted on this poll.']);
    }

    /**
     * authenticated user cannot vote duplicate on same poll
     */
    public function test_authenticated_user_cannot_vote_twice(): void
    {
        Event::fake([VoteRecorded::class]);
        Queue::fake();

        $user = User::factory()->create();
        $poll = $this->createPollWithOptions();
        $option = $poll->options->first();

        $this->actingAs($user)
            ->postJson("/poll/" . $poll->id . "/vote", ['option_id' => $option->id]);

        $this->actingAs($user)
            ->postJson("/poll/" . $poll->id . "/vote", ['option_id' => $option->id])
            ->assertStatus(422)
            ->assertJson(['message' => 'Already voted on this poll.']);
    }

    private function createPollWithOptions(array $pollAttrs = [], int $optionCount = 3): Poll
    {
        $poll = Poll::factory()->create($pollAttrs);
        PollOption::factory()->count($optionCount)->create(['poll_id' => $poll->id]);
        return $poll->load('options');
    }
}
