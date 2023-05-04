<?php

namespace InteractiveConsole\Helpers;

class Stty
{
    protected string $sttyMode;

    public function __construct()
    {
        $this->sttyMode = shell_exec('stty -g');
    }

    public function restore(): void
    {
        shell_exec("stty {$this->sttyMode}");
    }

    public function disableEcho(): void
    {
        shell_exec('stty -icanon -echo');
    }

    public function __destruct()
    {
        $this->restore();
    }
}
