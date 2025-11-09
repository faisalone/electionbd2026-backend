<?php

namespace App\Observers;

use App\Models\Poll;

class PollObserver
{
    /**
     * Handle the Poll "created" event.
     */
    public function created(Poll $poll): void
    {
        //
    }

    /**
     * Handle the Poll "updated" event.
     */
    public function updated(Poll $poll): void
    {
        // Check if the poll status was changed to 'ended' AND the end_date has actually passed
        if ($poll->isDirty('status') && $poll->status === 'ended') {
            // Only select a winner if poll has actually ended and no winner exists yet
            if ($poll->end_date <= now() && !$poll->hasWinner()) {
                $poll->selectWinner();
            }
        }
    }

    /**
     * Handle the Poll "deleted" event.
     */
    public function deleted(Poll $poll): void
    {
        //
    }

    /**
     * Handle the Poll "restored" event.
     */
    public function restored(Poll $poll): void
    {
        //
    }

    /**
     * Handle the Poll "force deleted" event.
     */
    public function forceDeleted(Poll $poll): void
    {
        //
    }
}
