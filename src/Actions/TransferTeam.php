<?php

namespace JoelButcher\JetstreamTeamTransfer\Actions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use JoelButcher\JetstreamTeamTransfer\Events\TeamTransferred;

class TransferTeam
{
    /**
     * Transfer the given app to the given team.
     *
     * @param  mixed  $user
     * @param  mixed  $team
     * @param  mixed  $teamMember
     * @return void
     */
    public function transfer($user, $team, $teamMember)
    {
        $this->authorize($user, $team, $teamMember);

        $this->ensureTeamUserExists($team, $teamMember);

        $team->transfer($user, $teamMember);

        TeamTransferred::dispatch($team, $user, $teamMember);
    }

    /**
     * Authorize that the user can transfer team ownership to the team member.
     *
     * @param  mixed  $user
     * @param  mixed  $team
     * @param  mixed  $teamMember
     * @return void
     */
    protected function authorize($user, $team, $teamMember)
    {
        if (! Gate::forUser($user)->check('transferTeam', $team) &&
            $user->id !== $teamMember->id) {
            throw new AuthorizationException;
        }
    }

    /**
     * Ensure that the new owner is part of the team.
     *
     * @param  mixed  $team
     * @param  mixed  $user
     * @return void
     */
    protected function ensureTeamUserExists($team, $user)
    {
        if (! $team->hasUser($user)) {
            throw ValidationException::withMessages([
                'You cannot transfer team ownership to users outside of this team.',
            ])->errorBag('transferTeam');
        }
    }
}
