<?php

namespace JoelButcher\JetstreamTeamTransfer\Actions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use JoelButcher\JetstreamTeamTransfer\Events\TeamTransferred;

class TransferTeam
{
    /**
     * Transfer the given app to the given team.
     *
     * @throws AuthorizationException
     */
    public function transfer(mixed $user, mixed $team, mixed $teamMember): void
    {
        $this->authorize($user, $team, $teamMember);

        $team->transfer($user, $teamMember);

        TeamTransferred::dispatch($team, $user, $teamMember);
    }

    /**
     * Authorize that the user can transfer team ownership to the team member.
     *
     * @throws AuthorizationException
     */
    protected function authorize(mixed $user, mixed $team, mixed $teamMember): void
    {
        if (! Gate::forUser($user)->check('transfer', $team) &&
            $user->id !== $teamMember->id) {
            throw new AuthorizationException;
        }
    }
}
