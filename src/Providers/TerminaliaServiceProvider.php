<?php

namespace Terminalia\Providers;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Terminalia\Mixins\Terminalia;

class TerminaliaServiceProvider extends ServiceProvider
{
    public function register()
    {
        Command::mixin(new Terminalia);
    }

    public function boot()
    {
        if (!Collection::hasMacro('loop')) {
            Collection::macro('loop', fn (int $i) => $this->get($i % $this->count()));
        }
    }
}
