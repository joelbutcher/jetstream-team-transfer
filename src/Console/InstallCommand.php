<?php

namespace JoelButcher\JetstreamTeamTransfer\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Laravel\Jetstream\Jetstream;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jetstream-team-transfer:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the components and resources for Jetstream Team Transfer';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        // Check if Jetstream has been installed.
        if (! file_exists(config_path('jetstream.php'))) {
            $this->warn('Jetstream hasn\'t been installed. This package requires Jetstream to be installed.');

            if ($this->ask('Do you want to install Jetstream? (yes/no)', 'no') !== 'yes') {
                return 0;
            }

            $stack = $this->choice('Which Jetstream stack do you prefer', ['livewire', 'inertia']);

            if (($useTeams = $this->ask('Will your application use teams? (yes/no)', 'no') === 'yes') === 'no') {
                $this->error('This package requires the Jetstream teams feature. Please enable this in the Jetstream config. ');

                return 0;
            }

            $this->callSilent('jetstream:install', ['stack' => $stack, '--teams' => $useTeams]);
        } else {
            $stack = config('jetstream.stack');
        }

        if ($stack === 'inertia') {
            $this->error('The inertia Jetstream pack is not supported');

            return 0;
        }

        // Directories...
        (new Filesystem)->ensureDirectoryExists(app_path('Policies'));
        (new Filesystem)->ensureDirectoryExists(resource_path('views/teams'));

        // Models...
        copy(__DIR__.'/../../stubs/app/Models/Team.php', app_path('Models/Team.php'));
        copy(__DIR__.'/../../stubs/app/Policies/TeamPolicy.php', app_path('Policies/TeamPolicy.php'));
        copy(__DIR__.'/../../stubs/resources/views/teams/team-transfer-form.blade.php', resource_path('views/teams/team-transfer-form.blade.php'));
    }
}
