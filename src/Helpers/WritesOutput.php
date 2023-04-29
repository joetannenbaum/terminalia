<?php

namespace InteractiveConsole\Helpers;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;

trait WritesOutput
{
    protected $lines = 0;

    // TODO: Are these silly at this point? Just use tag
    public function dim(string $text)
    {
        return "<unfocused>{$text}</unfocused>";
    }

    public function active(string $text)
    {
        return "<info>{$text}</info>";
    }

    public function warning(string $text)
    {
        return "<warning>{$text}</warning>";
    }

    protected function writeLine(string $text)
    {
        $this->output->writeln($text);
        $this->lines++;
    }

    protected function moveCursorToStart()
    {
        $this->cursor->moveUp($this->lines);
        $this->lines = 0;
    }

    protected function clearCurrentOutput()
    {
        $this->moveCursorToStart();
        $this->cursor->clearOutput();
    }

    protected function clearContentAfterQuestion()
    {
        if ($this->lines <= 2) {
            return;
        }

        $this->cursor->moveUp($this->lines - 2);
        $this->lines = 2;
        $this->cursor->clearOutput();
    }

    protected function hasError()
    {
        return isset($this->errorMessage) && $this->errorMessage;
    }

    protected function writeInactiveBlock(
        string $text,
        BlockSymbols $borderSymbol = BlockSymbols::LINE
    ) {
        $this->writeLine($this->dim($borderSymbol->value) . ' ' . $text);
    }

    protected function writeBlock(string $text = '', BlockSymbols $borderSymbol = BlockSymbols::LINE)
    {
        $tag = $this->getStyleTagForBlockLine();

        $this->writeLine("<{$tag}>{$borderSymbol->value}</{$tag}> {$text}");
    }

    protected function getStyleTagForBlockLine()
    {
        if ($this->canceled) {
            return 'unfocused';
        }

        if ($this->hasError()) {
            return 'warning';
        }

        return 'info';
    }

    protected function getStyledSymbolForQuestionBlock()
    {
        if ($this->canceled) {
            return '<canceled>' . BlockSymbols::CANCELED->value . '</canceled>';
        }

        if ($this->hasError()) {
            return $this->warning(BlockSymbols::WARNING->value);
        }

        return $this->active(BlockSymbols::ACTIVE->value);
    }

    protected function writeEndBlock(string $text)
    {
        $this->writeBlock($text, BlockSymbols::END);
    }

    protected function writeQuestionBlock()
    {
        $this->writeLine($this->dim(BlockSymbols::LINE->value));

        $symbol = $this->getStyledSymbolForQuestionBlock();

        $this->writeLine($symbol . ' ' . $this->question);
    }

    protected function writeAnsweredBlock(string $answer)
    {
        $this->writeLine($this->dim(BlockSymbols::LINE->value . ' '));
        $this->writeLine($this->active(BlockSymbols::ANSWERED->value) . ' ' . $this->question);
        $this->writeLine($this->dim(BlockSymbols::LINE->value . ' ' . $answer));
    }

    protected function wrapInTag(string $text, string $tag)
    {
        return "<{$tag}>{$text}</{$tag}>";
    }

    protected function registerStyles()
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
