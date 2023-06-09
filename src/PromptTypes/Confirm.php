<?php

namespace Terminalia\PromptTypes;

use Symfony\Component\Console\Output\OutputInterface;
use Terminalia\Enums\BlockSymbols;
use Terminalia\Enums\ControlSequence;
use Terminalia\Enums\TerminalEvent;
use Terminalia\Helpers\IsCancelable;
use Terminalia\Helpers\ListensForInput;
use Terminalia\Helpers\UsesTheCursor;
use Terminalia\Helpers\WritesOutput;

class Confirm
{
    use ListensForInput, WritesOutput, IsCancelable, UsesTheCursor;

    protected bool $answer;

    public function __construct(
        protected OutputInterface $output,
        protected string $question,
        protected bool $default = false,
        protected $inputStream = null,
    ) {
        $this->inputStream = $this->inputStream ?? fopen('php://stdin', 'rb');
        $this->initCursor();
        $this->answer = $default;
        $this->registerStyles();
    }

    public function prompt(): bool
    {
        $this->cursor->hide();

        $this->writeTitleBlock($this->question);

        $this->writeChoices();

        $this->registerListeners();

        $this->clearCurrentOutput();

        $this->writeAnsweredBlock($this->answer ? 'Yes' : 'No');

        $this->cursor->show();

        return $this->answer;
    }

    public function onCancel(string $message = 'Canceled'): void
    {
        $this->canceled = true;

        $this->clearCurrentOutput();
        $this->writeTitleBlock($this->question);
        $this->writeBlock($this->dim($this->answer ? 'Yes' : 'No'));
        $this->writeCanceledBlock($message);

        exit;
    }

    protected function registerListeners(): void
    {
        $listener = $this->inputListener();

        $listener->afterKeyPress($this->writeChoices(...));

        $listener->on([ControlSequence::UP, ControlSequence::LEFT, ControlSequence::DOWN, ControlSequence::RIGHT], function () {
            $this->answer = !$this->answer;
        });

        $listener->on(TerminalEvent::EXIT, function () {
            $this->cursor->show();
        });

        $listener->listen();
    }

    protected function writeChoices(): void
    {
        $this->clearContentAfterTitle();

        $index = $this->answer ? 0 : 1;

        $result = collect(['Yes', 'No'])->map(function ($item, $i) use ($index) {
            if ($index === $i) {
                return $this->checkboxSelected(BlockSymbols::RADIO_SELECTED->symbol()) . ' ' . $this->focused($item);
            }

            return $this->checkboxUnselected(BlockSymbols::RADIO_UNSELECTED->symbol()) . ' ' . $this->dim($item);
        });

        $this->writeBlock($result->join(' / '));

        $this->writeEndBlock('');
    }
}
