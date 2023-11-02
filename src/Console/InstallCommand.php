<?php

namespace JoelButcher\JetstreamTeamTransfer\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use JoelButcher\JetstreamTeamTransfer\Enums\InstallStack;
use Laravel\Jetstream\Jetstream;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;
use function Laravel\Prompts\alert;
use function Laravel\Prompts\error;
use function Laravel\Prompts\select;

class InstallCommand extends Command implements PromptsForMissingInput
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jetstream-team-transfer:install {stack : The development stack that should be used (Livewire, Inertia)}
                            {--pest : Indicates if Pest should be installed}';

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
            alert('Jetstream is not installed.');

            \Laravel\Prompts\info('Please run the following commands to install Jetstream:');
            \Laravel\Prompts\info('composer require laravel/jetstream');
            \Laravel\Prompts\info('php artisan jetstream:install <stack> <options>');

            return self::FAILURE;
        }

        if (! Jetstream::hasTeamFeatures()) {
            error('This package requires the "teams" feature for Jetstream to be enabled.');

            return self::FAILURE;
        }

        $callback = match (InstallStack::from(config('jetstream.stack'))) {
            InstallStack::Livewire => function () {
                $this->installLivewireStack();

                return self::SUCCESS;
            },
            InstallStack::Inertia => function () {
                $this->components->error(
                    string: 'Sorry, support for Inertia is not ready yet.'
                );

                return self::FAILURE;
            },
        };

        return $callback();
    }

    /**
     * Prompt for missing input arguments using the returned questions.
     */
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'stack' => function () {
                if ($this->isJetstreamInstalled()) {
                    return config('jetstream.stack');
                }

                return select(
                    label: 'Which stack would you like to use?',
                    options: collect(InstallStack::cases())->mapWithKeys(
                        fn(InstallStack $stack) => [$stack->value => $stack->label()],
                    ),
                    default: 'inertia',
                );
            }
        ];
    }

    protected function afterPromptingForMissingArguments(InputInterface $input, OutputInterface $output): void
    {
        $input->setOption('pest', select(
                label: 'Which testing framework do you prefer?',
                options: ['PHPUnit', 'Pest'],
                default: $this->isUsingPest() ? 'Pest' : 'PHPUnit'
            ) === 'Pest');
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
        copy($stubs.'/Feature/TransferTeamTest.php', base_path('tests/Feature/TransferTeamTest.php'));

        $this->installModelTrait();
        $this->installPolicy();

        if (! $this->hasFilesWithDarkMode()) {
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
        return $this->option('pest')
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

    /**
     * Determine if Laravel Jetstream is installed.
     */
    private function isJetstreamInstalled(): bool
    {
        return file_exists(config_path('jetstream.php'));
    }

    protected function isUsingPest(): bool
    {
        return file_exists(base_path('tests/Pest.php'));
    }

    private function hasFilesWithDarkMode(): bool
    {
        // Find all the files published by the starter kit that have dark mode class utilities,
        // ignoring any and all files that will have been overwritten by Socialstream
        $files = (new Finder)
            ->in([resource_path('views'), resource_path('js')])
            ->name(['*.blade.php', '*.vue'])
            ->notPath(['Pages/Welcome.vue', 'Pages/Welcome.vue'])
            ->contains('/\sdark:[^\s"\']+/');

        return $files->count() > 0;
    }

}
