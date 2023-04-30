<?php

namespace InteractiveConsole\PromptTypes;

use Illuminate\Support\Collection;
use InteractiveConsole\Enums\ControlSequence;
use InteractiveConsole\Enums\TerminalEvent;
use InteractiveConsole\Helpers\IsCancelable;
use InteractiveConsole\Helpers\ListensForInput;
use InteractiveConsole\Helpers\ValidatesInput;
use InteractiveConsole\Helpers\WritesOutput;
use Symfony\Component\Console\Cursor;
use Symfony\Component\Console\Style\OutputStyle;

class Choices
{
    use ListensForInput, WritesOutput, ValidatesInput, IsCancelable;

    protected string $query = '';

    protected Cursor $cursor;

    protected Collection $selected;

    protected int $focusedIndex = 0;

    protected ?string $errorMessage = null;

    protected bool $multiple = false;

    protected string|array $rules = [];

    public function __construct(
        protected OutputStyle $output,
        protected string $question,
        protected Collection $items,
        protected $inputStream = null,
        protected string|array $default = [],
    ) {
        $this->inputStream = $this->inputStream ?? fopen('php://stdin', 'rb');
        $this->cursor = new Cursor($this->output, $this->inputStream);
        $this->selected = collect(is_array($default) ? $default : [$default]);
        $this->registerStyles();
    }

    public function setMultiple(bool $multiple = true): self
    {
        $this->multiple = $multiple;

        return $this;
    }

    public function prompt(): mixed
    {
        $this->cursor->hide();

        $this->writeQuestionBlock($this->question);

        $this->writeChoices();

        $this->registerListeners();

        if ($errorMessage = $this->validate($this->selected)) {
            $this->errorMessage = $errorMessage;
            $this->clearCurrentOutput();

            return $this->prompt();
        }

        $selectedItems = $this->selected->map(fn ($i) => $this->items[$i]);

        $this->clearCurrentOutput();

        $this->writeAnsweredBlock($selectedItems->join(', '));

        $this->cursor->show();

        return $this->multiple ? $selectedItems->toArray() : $selectedItems->first();
    }

    public function onCancel(string $message = 'Canceled'): void
    {
        $this->canceled = true;

        $this->clearCurrentOutput();
        $this->writeQuestionBlock();

        if ($this->selected->count() > 0) {
            $this->writeBlock(
                $this->wrapInTag(
                    $this->selected->map(fn ($i) => $this->items->get($i))->join(', '),
                    'unfocused',
                ),
            );
        }

        $this->writeCanceledBlock($message);

        exit();
    }

    protected function registerListeners()
    {
        $listener = $this->inputListener();

        $listener->afterKeyPress($this->writeChoices(...));

        $listener->on([ControlSequence::UP, ControlSequence::LEFT], function () {
            $this->setRelativeFocusedIndex(-1);
        });

        $listener->on([ControlSequence::DOWN, ControlSequence::RIGHT], function () {
            $this->setRelativeFocusedIndex(1);
        });

        $listener->on(' ', function () {
            $this->setSelected();
        });

        $listener->on(TerminalEvent::EXIT, function () {
            $this->cursor->show();
        });

        $listener->listen();
    }

    protected function setRelativeFocusedIndex(int $offset)
    {
        $this->focusedIndex += $offset;

        if ($this->focusedIndex < 0) {
            $this->focusedIndex = $this->items->count() - 1;
        } elseif ($this->focusedIndex >= $this->items->count()) {
            $this->focusedIndex = 0;
        }
    }

    protected function setSelected()
    {
        if (!$this->multiple) {
            $this->selected = collect([$this->focusedIndex]);

            return;
        }

        if ($this->selected->contains($this->focusedIndex)) {
            $this->selected = $this->selected->reject(fn ($i) => $i === $this->focusedIndex);
        } else {
            $this->selected->push($this->focusedIndex);
        }
    }

    protected function writeChoices()
    {
        $this->clearContentAfterQuestion();

        $this->items->each(function ($item, $i) {
            $tag = $this->focusedIndex === $i ? 'focused' : 'unfocused';
            $radioTag = $this->selected->contains($i) ? 'radio_selected' : 'radio_unselected';

            if ($this->multiple) {
                $checked = $this->selected->contains($i) ? '■' : '□';
            } else {
                $checked = $this->selected->contains($i) ? '●' : '○';
            }

            $this->writeBlock($this->wrapInTag($checked, $radioTag) . ' ' . $this->wrapInTag($item, $tag));
        });

        $this->writeEndBlock($this->errorMessage ?? '');
    }
}
