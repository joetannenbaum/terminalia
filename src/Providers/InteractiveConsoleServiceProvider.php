<?php

namespace InteractiveConsole\Providers;

use Illuminate\Console\Command;
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
        //
    }
}
