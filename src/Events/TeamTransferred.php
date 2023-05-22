<?php

namespace JoelButcher\JetstreamTeamTransfer\Events;

use Illuminate\Foundation\Events\Dispatchable;

class TeamTransferred
{
    use Dispatchable;

    /**
     * The team instance.
     */
    public mixed $team;

    /**
     * The original team owner.
     */
    public mixed $from;

    /**
     * The team member that the team was transferred to.
     */
    public mixed $to;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(mixed $team, mixed $from, mixed $to)
    {
        $this->team = $team;
        $this->from = $from;
        $this->to = $to;
    }
}
