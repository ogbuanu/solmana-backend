<?php

namespace App\Jobs;

use App\Models\ActionPoint;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class KycPointJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //
        $users = User::where('kyc_verified', 'TRUE')->get();
        $ids = array_map(function ($user) {
            return $user['id'];
        }, $users->toArray());
        $actionPoints = ActionPoint::whereIn('user_id', $ids)->where('last_kyc_earning', '>', Carbon::now()->subDays(1))->get();

        foreach ($actionPoints as $actionPoint) {
            $actionPoint->update(['balance' => \DB::raw('balance + 50'), 'last_kyc_earning' => Carbon::now()]);
        }

        return;
    }
}
