<?php

namespace App\Events;

use App\Models\Poll;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VoteRecorded implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $pollId;
    public int $totalVotes;
    public array $results;

    public function __construct(Poll $poll, ?int $totalVotes = null, ?array $results = null)
    {
        $this->pollId = $poll->id;

        if ($totalVotes !== null && $results !== null) {
            $this->totalVotes = $totalVotes;
            $this->results = $results;
        } else {
            $poll->load('options');

            $this->totalVotes = $poll->total_votes;
            $this->results = $poll->options->map(function ($option) use ($poll) {
                $percentage = $poll->total_votes > 0
                    ? round(($option->votes_count / $poll->total_votes) * 100, 1)
                    : 0;

                return [
                    'id' => $option->id,
                    'text' => $option->text,
                    'votes_count' => $option->votes_count,
                    'percentage' => $percentage,
                ];
            })->toArray();
        }
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('poll.' . $this->pollId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'vote.recorded';
    }

    public function broadcastWith(): array
    {
        return [
            'poll_id' => $this->pollId,
            'total_votes' => $this->totalVotes,
            'results' => $this->results,
        ];
    }
}
