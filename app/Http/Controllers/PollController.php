<?php

namespace App\Http\Controllers;

use App\Models\Poll;
use App\Models\Vote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use App\Events\VoteRecorded;
use App\Jobs\UpdatePendingVotes;
use Illuminate\Support\Facades\Redis;

class PollController extends Controller
{
    public function index()
    {
        $polls = Cache::remember('active_polls_page_' . request('page', 1), 60, function () {
            return Poll::where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('end_at')->orWhere('end_at', '>', now());
                })
                ->withCount('options')
                ->latest()
                ->paginate(12);
        });

        foreach ($polls as $poll) {
            $poll->total_votes += (int) Cache::get("pending_total_" . $poll->id, 0);
        }

        return view('polls.index',['polls' => $polls]);
    }

    public function show(Request $request, $slug)
    {
        $poll = Cache::remember("poll_" . $slug, 60 ,function () use ($slug) {
            return Poll::where('slug', $slug)->with('options')->firstOrFail();
        });

        $votedOptionId = $this->getVotedOptionId($poll->id);
        $hasVoted = $votedOptionId !== null;

        // Add pending cached votes for real-time display
        $this->applyPendingCounts($poll);


        return view('polls.show', [
            'poll' => $poll, 
            'hasVoted' => $hasVoted, 
            'votedOptionId' => $votedOptionId
        ]);
    }

    public function vote(Request $request, Poll $poll)
    {

        
        $key = 'vote_' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 10)) {

            return response()->json([
                'success' => false,
                'message' => 'Too many attempts. Please try later.',
            ], 429);

        }

        RateLimiter::hit($key, 60);

        $request->validate([
            'option_id' => ['required', 'integer'],
        ]);

        if (!$poll->isOpen()) {
            return response()->json([
                'success' => false,
                'message' => 'This poll is no longer accepting votes.',
            ], 422);
        }

        $optionIds = Cache::rememberForever("poll_option_ids_" . $poll->id, function () use ($poll) {
            return $poll->options()->pluck('id')->toArray();
        });

        if (!in_array((int) $request->option_id, $optionIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid option for this poll.',
            ], 422);
        }

        $ip = $request->ip();
        $userId = auth()->id();

        $voteKey = $this->voteCacheKey($poll->id);

        if (Cache::has($voteKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Already voted on this poll.',
            ], 422);
        }

        $now = now()->toDateTimeString();

        Redis::rpush("pending_votes_" .$poll->id, json_encode([
            'poll_id' => $poll->id,
            'poll_option_id' => (int) $request->option_id,
            'user_id' => $userId,
            'ip_address' => $ip,
            'created_at' => $now,
            'updated_at' => $now,
        ]));

        Cache::increment("pending_count_" . $poll->id . "_" . $request->option_id);
        Cache::increment("pending_total_" . $poll->id );

        Cache::forever($voteKey, (int) $request->option_id);

        if (!Cache::has("flush_scheduled_" . $poll->id)) {
            Cache::put("flush_scheduled_". $poll->id , true, 90);
            UpdatePendingVotes::dispatch($poll->id)->delay(now()->addMinute());
        }

        $this->applyPendingCounts($poll);

        $results = $poll->options->map(function ($opt) use ($poll) {

            return [
                'id' => $opt->id,
                'text' => $opt->text,
                'votes_count' => $opt->votes_count,
                'percentage' => $poll->total_votes > 0
                    ? round(($opt->votes_count / $poll->total_votes) * 100, 1)
                    : 0,
            ];
        });

        broadcast(new VoteRecorded($poll, $poll->total_votes, $results->toArray()));

        return response()->json([
            'success' => true,
            'message' => 'Vote recorded!',
            'total_votes' => $poll->total_votes,
            'results' => $results,
            'voted_option_id' => (int) $request->option_id,
        ]);
    }

    private function getVotedOptionId(int $pollId): ?int
    {
        $key = $this->voteCacheKey($pollId);
        $cached = Cache::get($key);

        if ($cached !== null) {
            return (int) $cached;
        }

        $userId = auth()->id();
        $ip = request()->ip();

        $vote = Vote::where('poll_id', $pollId)
            ->where(function ($q) use ($userId, $ip) {
                if ($userId) {
                    $q->where('user_id', $userId);
                } else {
                    $q->where('ip_address', $ip);
                }
            })
            ->first();

        if ($vote) {
            Cache::forever($key, $vote->poll_option_id);
            return (int) $vote->poll_option_id;
        }

        return null;
    }

    private function voteCacheKey(int $pollId)
    {
        if (auth()->check()) {
            return "voted_" . $pollId . "user" . auth()->id();
        }

        return "voted" . $pollId . "-ip-" . request()->ip();
    }

    private function applyPendingCounts(Poll $poll): void
    {
        $poll->total_votes += (int) Cache::get("pending_total_" . $poll->id, 0);

        foreach ($poll->options as $option) {
            $option->votes_count += (int) Cache::get("pending_count_" . $poll->id . "_" . $option->id, 0);
        }
    }

}
