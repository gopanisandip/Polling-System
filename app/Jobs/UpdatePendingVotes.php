<?php

namespace App\Jobs;

use App\Events\VoteRecorded;
use App\Models\Poll;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class UpdatePendingVotes implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $pollId) {}

    public function handle(): void
    {
        
        $votes = [];

        while ($data = Redis::lpop("pending_votes_" . $this->pollId)) {
            $votes[] = json_decode($data, true);
        }

        if (empty($votes)) {
            Cache::forget("flush_scheduled_" . $this->pollId);
            return;
        }
        
        $optionCounts = [];
        $totalCount = count($votes);

        foreach ($votes as $vote) {
            $optionId = $vote['poll_option_id'];
            $optionCounts[$optionId] = ($optionCounts[$optionId] ?? 0) + 1;
        }

        try {

            DB::transaction(function () use ($votes, $optionCounts, $totalCount) {

                $inserted = DB::table('votes')->insertOrIgnore($votes);

                /**
                 *  old code always incremented by the full batch count, even when insertOrIgnore() 
                 *  silently skipped duplicates. This caused vote counts to be 
                 *  higher than actual votes in the DB (phantom votes).
                 */
                
                if ($inserted === $totalCount) {

                    foreach ($optionCounts as $optionId => $count) {
                        DB::table('poll_options')
                            ->where('id', $optionId)
                            ->increment('votes_count', $count);
                    }

                    DB::table('polls')
                        ->where('id', $this->pollId)
                        ->increment('total_votes', $totalCount);

                }else{

                    $actualCounts = DB::table('votes')
                        ->where('poll_id', $this->pollId)
                        ->selectRaw('poll_option_id, COUNT(*) as cnt')
                        ->groupBy('poll_option_id')
                        ->pluck('cnt', 'poll_option_id');

                    foreach ($actualCounts as $optionId => $count) {
                        DB::table('poll_options')
                            ->where('id', $optionId)
                            ->update(['votes_count' => $count]);
                    }

                    DB::table('polls')
                        ->where('id', $this->pollId)
                        ->update(['total_votes' => $actualCounts->sum()]);
                }
               
            });

        } catch (\Exception $e) {
            
            foreach ($votes as $vote) {
                Redis::rpush("pending_votes_" . $this->pollId, json_encode($vote));
            }

            throw $e;
        }

        
        foreach ($optionCounts as $optionId => $count) {
            Cache::decrement("pending_count_" . $this->pollId . "_" . $optionId, $count);
        }

        Cache::decrement("pending_total_" . $this->pollId, $totalCount);

        $poll = Poll::with('options')->find($this->pollId);

        if ($poll) {

            Cache::forget("poll_" . $poll->slug);
            Cache::forget("poll_results_" . $poll->id);
            Cache::forget('active_polls_page_1');

            broadcast(new VoteRecorded($poll));
        }

        Cache::forget("flush_scheduled_" . $this->pollId);
        
    }

}
