<?php

namespace JoelButcher\JetstreamTeamTransfer\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Laravel\Jetstream\Jetstream;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jetstream-team-transfer:install
                            {--stack= : Indicates the desired stack to be installed (Livewire, Inertia)}
                            {--dark : Indicate that dark mode support should be installed}
                            {--teams : Indicates if team support should be installed}
                            {--api : Indicates if API support should be installed}
                            {--verification : Indicates if email verification support should be installed}
                            {--pest : Indicates if Pest should be installed}
                            {--ssr : Indicates if Inertia SSR support should be installed}
                            {--composer=global : Absolute path to the Composer binary which should be used to install packages}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the components and resources for Jetstream Team Transfer';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Check if Jetstream has been installed.
        if (! file_exists(config_path('jetstream.php'))) {
            $this->components->warn('Jetstream hasn\'t been installed. Installing now...');

            $stack = $this->option('stack') ?: $this->components->choice('Which stack would you like to use [inertia] or [livewire]?', ['inertia', 'livewire']);

            if (! in_array($stack, ['inertia', 'livewire'])) {
                $this->components->error('Invalid stack. Supported stacks are [inertia] and [livewire].');

                return Command::FAILURE;
            }

            $this->call('jetstream:install', [
                'stack' => $stack,
                '--teams' => $this->option('teams'),
                '--api' => $this->option('api'),
                '--verification' => $this->option('verification'),
                '--pest' => $this->option('pest'),
                '--ssr' => $this->option('ssr'),
                '--composer' => $this->option('composer'),
            ]);
        } else {
            $stack = config('jetstream.stack');
        }

        if (! Jetstream::hasTeamFeatures()) {
            $this->components->error('This package requires the "teams" feature for Jetstream to be enabled.');

            return Command::FAILURE;
        }

        if ($stack === 'livewire') {
            $this->installLivewireStack();
        }

        if ($stack === 'inertia') {
            $this->components->error(
                string: 'Sorry, support for Inertia is not ready yet.'
            );

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function installLivewireStack(): void
    {
        // Directories...
        (new Filesystem)->ensureDirectoryExists(resource_path('views/teams'));

        // Views...
        copy(__DIR__.'/../../stubs/resources/views/teams/show.blade.php', resource_path('views/teams/show.blade.php'));
        copy(__DIR__.'/../../stubs/resources/views/teams/team-transfer-form.blade.php', resource_path('views/teams/team-transfer-form.blade.php'));

        // Tests...
        $stubs = $this->getTestStubsPath();
        copy($stubs.'/TransferTeamTest.php', base_path('tests/Feature/TransferTeamTest.php'));

        $this->installModelTrait();
        $this->installPolicy();

        if (! $this->option('dark')) {
            $this->removeDarkClasses((new Finder)
                ->in(resource_path('views'))
                ->name('*.blade.php')
                ->filter(fn ($file) => $file->getPathname() !== resource_path('views/welcome.blade.php'))
            );
        }

        if (file_exists(base_path('pnpm-lock.yaml'))) {
            $this->runCommands(['pnpm install', 'pnpm run build']);
        } elseif (file_exists(base_path('yarn.lock'))) {
            $this->runCommands(['yarn install', 'yarn run build']);
        } else {
            $this->runCommands(['npm install', 'npm run build']);
        }

        $this->line('');
        $this->components->info('Livewire scaffolding installed successfully.');
    }

    private function installModelTrait(): void
    {
        $this->appendToFile(
            app_path('Models/Team.php'),
            'use Illuminate\Database\Eloquent\Factories\HasFactory;',
            'use JoelButcher\JetstreamTeamTransfer\CanBeTransferred;'
        );

        $this->appendToFile(
            app_path('Models/Team.php'),
            'use HasFactory;',
            '    use CanBeTransferred;',
        );
    }

    private function installPolicy(): void
    {
        $policy = <<<'PHP'
    /**
     * Determine whether the user can transfer a team to another member.
     */
    public function transferTeam(User $user, Team $team): bool
    {
        return $user->ownsTeam($team);
    }
PHP;

        $after = <<<'PHP'
    /**
     * Determine whether the user can remove team members.
     */
    public function removeTeamMember(User $user, Team $team): bool
    {
        return $user->ownsTeam($team);
    }
PHP;

        $this->appendToFile(
            app_path('Policies/TeamPolicy.php'),
            $after,
            PHP_EOL.$policy
        );
    }

    private function appendToFile(string $pathToFile, string $after, string $contents): void
    {
        if (! Str::contains($fileContents = file_get_contents($pathToFile), $contents)) {
            file_put_contents($pathToFile, str_replace(
                $after,
                $after.PHP_EOL.$contents,
                $fileContents
            ));
        }
    }

    /**
     * Returns the path to the correct test stubs.
     */
    private function getTestStubsPath(): string
    {
        return file_exists(base_path('tests/Pest.php')) || $this->option('pest')
            ? __DIR__.'/../../stubs/pest-tests'
            : __DIR__.'/../../stubs/tests';
    }

    /**
     * Remove Tailwind dark classes from the given files.
     */
    private function removeDarkClasses(Finder $finder): void
    {
        foreach ($finder as $file) {
            file_put_contents($file->getPathname(), preg_replace('/\sdark:[^\s"\']+/', '', $file->getContents()));
        }
    }

    /**
     * Execute the given commands using the given environment.
     */
    protected function runCommands(array $commands, array $env = []): Process
    {
        $process = Process::fromShellCommandline(implode(' && ', $commands), null, $env, null, null);

        $process->run(function ($type, $line) {
            $this->output->write('    '.$line);
        });

        return $process;
    }
}
