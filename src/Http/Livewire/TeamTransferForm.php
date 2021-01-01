<?php

namespace JoelButcher\JetstreamTeamTransfer\Http\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use JoelButcher\JetstreamTeamTransfer\Actions\TransferTeam;
use Laravel\Jetstream\Jetstream;
use Livewire\Component;

class TeamTransferForm extends Component
{
    /**
     * The team instance.
     *
     * @var mixed
     */
    public $team;

    /**
     * Indicates if team transfer is being confirmed.
     *
     * @var bool
     */
    public $confirmingTransferTeam;

    /**
     * The "transfer team" form state.
     *
     * @var array
     */
    public $transferTeamForm = [
        'email',
    ];

    /**
     * The user's current password.
     *
     * @var string
     */
    public $password = '';

    /**
     * Mount the component.
     *
     * @param  mixed  $team
     * @return void
     */
    public function mount($team)
    {
        $this->team = $team;
    }

    /**
     * Confirm that the user would like to transfer the current team.
     *
     * @return void
     */
    public function confirmTransferTeam()
    {
        $this->resetErrorBag();

        $this->password = '';

        $this->dispatchBrowserEvent('confirming-team-transfer');

        $this->confirmingTransferTeam = true;
    }

    /**
     * Transfer the current team.
     *
     * @return void
     */
    public function transferTeam(TransferTeam $transferrer)
    {
        $this->resetErrorBag();

        if (! Hash::check($this->password, Auth::user()->password)) {
            throw ValidationException::withMessages([
                'password' => [__('This password does not match our records.')],
            ]);
        }

        $transferrer->transfer(
            Auth::user(),
            $this->team,
            Jetstream::findUserByEmailOrFail($this->transferTeamForm['email'])
        );

        $this->confirmingTransferTeam = false;

        $this->emit('teamTransferred');
    }

    /**
     * Render the component.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('teams.team-transfer-form');
    }
}
