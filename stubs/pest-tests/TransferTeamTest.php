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

beforeEach(function () {
    Gate::policy(Team::class, TeamPolicy::class);
    Jetstream::useUserModel(User::class);
    Jetstream::useMembershipModel(Membership::class);
});

test('team can be transferred', function () {
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

    (new TransferTeam())->transfer($team->owner, $team, $otherUser);

    expect($team->fresh()->owner->id)->toEqual($otherUser->id);
});

test('team transfer can be validated', function () {
    $user = User::forceCreate([
        'name' => 'Taylor Otwell',
        'email' => 'taylor@laravel.com',
        'password' => 'secret',
    ]);

    $team = $user->ownedTeams()->create([
        'name' => 'Test Team',
        'personal_team' => false,
    ]);

    (new ValidateTeamTransfer)->validate($team->owner, $team);

    expect(true)->toBeTrue();
});

test('personal team cant be transferred', function () {
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

    expect(fn () => (new ValidateTeamTransfer())->validate($team->owner, $team))
        ->toThrow(ValidationException::class);
});

test('non owner cant transfer team', function () {
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

    expect(fn () => (new ValidateTeamTransfer())->validate(User::forceCreate([
        'name' => 'Adam Wathan',
        'email' => 'adam@laravel.com',
        'password' => 'secret',
    ]), $team))->toThrow(AuthorizationException::class);
});
