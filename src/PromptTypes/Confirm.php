<?php

namespace InteractiveConsole\PromptTypes;

use InteractiveConsole\Enums\ControlSequence;
use InteractiveConsole\Enums\TerminalEvent;
use InteractiveConsole\Helpers\IsCancelable;
use InteractiveConsole\Helpers\ListensForInput;
use InteractiveConsole\Helpers\UsesTheCursor;
use InteractiveConsole\Helpers\WritesOutput;
use Symfony\Component\Console\Style\OutputStyle;

class Confirm
{
    use ListensForInput, WritesOutput, IsCancelable, UsesTheCursor;

    protected bool $answer;

    public function __construct(
        protected OutputStyle $output,
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
        $this->writeBlock($this->wrapInTag($this->answer ? 'Yes' : 'No', 'unfocused'));
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
        $this->clearContentAfterQuestion();

        $index = $this->answer ? 0 : 1;

        $result = collect(['Yes', 'No'])->map(function ($item, $i) use ($index) {
            $tag = $index === $i ? 'focused' : 'unfocused';
            $radioTag = $index === $i ? 'radio_selected' : 'radio_unselected';
            $checked = $index === $i ? '●' : '○';

            return $this->wrapInTag($checked, $radioTag) . ' ' . $this->wrapInTag($item, $tag);
        });

        $this->writeBlock($result->join(' / '));

        $this->writeEndBlock('');
    }
}
