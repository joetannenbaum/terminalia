<?php

namespace InteractiveConsole\PromptTypes;

use InteractiveConsole\Enums\BlockSymbols;
use InteractiveConsole\Helpers\WritesOutput;
use Symfony\Component\Console\Style\OutputStyle;

class Note
{
    use WritesOutput;

    protected bool $answer;

    protected int $lineWidth;

    protected int $horizontalPadding = 2;

    public function __construct(
        protected OutputStyle $output,
        protected string|array $text,
        protected string $title,
    ) {
        if (is_array($this->text)) {
            $this->text = implode(PHP_EOL . PHP_EOL, $this->text);
        }

        $this->registerStyles();
    }

    public function display(): void
    {
        // TODO: Base this on terminal width?
        $lines = collect(explode(PHP_EOL, wordwrap($this->text, 60)));

        $max = max($lines->max(fn ($line) => mb_strlen($line)), mb_strlen($this->title));

        $this->lineWidth = $max + 2 + ($this->horizontalPadding * 2); // Max line + border lines + horizontal padding

        $title = $this->title === '' ? ' ' : $this->padding() . $this->title . $this->padding();

        $this->writeInactiveBlock();

        $this->writeLine(
            $this->wrapInTag(BlockSymbols::ANSWERED->value, 'info') .
                $title .
                $this->wrapInTag(
                    str_repeat(
                        BlockSymbols::HORIZONTAL->value,
                        $this->lineWidth - mb_strlen($title),
                    ) . BlockSymbols::CORNER_TOP_RIGHT->value,
                    'unfocused'
                ),
        );

        $this->writeBlankLine(2);

        $lines->each(
            fn ($line) => $this->writeInactiveBlock(
                $this->wrapInTag(
                    str_repeat(' ', $this->horizontalPadding - 1) // Account for the border line to align with title
                        . $line
                        . str_repeat(
                            ' ',
                            $this->lineWidth - mb_strlen($line) - $this->horizontalPadding,
                        ) .
                        BlockSymbols::LINE->value,
                    'unfocused'
                ),
            ),
        );

        $this->writeBlankLine(2);

        $this->writeLine(
            $this->wrapInTag(
                BlockSymbols::CONNECT_LEFT->value
                    . str_repeat(BlockSymbols::HORIZONTAL->value, $this->lineWidth)
                    . BlockSymbols::CORNER_BOTTOM_RIGHT->value,
                'unfocused'
            )
        );
    }

    protected function padding()
    {
        return str_repeat(' ', $this->horizontalPadding);
    }

    protected function writeBlankLine($lines = 1)
    {
        foreach (range(1, $lines) as $line) {
            $this->writeInactiveBlock(
                $this->wrapInTag(
                    str_repeat(' ', $this->lineWidth - 1) . BlockSymbols::LINE->value,
                    'unfocused'
                ),
            );
        }
    }
}
