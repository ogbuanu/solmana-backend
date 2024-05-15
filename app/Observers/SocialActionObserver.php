<?php

namespace App\Observers;

use App\Models\ActionPoint;
use App\Models\SocialAction;

class SocialActionObserver
{
    /**
     * Handle the SocialAction "created" event.
     */
    // public function created(SocialAction $socialAction): void
    // {
    //     //
    // }

    /**
     * Handle the SocialAction "updated" event.
     */
    public function updated(SocialAction $socialAction): void
    {
        //
        if ($socialAction->wasChanged('status') && $socialAction->status == "APPROVED") {
            $action = ActionPoint::where('user_id', $socialAction->user_id)->first();
            $action->balance += 10;
            $action->save();
        }
    }

    /**
     * Handle the SocialAction "deleted" event.
     */
    // public function deleted(SocialAction $socialAction): void
    // {
    //     //
    // }

    // /**
    //  * Handle the SocialAction "restored" event.
    //  */
    // public function restored(SocialAction $socialAction): void
    // {
    //     //
    // }

    // /**
    //  * Handle the SocialAction "force deleted" event.
    //  */
    // public function forceDeleted(SocialAction $socialAction): void
    // {
    //     //
    // }
}
