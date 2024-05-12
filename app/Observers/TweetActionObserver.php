<?php

namespace App\Observers;

use App\Models\ActionPoint;
use App\Models\TweetAction;
use Illuminate\Support\Carbon;

class TweetActionObserver
{
    /**
     * Handle the TweetAction "created" event.
     */
    public function created(TweetAction $tweetAction): void
    {
        //
    }

    /**
     * Handle the TweetAction "updated" event.
     */
    public function updated(TweetAction $tweetAction): void
    {
        //
        if ($tweetAction->wasChanged('status')) {
            $actionPoint = ActionPoint::where('user_id', $tweetAction->user_id)->first();
            if ($tweetAction->status == "APPROVED") {
                $actionPoint->addPoint('tweet');
                $actionPoint->last_tweeted = Carbon::now();
            }
            $actionPoint->is_pending = "FALSE";
            $actionPoint->save();
        }
    }

    /**
     * Handle the TweetAction "deleted" event.
     */
    public function deleted(TweetAction $tweetAction): void
    {
        //
    }

    /**
     * Handle the TweetAction "restored" event.
     */
    public function restored(TweetAction $tweetAction): void
    {
        //
    }

    /**
     * Handle the TweetAction "force deleted" event.
     */
    public function forceDeleted(TweetAction $tweetAction): void
    {
        //
    }
}
