<div>
    @if (Gate::check('transferTeam', $team) && ! $team->personal_team && $team->users->count())
        <x-section-border />

        <div class="mt-10 sm:mt-0">
            <x-action-section>
                <x-slot name="title">
                    {{ __('Transfer Team') }}
                </x-slot>

                <x-slot name="description">
                    {{ __("Transfer team ownership to another team member.") }}
                </x-slot>

                <x-slot name="content">
                    <div class="col-span-6">
                        <div class="max-w-xl text-sm text-gray-600 dark:text-gray-600">
                            {{ __('Please provider the email address of the team you would like to transfer the team to. The email address must be associated with an existing account.') }}
                        </div>
                    </div>

                    <!-- Member Email -->
                    <div class="grid grid-cols-6 gap-6 mt-5">
                        <div class="col-span-6 sm:col-span-4">
                            <x-label for="email" value="{{ __('Email') }}" />
                            <x-input id="name" type="text" class="mt-1 block w-full" wire:model.defer="transferTeamForm.email" wire:keydown.enter="confirmTransferTeam" />
                            <x-input-error for="email" class="mt-2" />
                        </div>
                    </div>

                    <div class="flex items-center mt-5">
                        <x-danger-button wire:click="confirmTransferTeam" wire:loading.attr="disabled">
                            {{ __('Transfer Team') }}
                        </x-danger-button>

                        <x-action-message class="ml-3" on="loggedOut">
                            {{ __('Team transferred, please refresh.') }}
                        </x-action-message>
                    </div>
                </x-slot>
            </x-action-section>
        </div>
    @endif

    <!-- Logout Other Devices Confirmation Modal -->
    <x-dialog-modal wire:model="confirmingTransferTeam">
        <x-slot name="title">
            {{ __('Transfer Team') }}
        </x-slot>

        <x-slot name="content">
            {{ __('Are you sure you want to transfer this team? Once a team is transferred, you will lose control of all of its resources and data.') }}

            <div class="mt-4" x-data="{}" x-on:confirming-team-transfer.window="setTimeout(() => $refs.password.focus(), 250)">
                <x-input type="password" class="mt-1 block w-3/4" placeholder="{{ __('Password') }}"
                            x-ref="password"
                            wire:model.defer="password"
                            wire:keydown.enter="transferTeam" />

                <x-input-error for="password" class="mt-2" />
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$toggle('confirmingTransferTeam')" wire:loading.attr="disabled">
                {{ __('Nevermind') }}
            </x-secondary-button>

            <x-danger-button class="ml-2" wire:click="transferTeam" wire:loading.attr="disabled">
                {{ __('Transfer Team') }}
            </x-danger-button>
        </x-slot>
    </x-dialog-modal>
</div>
