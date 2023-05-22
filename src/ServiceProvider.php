<?php

namespace JoelButcher\JetstreamTeamTransfer;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;
use JoelButcher\JetstreamTeamTransfer\Http\Livewire\TeamTransferForm;
use Laravel\Jetstream\Jetstream;
use Livewire\Livewire;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->afterResolving(BladeCompiler::class, function () {
            if (Jetstream::hasTeamFeatures()) {
                Livewire::component('teams.team-transfer-form', TeamTransferForm::class);
            }
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'jetstream-team-transfer');

        $this->configureCommands();
    }

    /**
     * Configure the commands offered by the application.
     */
    protected function configureCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            Console\InstallCommand::class,
        ]);
    }
}
