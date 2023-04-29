<?php

namespace InteractiveConsole\Helpers;

use Symfony\Component\Console\Style\OutputStyle;

class OutroHelper
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
        $this->writeInactiveBlock('');
        $this->writeInactiveBlock($this->text, BlockSymbols::END);
        $this->output->newLine();
    }
}
