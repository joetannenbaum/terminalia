<?php

namespace Terminalia\PromptTypes;

use Symfony\Component\Console\Output\OutputInterface;
use Terminalia\Enums\BlockSymbols;
use Terminalia\Helpers\WritesOutput;

class Intro
{
    use WritesOutput;

    protected bool $answer;

    public function __construct(
        protected OutputInterface $output,
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
