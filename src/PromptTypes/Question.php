<?php

namespace Terminalia\PromptTypes;

use Symfony\Component\Console\Style\OutputStyle;
use Terminalia\Enums\ControlSequence;
use Terminalia\Helpers\IsCancelable;
use Terminalia\Helpers\ListensForInput;
use Terminalia\Helpers\UsesTheCursor;
use Terminalia\Helpers\ValidatesInput;
use Terminalia\Helpers\WritesOutput;

class Question
{
    use ListensForInput, WritesOutput, ValidatesInput, IsCancelable, UsesTheCursor;

    protected ?string $errorMessage = null;

    protected string $answer = '';

    public function __construct(
        protected OutputStyle $output,
        protected string $question,
        protected ?string $default = null,
        protected bool $hidden = false,
        protected $inputStream = null,
    ) {
        $this->inputStream = $this->inputStream ?? fopen('php://stdin', 'rb');
        $this->initCursor();
        $this->registerStyles();

        if ($default !== null) {
            $this->answer = $default;
        }
    }

    public function prompt(): string
    {
        $this->cursor->hide();

        $this->writeTitleBlock($this->question);
        $this->writeBlock($this->answer);
        $this->writeEndBlock($this->errorMessage ?? '');

        // Position the cursor so they can start typing in the correct place
        $this->cursor->moveUp(2);
        $this->cursor->moveToColumn(3 + strlen($this->answer));

        $this->cursor->show();

        $this->listenForInput();

        if ($errorMessage = $this->validate($this->answer)) {
            $this->errorMessage = $errorMessage;

            $this->clearQuestion();

            return $this->prompt();
        }

        $this->clearQuestion();
        $this->writeAnsweredBlock($this->getAnswerDisplay());
        $this->cursor->show();

        return $this->answer;
    }

    public function onCancel(string $message = 'Canceled'): void
    {
        $this->canceled = true;

        $this->clearQuestion();
        $this->writeTitleBlock($this->question);

        if ($this->getAnswerDisplay() !== '') {
            $this->writeBlock(
                $this->dim($this->getAnswerDisplay()),
            );
        }

        $this->writeCanceledBlock($message);

        exit;
    }

    protected function getAnswerDisplay(): string
    {
        if ($this->hidden) {
            return str_repeat('•', strlen($this->answer));
        }

        return $this->answer;
    }

    protected function listenForInput(): void
    {
        $listener = $this->inputListener();

        $listener->afterKeyPress($this->writeAnswer(...));

        $listener->on(ControlSequence::BACKSPACE, function () {
            $this->answer = substr($this->answer, 0, -1);
        });

        // TODO: Handle for arrow keys (left and right) to position cursor and edit answer

        $listener->on('*', function (string $text) {
            $this->answer .= $text;
        });

        $listener->listen();
    }

    protected function clearQuestion(): void
    {
        $this->clearCurrentOutput();
    }

    protected function writeAnswer(): void
    {
        $this->cursor->moveToColumn(3);
        $this->cursor->clearLineAfter();
        $this->output->write($this->getAnswerDisplay());
    }
}
