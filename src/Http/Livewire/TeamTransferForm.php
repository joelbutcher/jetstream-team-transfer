<?php

namespace JoelButcher\JetstreamTeamTransfer\Http\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use JoelButcher\JetstreamTeamTransfer\Actions\TransferTeam;
use Laravel\Jetstream\Jetstream;
use Livewire\Component;

class TeamTransferForm extends Component
{
    /**
     * The team instance.
     */
    public mixed $team = null;

    /**
     * Indicates if team transfer is being confirmed.
     */
    public bool $confirmingTransferTeam = false;

    /**
     * The "transfer team" form state.
     *
     * @var array<int, string>
     */
    public array $transferTeamForm = [
        'email',
    ];

    /**
     * The user's current password.
     */
    public string $password = '';

    /**
     * Mount the component.
     */
    public function mount(mixed $team): void
    {
        $this->team = $team;
    }

    /**
     * Confirm that the user would like to transfer the current team.
     */
    public function confirmTransferTeam(): void
    {
        $this->resetErrorBag();

        $this->password = '';

        $this->dispatchBrowserEvent('confirming-team-transfer');

        $this->confirmingTransferTeam = true;
    }

    /**
     * Transfer the current team.
     */
    public function transferTeam(TransferTeam $action): void
    {
        $this->resetErrorBag();

        if (! Hash::check($this->password, Auth::user()->password)) {
            throw ValidationException::withMessages([
                'password' => [__('This password does not match our records.')],
            ]);
        }

        $action->transfer(
            Auth::user(),
            $this->team,
            Jetstream::findUserByEmailOrFail($this->transferTeamForm['email'])
        );

        $this->confirmingTransferTeam = false;

        $this->emit('teamTransferred');
    }

    /**
     * Render the component.
     */
    public function render(): View
    {
        return view('teams.team-transfer-form');
    }
}
