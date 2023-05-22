<?php

namespace JoelButcher\JetstreamTeamTransfer\Actions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ValidateTeamTransfer
{
    /**
     * Validate that the team can be deleted by the given user.
     *
     * @throws AuthorizationException
     */
    public function validate(mixed $user, mixed $team): void
    {
        Gate::forUser($user)->authorize('transferTeam', $team);

        if ($team->personal_team) {
            throw ValidationException::withMessages([
                'team' => __('You may not transfer your personal team.'),
            ]);
        }
    }
}
