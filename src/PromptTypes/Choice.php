<?php

namespace Terminalia\PromptTypes;

use Illuminate\Support\Collection;
use Symfony\Component\Console\Output\OutputInterface;
use Terminalia\Enums\BlockSymbols;
use Terminalia\Enums\ControlSequence;
use Terminalia\Enums\TerminalEvent;
use Terminalia\Helpers\Choices;
use Terminalia\Helpers\InputListener;
use Terminalia\Helpers\IsCancelable;
use Terminalia\Helpers\ListensForInput;
use Terminalia\Helpers\UsesTheCursor;
use Terminalia\Helpers\ValidatesInput;
use Terminalia\Helpers\WritesOutput;

class Choice
{
    use ListensForInput, WritesOutput, ValidatesInput, IsCancelable, UsesTheCursor;

    protected string $query = '';

    protected bool $filtering = false;

    protected Collection $selected;

    protected Collection $displayedItems;

    protected int $focusedIndex = 0;

    protected ?string $errorMessage = null;

    protected bool $multiple = false;

    protected bool $filterable = false;

    protected array $defaultCursorPosition;

    public function __construct(
        protected OutputInterface $output,
        protected string $question,
        protected Choices $items,
        protected string|iterable $default = [],
        protected $inputStream = null,
    ) {
        $this->inputStream = $this->inputStream ?? fopen('php://stdin', 'rb');
        $this->initCursor();
        $this->registerStyles();

        $this->displayedItems = $this->items->choices();

        $this->selected = $this->items->getSelectedFromDefault($default);
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

        $this->clearCurrentOutput();
        $this->writeAnsweredBlock(
            $this->selected->map(fn ($i) => $this->items[$i])->join(', ')
        );

        $this->cursor->show();

        $selectedValues = $this->items->value($this->selected);

        if (!$this->multiple) {
            return $selectedValues->first();
        }

        return $this->items->returnAsArray() ? $selectedValues->toArray() : $selectedValues;
    }

    public function onCancel(string $message = 'Canceled'): void
    {
        $this->canceled = true;

        $this->clearCurrentOutput();
        $this->writeTitleBlock($this->question);

        if ($this->selected->count() > 0) {
            $this->writeBlock(
                $this->dim(
                    $this->selected->map(fn ($i) => $this->items->choices()->get($i))->join(', '),
                ),
            );
        }

        $this->writeCanceledBlock($message);

        exit;
    }

    protected function registerListeners(): void
    {
        $listener = $this->inputListener();

        $listener->on(' ', function () {
            $this->setSelected();
        });

        if ($this->filterable) {
            $this->registerFilterListeners($listener);
        }

        $this->registerCommonListeners($listener);

        $listener->listen();
    }

    protected function setQuery(string $query): void
    {
        $this->query = $query;
        $this->setDisplayedItems();
    }

    protected function setDisplayedItems(): void
    {
        if ($this->query === '') {
            $this->displayedItems = $this->items->choices();

            return;
        }

        $this->displayedItems = $this->items->choices()->filter(
            fn ($item) => str_contains(strtolower($item), strtolower($this->query))
        );
    }

    protected function registerCommonListeners(InputListener $listener): void
    {
        $listener->on([ControlSequence::UP, ControlSequence::LEFT], function () {
            $this->setRelativeFocusedIndex(-1);
        });

        $listener->on([ControlSequence::DOWN, ControlSequence::RIGHT], function () {
            $this->setRelativeFocusedIndex(1);
        });

        $listener->on(TerminalEvent::EXIT, function () {
            $this->cursor->show();
        });

        $listener->afterKeyPress($this->writeChoices(...));
    }

    protected function registerFilterListeners(InputListener $defaultListener): void
    {
        $filterListener = $this->inputListener();

        $filterListener->on('*', function (string $text) {
            $this->setQuery($this->query . $text);
        });

        $filterListener->on(
            TerminalEvent::ESCAPE,
            function () use ($filterListener, $defaultListener) {
                $this->filtering = false;
                $this->writeChoices();
                $filterListener->stop();
                $defaultListener->listen();
            }
        );

        $filterListener->setStopOnEnter(false);

        $filterListener->on(
            "\n",
            function () use ($filterListener, $defaultListener) {
                if (trim($this->query) === '') {
                    $this->filtering = false;
                    $this->writeChoices();
                    $filterListener->stop();
                    $defaultListener->listen();
                } else {
                    $this->setSelected();
                    $this->setQuery('');
                }
            }
        );

        $filterListener->on(ControlSequence::BACKSPACE, function () {
            $this->setQuery(substr($this->query, 0, -1));
        });

        $defaultListener->on('/', function () use ($defaultListener, $filterListener) {
            $this->filtering = true;
            $this->setQuery('');
            $this->writeChoices();
            $defaultListener->stop();
            $filterListener->listen();
        });

        $this->registerCommonListeners($filterListener);
    }

    protected function setRelativeFocusedIndex(int $offset): void
    {
        $this->focusedIndex = $this->getFocusedIndexFromOffset($offset);
    }

    protected function getFocusedIndexFromOffset(int $offset): int
    {
        $newIndex = $this->focusedIndex + $offset;

        if ($this->displayedItems->count() === 0) {
            // If we aren't displaying anything, just cut out early
            return -1;
        }

        if ($this->displayedItems->count() === 1) {
            // No need to change anything, we already have the right focused item
            return $this->focusedIndex;
        }

        if ($newIndex >= $this->displayedItems->count()) {
            return $this->displayedItems->keys()->first() ?? -1;
        }

        if ($newIndex < 0) {
            return $this->displayedItems->keys()->last() ?? -1;
        }

        if ($this->displayedItems->keys()->contains($newIndex)) {
            return $newIndex;
        }

        $newKey = $this->displayedItems->keys()->first(fn ($i) => $offset > 0 ? $i > $this->focusedIndex : $i < $this->focusedIndex);

        if ($newKey !== null) {
            return $newKey;
        }

        if ($offset < 0) {
            return $this->displayedItems->keys()->last();
        }

        return $this->displayedItems->keys()->first();
    }

    protected function setSelected(): void
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

    protected function writeChoices(): void
    {
        $this->cursor->hide();
        $this->clearContentAfterTitle();

        if ($this->filtering) {
            if (!$this->hasBookmark('filterQuery')) {
                $this->bookmark('filterQuery');
            }

            $this->writeBlock($this->active('>') . " {$this->query}");

            if ($this->displayedItems->count() === 0) {
                $this->writeBlock($this->dim('No results'));
            }
        }

        [$selectedSymbol, $unselectedSymbol] = $this->multiple
            ? [BlockSymbols::CHECKBOX_SELECTED, BlockSymbols::CHECKBOX_UNSELECTED]
            : [BlockSymbols::RADIO_SELECTED, BlockSymbols::RADIO_UNSELECTED];

        if (!$this->displayedItems->keys()->contains($this->focusedIndex)) {
            $this->focusedIndex = $this->displayedItems->keys()->first() ?? -1;
        }

        $this->displayedItems->each(
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
            // Spacer block
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
