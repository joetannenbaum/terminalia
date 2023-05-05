<?php

namespace Terminalia\Helpers;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Terminalia\Enums\BlockSymbols;

trait WritesOutput
{
    use UsesTheCursor;

    protected function writeLine(string $text): void
    {
        $this->output->writeln($text);
    }

    protected function hasError(): bool
    {
        return isset($this->errorMessage) && $this->errorMessage;
    }

    protected function writeInactiveBlock(
        string $text = '',
        BlockSymbols $borderSymbol = BlockSymbols::LINE
    ): void {
        $this->writeLine(
            $this->dim($borderSymbol->symbol()) . ' ' . $text
        );
    }

    protected function writeBlock(string $text = '', BlockSymbols $borderSymbol = BlockSymbols::LINE): void
    {
        $this->writeLine(
            $this->wrapInTag(
                $borderSymbol->symbol(),
                $this->getLineStyle(),
            ) . ' ' . $text
        );
    }

    protected function getLineStyle(): string
    {
        if ($this->canceled ?? false) {
            return 'dim';
        }

        if ($this->hasError()) {
            return 'warning';
        }

        return 'info';
    }

    protected function getStyledSymbolForTitleBlock(): string
    {
        if ($this->canceled) {
            return $this->canceled(BlockSymbols::CANCELED->symbol());
        }

        if ($this->hasError()) {
            return $this->warning(BlockSymbols::WARNING->symbol());
        }

        return $this->active(BlockSymbols::ACTIVE->symbol());
    }

    protected function writeEndBlock(string $text): void
    {
        $this->writeBlock($text, BlockSymbols::END);
    }

    protected function writeTitleBlock(string $text): void
    {
        // Write a leading line to separate the previous question
        $this->writeInactiveBlock();
        $this->writeLine($this->getStyledSymbolForTitleBlock() . ' ' . $text);
        $this->bookmark('endOfTitle');
    }

    protected function writeAnsweredBlock(string $answer): void
    {
        $this->writeInactiveBlock();
        $this->writeLine($this->active(BlockSymbols::ANSWERED->symbol()) . ' ' . $this->question);
        $this->writeLine($this->dim(BlockSymbols::LINE->symbol() . ' ' . $answer));
    }

    protected function registerStyles(): void
    {
        collect([
            'focused'             => new OutputFormatterStyle('black', null, ['bold']),
            'dim'                 => 'gray',
            'checkbox_selected'   => 'green',
            'checkbox_unselected' => 'gray',
            'help_key'            => 'white',
            'help_value'          => 'gray',
            'warning'             => 'yellow',
            'canceled'            => 'red',
            'intro'               => new OutputFormatterStyle('black', 'green'),
            'pending'             => 'magenta',
        ])
            ->filter(fn ($value, $key) => !$this->output->getFormatter()->hasStyle($key))
            ->each(function ($value, $key) {
                $style = (is_string($value)) ? new OutputFormatterStyle($value) : $value;

                $this->output->getFormatter()->setStyle($key, $style);
            });
    }

    protected function wrapInTag(string $text, string $tag): string
    {
        return "<{$tag}>{$text}</{$tag}>";
    }

    protected function keyboardShortcutHelp(string $key, string $value): string
    {
        return $this->helpKey($key) . ' ' . $this->helpValue($value);
    }

    protected function helpKey(string $text): string
    {
        return $this->wrapInTag($text, 'help_key');
    }

    protected function helpValue(string $text): string
    {
        return $this->wrapInTag($text, 'help_value');
    }

    protected function active(string $text): string
    {
        return $this->wrapInTag($text, 'info');
    }

    protected function dim(string $text): string
    {
        return $this->wrapInTag($text, 'dim');
    }

    protected function focused(string $text): string
    {
        return $this->wrapInTag($text, 'focused');
    }

    protected function checkboxSelected(string $text): string
    {
        return $this->wrapInTag($text, 'checkbox_selected');
    }

    protected function checkboxUnselected(string $text): string
    {
        return $this->wrapInTag($text, 'checkbox_unselected');
    }

    protected function canceled(string $text): string
    {
        return $this->wrapInTag($text, 'canceled');
    }

    protected function warning(string $text): string
    {
        return $this->wrapInTag($text, 'warning');
    }

    protected function pending(string $text): string
    {
        return $this->wrapInTag($text, 'pending');
    }
}
