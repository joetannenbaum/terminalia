<?php

namespace Terminalia\PromptTypes;

use Symfony\Component\Console\Style\OutputStyle;
use Terminalia\Enums\BlockSymbols;
use Terminalia\Helpers\WritesOutput;

class Output
{
    use WritesOutput;

    protected bool $answer;

    public function __construct(
        protected OutputStyle $output,
        protected string|array $text,
        protected string $tag,
    ) {
        if (is_array($this->text)) {
            $this->text = implode(PHP_EOL . PHP_EOL, $this->text);
        }

        $this->registerStyles();
    }

    public function display(): void
    {
        $lines = collect(explode(PHP_EOL, wordwrap($this->text, 60)));

        $blockSymbol = match ($this->tag) {
            'info'    => BlockSymbols::ANSWERED,
            'comment' => BlockSymbols::RADIO_SELECTED,
            'warning' => BlockSymbols::WARNING,
            'error'   => BlockSymbols::CANCELED,
            default   => BlockSymbols::ANSWERED,
        };

        $tag = match ($this->tag) {
            'error' => 'canceled',
            default => $this->tag
        };

        $this->writeInactiveBlock('');
        $this->writeLine(
            $this->wrapInTag($blockSymbol->symbol() . ' ' . $lines->shift(), $tag)
        );
        $lines->each(fn ($line) => $this->writeInactiveBlock($this->wrapInTag($line, $tag)));
    }
}
