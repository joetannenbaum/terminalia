<?php

namespace InteractiveConsole\Helpers;

use InteractiveConsole\Enums\BlockSymbols;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

trait WritesOutput
{
    protected $lines = 0;

    protected function writeLine(string $text): void
    {
        $this->output->writeln($text);
        $this->lines++;
    }

    protected function moveCursorToStart(): void
    {
        $this->cursor->moveUp($this->lines);
        $this->lines = 0;
    }

    protected function clearCurrentOutput(): void
    {
        $this->moveCursorToStart();
        $this->cursor->clearOutput();
    }

    protected function clearContentAfterQuestion(): void
    {
        if ($this->lines <= 2) {
            return;
        }

        $this->cursor->moveUp($this->lines - 2);
        $this->lines = 2;
        $this->cursor->clearOutput();
    }

    protected function hasError(): bool
    {
        return isset($this->errorMessage) && $this->errorMessage;
    }

    protected function writeInactiveBlock(
        string $text,
        BlockSymbols $borderSymbol = BlockSymbols::LINE
    ): void {
        $this->writeLine(
            $this->wrapInTag($borderSymbol->value, 'unfocused') . ' ' . $text
        );
    }

    protected function writeBlock(string $text = '', BlockSymbols $borderSymbol = BlockSymbols::LINE): void
    {
        $tag = $this->getStyleTagForBlockLine();

        $this->writeLine("<{$tag}>{$borderSymbol->value}</{$tag}> {$text}");
    }

    protected function getStyleTagForBlockLine(): string
    {
        if ($this->canceled) {
            return 'unfocused';
        }

        if ($this->hasError()) {
            return 'warning';
        }

        return 'info';
    }

    protected function getStyledSymbolForQuestionBlock(): string
    {
        if ($this->canceled) {
            return $this->wrapInTag(BlockSymbols::CANCELED->value, 'canceled');
        }

        if ($this->hasError()) {
            return $this->wrapInTag(BlockSymbols::WARNING->value, 'warning');
        }

        return $this->wrapInTag(BlockSymbols::ACTIVE->value, 'info');
    }

    protected function writeEndBlock(string $text): void
    {
        $this->writeBlock($text, BlockSymbols::END);
    }

    protected function writeQuestionBlock(): void
    {
        $this->writeLine($this->wrapInTag(BlockSymbols::LINE->value, 'unfocused'));

        $symbol = $this->getStyledSymbolForQuestionBlock();

        $this->writeLine($symbol . ' ' . $this->question);
    }

    protected function writeAnsweredBlock(string $answer): void
    {
        $this->writeLine($this->wrapInTag(BlockSymbols::LINE->value, 'unfocused'));
        $this->writeLine($this->wrapInTag(BlockSymbols::ANSWERED->value, 'info') . ' ' . $this->question);
        $this->writeLine($this->wrapInTag(BlockSymbols::LINE->value . ' ' . $answer, 'unfocused'));
    }

    protected function wrapInTag(string $text, string $tag): string
    {
        return "<{$tag}>{$text}</{$tag}>";
    }

    protected function registerStyles(): void
    {
        collect([
            'focused'          => new OutputFormatterStyle('black', null, ['bold']),
            'unfocused'        => 'gray',
            'radio_selected'   => 'green',
            'radio_unselected' => 'gray',
            'help_key'         => 'white',
            'help_value'       => 'gray',
            'warning'          => 'yellow',
            'canceled'         => 'red',
            'intro'            => new OutputFormatterStyle('black', 'green'),
        ])->each(function ($value, $key) {
            $style = (is_string($value)) ? new OutputFormatterStyle($value) : $value;

            if (!$this->output->getFormatter()->hasStyle($key)) {
                $this->output->getFormatter()->setStyle($key, $style);
            }
        });
    }
}
