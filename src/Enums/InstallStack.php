<?php

namespace JoelButcher\JetstreamTeamTransfer\Enums;

enum InstallStack: string
{
    case Livewire = 'livewire';
    case Inertia = 'inertia';

    public function label(): string
    {
        return match ($this) {
            self::Livewire => 'Livewire',
            self::Inertia => 'Vue with Inertia',
        };
    }
}
