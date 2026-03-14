<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Poll;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PollController extends Controller
{
    public function index()
    {
        $polls = Poll::where('user_id', auth()->id())
            ->withCount('votes')
            ->latest()
            ->paginate(10);

        foreach ($polls as $poll) {
            $poll->votes_count += (int) Cache::get("pending_total_" . $poll->id, 0);
        }

        return view('admin.polls.index', [
            'polls' => $polls
        ]);
    }

    public function create()
    {
        return view('admin.polls.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:128'],
            'description' => ['nullable', 'string', 'max:1000'],
            'options' => ['required', 'array', 'min:2', 'max:20'],
            'options.*' => ['required', 'string', 'max:128'],
            'end_at' => ['nullable', 'date', 'after:now'],
        ]);

        $poll = DB::transaction(function () use ($validated) {

            $poll = Poll::create([
                'user_id' => auth()->id(),
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'is_active' => true,
                'end_at' => $validated['end_at'] ?? null,
            ]);

            foreach ($validated['options'] as $index => $optionText) {
                $poll->options()->create([
                    'text' => $optionText,
                ]);
            }

            return $poll;
        });

        $poll->load('options');

        Cache::forever("poll_" . $poll->slug, $poll);
        Cache::forever("poll_results_" . $poll->id, $poll->toArray());
        Cache::forever("poll_option_ids_" . $poll->id, $poll->options->pluck('id')->toArray());
        Cache::forget('active_polls_page_1');

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Poll created!',
                'poll' => $poll,
                'redirect' => route('admin.polls.show', $poll),
            ]);
        }

        return redirect()->route('admin.polls.show', $poll)
            ->with('success', 'Poll created!');
    }

    public function show(Poll $poll)
    {
        $this->authorizePoll($poll);

        $poll->load('options');
        $this->applyPendingCounts($poll);

        return view('admin.polls.show', ['poll' => $poll]);
    }

    public function edit(Poll $poll)
    {
        $this->authorizePoll($poll);

        $poll->load('options');

        return view('admin.polls.edit', ['poll' => $poll]);
    }

    public function update(Request $request, Poll $poll)
    {
        $this->authorizePoll($poll);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:128'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
            'end_at' => ['nullable', 'date'],
        ]);

        $poll->update($validated);

        $this->clearPollCache($poll);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Poll updated!',
            ]);
        }

        return redirect()->route('admin.polls.show', $poll)
            ->with('success', 'Poll updated!');
    }

    public function destroy(Poll $poll)
    {
        $this->authorizePoll($poll);

        // no need transaction here because we already have database with cancade delete..so it will handle internally by database
        $poll->delete(); 

        $this->clearPollCache($poll);

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Poll deleted!']);
        }

        return redirect()->route('admin.polls.index')
            ->with('success', 'Poll deleted!');
    }

    public function results(Poll $poll)
    {
        $this->authorizePoll($poll);

        $results = Cache::rememberForever("poll_results_" . $poll->id, function () use ($poll) {
            return $poll->load('options')->toArray();
        });

        $pendingTotal = (int) Cache::get("pending_total_" . $poll->id, 0);
        $results['total_votes'] = ($results['total_votes'] ?? 0) + $pendingTotal;

        if (isset($results['options'])) {
            foreach ($results['options'] as &$option) {
                $pending = (int) Cache::get("pending_count_" . $poll->id  ."_" . $option['id'], 0);
                $option['votes_count'] = ($option['votes_count'] ?? 0) + $pending;
            }
        }

        return response()->json($results);
    }

    private function authorizePoll(Poll $poll): void
    {
        if ($poll->user_id !== auth()->id()) {
            abort(403, 'Unauthorized.');
        }
    }

    private function clearPollCache(Poll $poll): void
    {
        Cache::forget("poll_" . $poll->slug);
        Cache::forget("poll_results_" . $poll->id);
        Cache::forget("poll_option_ids_" . $poll->id);
        Cache::forget('active_polls_page_1');
    }

    private function applyPendingCounts(Poll $poll): void
    {
        $poll->total_votes += (int) Cache::get("pending_total_" . $poll->id, 0);

        foreach ($poll->options as $option) {
            $option->votes_count += (int) Cache::get("pending_count_" . $poll->id . "_" . $option->id, 0);
        }

    }
}
