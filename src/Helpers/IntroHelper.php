<?php

namespace InteractiveConsole\Helpers;

use Symfony\Component\Console\Style\OutputStyle;

class IntroHelper
{
    use WritesOutput;

    protected bool $answer;

    public function __construct(
        protected OutputStyle $output,
        protected string $text,
    ) {
        $this->registerStyles();
    }

    public function display()
    {
        $this->output->newLine();
        $this->writeInactiveBlock("<intro> {$this->text} </intro>", BlockSymbols::START);
    }
}
