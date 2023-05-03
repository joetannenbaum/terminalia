<?php

namespace InteractiveConsole\PromptTypes;

use Illuminate\Support\Collection;
use InteractiveConsole\Enums\BlockSymbols;
use InteractiveConsole\Enums\ControlSequence;
use InteractiveConsole\Enums\TerminalEvent;
use InteractiveConsole\Helpers\IsCancelable;
use InteractiveConsole\Helpers\ListensForInput;
use InteractiveConsole\Helpers\UsesTheCursor;
use InteractiveConsole\Helpers\ValidatesInput;
use InteractiveConsole\Helpers\WritesOutput;
use Symfony\Component\Console\Style\OutputStyle;

class Choices
{
    use ListensForInput, WritesOutput, ValidatesInput, IsCancelable, UsesTheCursor;

    protected string $query = '';

    protected bool $filtering = false;

    protected Collection $selected;

    protected int $focusedIndex = 0;

    protected ?string $errorMessage = null;

    protected bool $multiple = false;

    protected bool $filterable = false;

    protected array $defaultCursorPosition;

    public function __construct(
        protected OutputStyle $output,
        protected string $question,
        protected Collection $items,
        protected string|array $default = [],
        protected $inputStream = null,
    ) {
        $this->inputStream = $this->inputStream ?? fopen('php://stdin', 'rb');
        $this->initCursor();
        $this->selected = collect(is_array($default) ? $default : [$default]);
        $this->registerStyles();
    }

    public function setMultiple(bool $multiple = true): self
    {
        $this->multiple = $multiple;

        return $this;
    }

    public function setFilterable(bool $filterable = true): self
    {
        $this->filterable = $filterable;

        return $this;
    }

    public function prompt(): mixed
    {
        $this->cursor->hide();

        $this->writeTitleBlock($this->question);

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
        $this->writeTitleBlock($this->question);

        if ($this->selected->count() > 0) {
            $this->writeBlock(
                $this->dim(
                    $this->selected->map(fn ($i) => $this->items->get($i))->join(', '),
                ),
            );
        }

        $this->writeCanceledBlock($message);

        exit;
    }

    protected function registerListeners()
    {
        $listener = $this->inputListener();

        $filterListener = $this->inputListener();

        $filterListener->afterKeyPress($this->writeChoices(...));

        $filterListener->on('*', function (string $text) {
            $this->query .= $text;
        });

        $filterListener->on(
            TerminalEvent::ESCAPE,
            function () use ($filterListener, $listener) {
                $this->filtering = false;
                $this->writeChoices();
                $filterListener->stop();
                $listener->listen();
            }
        );

        $filterListener->setStopOnEnter(false);

        $filterListener->on(
            "\n",
            function () use ($filterListener, $listener) {
                if (trim($this->query) === '') {
                    $this->filtering = false;
                    $this->writeChoices();
                    $filterListener->stop();
                    $listener->listen();
                } else {
                    $this->setSelected();
                    $this->query = '';
                }
            }
        );

        $filterListener->on(' ', function () {
            $this->setSelected();
        });

        $filterListener->on([ControlSequence::UP, ControlSequence::LEFT], function () {
            $this->setRelativeFocusedIndex(-1);
        });

        $filterListener->on([ControlSequence::DOWN, ControlSequence::RIGHT], function () {
            $this->setRelativeFocusedIndex(1);
        });

        $filterListener->on(ControlSequence::BACKSPACE, function () {
            $this->query = substr($this->query, 0, -1);
        });

        $listener->on('/', function () use ($listener, $filterListener) {
            $this->filtering = true;
            $this->query = '';
            $this->writeChoices();
            $listener->stop();
            $filterListener->listen();
        });

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

        $listener->afterKeyPress($this->writeChoices(...));

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
        if ($this->focusedIndex === -1) {
            // We are filtering and there are no valid choices,
            // so we don't want to change anything.
            return;
        }

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
        $this->cursor->hide();
        $this->clearContentAfterTitle();

        if ($this->filtering) {
            if (!$this->hasBookmark('filterQuery')) {
                $this->bookmark('filterQuery');
            }

            $this->writeBlock($this->active('>') . " {$this->query}");
        }

        [$selectedSymbol, $unselectedSymbol] = $this->multiple
            ? [BlockSymbols::CHECKBOX_SELECTED, BlockSymbols::CHECKBOX_UNSELECTED]
            : [BlockSymbols::RADIO_SELECTED, BlockSymbols::RADIO_UNSELECTED];

        $currentItems = $this->items->filter(
            fn ($item) => str_contains(strtolower($item), strtolower($this->query))
        );

        if (!$currentItems->keys()->contains($this->focusedIndex)) {
            $this->focusedIndex = $currentItems->keys()->first() ?? -1;
        }

        $currentItems->each(
            function ($item, $i) use ($selectedSymbol, $unselectedSymbol) {
                $checked = $this->selected->contains($i) ? $selectedSymbol : $unselectedSymbol;
                $display = $this->focusedIndex === $i ? $this->focused($item) : $this->dim($item);

                $radio = $this->selected->contains($i)
                    ? $this->checkboxSelected($checked->symbol())
                    : $this->checkboxUnselected($checked->symbol());

                $this->writeBlock("{$radio} {$display}");
            }
        );

        if ($this->filterable) {
            $this->writeBlock();
            if ($this->filtering) {
                $this->writeBlock($this->keyboardShortcutHelp('esc', 'stop filtering'));
            } else {
                $this->writeBlock($this->keyboardShortcutHelp('/', 'filter'));
            }
        }

        $this->writeEndBlock($this->errorMessage ?? '');

        if ($this->filtering) {
            $this->moveToBookmark('filterQuery');
            $this->cursor->moveToColumn(mb_strlen("> {$this->query}") + 3);
            $this->cursor->show();
        }
    }
}
