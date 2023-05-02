<?php

namespace InteractiveConsole\Providers;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use InteractiveConsole\Mixins\InteractiveConsole;

class InteractiveConsoleServiceProvider extends ServiceProvider
{
    public function register()
    {
        Command::mixin(new InteractiveConsole);
    }

    public function boot()
    {
        if (!Collection::hasMacro('loop')) {
            Collection::macro('loop', fn (int $i) => $this->get($i % $this->count()));
        }
    }
}
