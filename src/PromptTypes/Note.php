<?php

namespace Terminalia\PromptTypes;

use Symfony\Component\Console\Style\OutputStyle;
use Terminalia\Enums\BlockSymbols;
use Terminalia\Helpers\WritesOutput;

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

        $title = $this->title === '' ? ' ' : ' ' . $this->title . $this->padding();

        $this->writeInactiveBlock();

        $this->writeLine(
            $this->active(BlockSymbols::ANSWERED->symbol()) .
                $title .
                $this->dim(
                    str_repeat(
                        BlockSymbols::HORIZONTAL->symbol(),
                        $this->lineWidth - mb_strlen($title),
                    ) . BlockSymbols::CORNER_TOP_RIGHT->symbol(),
                ),
        );

        $this->writeBlankLine(2);

        $lines->each(
            fn ($line) => $this->writeInactiveBlock(
                $this->dim(
                    str_repeat(' ', $this->horizontalPadding - 1) // Account for the border line to align with title
                        . $line
                        . str_repeat(
                            ' ',
                            $this->lineWidth - mb_strlen($line) - $this->horizontalPadding,
                        ) .
                        BlockSymbols::LINE->symbol(),
                ),
            ),
        );

        $this->writeBlankLine(2);

        $this->writeLine(
            $this->dim(
                BlockSymbols::CONNECT_LEFT->symbol()
                    . str_repeat(BlockSymbols::HORIZONTAL->symbol(), $this->lineWidth)
                    . BlockSymbols::CORNER_BOTTOM_RIGHT->symbol(),
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
                $this->dim(
                    str_repeat(' ', $this->lineWidth - 1) . BlockSymbols::LINE->symbol(),
                ),
            );
        }
    }
}
