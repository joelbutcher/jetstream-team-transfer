<?php

namespace Tests\Feature;

use App\Models\Membership;
use App\Models\Team;
use App\Models\User;
use App\Policies\TeamPolicy;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use JoelButcher\JetstreamTeamTransfer\Actions\TransferTeam;
use JoelButcher\JetstreamTeamTransfer\Actions\ValidateTeamTransfer;
use Laravel\Jetstream\Jetstream;
use Tests\TestCase;

class TransferTeamTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Gate::policy(Team::class, TeamPolicy::class);
        Jetstream::useUserModel(User::class);
        Jetstream::useMembershipModel(Membership::class);
    }

    public function test_team_can_be_transferred()
    {
        $user = User::forceCreate([
            'name' => 'Taylor Otwell',
            'email' => 'taylor@laravel.com',
            'password' => 'secret',
        ]);

        $team = $user->ownedTeams()->create([
            'name' => 'Test Team',
            'personal_team' => false,
        ]);

        $team->users()->attach($otherUser = User::forceCreate([
            'name' => 'Adam Wathan',
            'email' => 'adam@laravel.com',
            'password' => 'secret',
        ]), ['role' => 'admin']);

        $action = new TransferTeam;

        $action->transfer($team->owner, $team, $otherUser);

        $this->assertEquals($otherUser->id, $team->owner->id);
    }

    public function test_team_transfer_can_be_validated()
    {
        $user = User::forceCreate([
            'name' => 'Taylor Otwell',
            'email' => 'taylor@laravel.com',
            'password' => 'secret',
        ]);

        $team = $user->ownedTeams()->create([
            'name' => 'Test Team',
            'personal_team' => false,
        ]);

        $action = new ValidateTeamTransfer;

        $action->validate($team->owner, $team);

        $this->assertTrue(true);
    }

    public function test_personal_team_cant_be_transferred()
    {
        $this->expectException(ValidationException::class);

        $user = User::forceCreate([
            'name' => 'Taylor Otwell',
            'email' => 'taylor@laravel.com',
            'password' => 'secret',
        ]);

        $team = $user->ownedTeams()->create([
            'name' => 'Test Team',
            'personal_team' => false,
        ]);

        $team->forceFill(['personal_team' => true])->save();

        $action = new ValidateTeamTransfer;

        $action->validate($team->owner, $team);
    }

    public function test_non_owner_cant_transfer_team()
    {
        $this->expectException(AuthorizationException::class);

        Jetstream::useUserModel(User::class);

        $user = User::forceCreate([
            'name' => 'Taylor Otwell',
            'email' => 'taylor@laravel.com',
            'password' => 'secret',
        ]);

        $team = $user->ownedTeams()->create([
            'name' => 'Test Team',
            'personal_team' => false,
        ]);

        $action = new ValidateTeamTransfer;

        $action->validate(User::forceCreate([
            'name' => 'Adam Wathan',
            'email' => 'adam@laravel.com',
            'password' => 'secret',
        ]), $team);
    }
}
