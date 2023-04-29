<?php

namespace InteractiveConsole\PromptTypes;

use InteractiveConsole\Enums\BlockSymbols;
use InteractiveConsole\Helpers\WritesOutput;
use Symfony\Component\Console\Style\OutputStyle;

class Intro
{
    use WritesOutput;

    protected bool $answer;

    public function __construct(
        protected OutputStyle $output,
        protected string $text,
    ) {
        $this->registerStyles();
    }

    public function display(): void
    {
        $this->output->newLine();
        $this->writeInactiveBlock("<intro> {$this->text} </intro>", BlockSymbols::START);
    }
}
