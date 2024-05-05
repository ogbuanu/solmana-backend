<?php

namespace App\Observers;

use App\Models\ActionPoint;
use Illuminate\Support\Facades\Log;

class PointObserver
{
    /**
     * Handle the ActionPoint "created" event.
     */
    public function created(ActionPoint $actionPoint): void
    {
        //
    }

    /**
     * Handle the ActionPoint "updated" event.
     */
    public function updated(ActionPoint $actionPoint): void
    {
        $this->updateTireLevel($actionPoint);
    }

    /**
     * Handle the ActionPoint "deleted" event.
     */
    public function deleted(ActionPoint $actionPoint): void
    {
        //
    }

    /**
     * Handle the ActionPoint "restored" event.
     */
    public function restored(ActionPoint $actionPoint): void
    {
        //
    }

    /**
     * Handle the ActionPoint "force deleted" event.
     */
    public function forceDeleted(ActionPoint $actionPoint): void
    {
        //
    }

    protected function updateTireLevel(ActionPoint $actionPoint)
    {
        $Tire_Level = config("data.tireLevel");

        Log::info("updateTireLevel");
        if ($actionPoint->balance >= 200) {
            $actionPoint->tire_level = $Tire_Level['good'];
            $actionPoint->save();
        }

        if ($actionPoint->balance >= 1000) {
            $actionPoint->tire_level = $Tire_Level['better'];
            $actionPoint->save();
        }

        if ($actionPoint->balance >= 5000) {
            $actionPoint->tire_level = $Tire_Level['best'];
            $actionPoint->save();
        }

        if ($actionPoint->balance >= 10000) {
            $actionPoint->tire_level = $Tire_Level['rice'];
            $actionPoint->save();
        }
    }
}
