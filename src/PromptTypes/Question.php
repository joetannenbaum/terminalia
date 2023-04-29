<?php

namespace InteractiveConsole\PromptTypes;

use InteractiveConsole\Enums\ControlSequence;
use InteractiveConsole\Helpers\IsCancelable;
use InteractiveConsole\Helpers\ListensForInput;
use InteractiveConsole\Helpers\ValidatesInput;
use InteractiveConsole\Helpers\WritesOutput;
use Symfony\Component\Console\Cursor;
use Symfony\Component\Console\Style\OutputStyle;

class Question
{
    use ListensForInput, WritesOutput, ValidatesInput, IsCancelable;

    protected Cursor $cursor;

    protected ?string $errorMessage = null;

    protected string $answer = '';

    public function __construct(
        protected OutputStyle $output,
        protected $inputStream,
        protected string $question,
        protected ?string $default = null,
        protected bool $hidden = false,
    ) {
        $this->cursor = new Cursor($this->output, $this->inputStream);
        $this->registerStyles();

        if ($default !== null) {
            $this->answer = $default;
        }
    }

    public function prompt(): string
    {
        $this->cursor->hide();

        $this->writeQuestionBlock($this->question);
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
        $this->writeQuestionBlock();

        if ($this->getAnswerDisplay() !== '') {
            $this->writeBlock(
                $this->wrapInTag($this->getAnswerDisplay(), 'unfocused'),
            );
        }

        $this->writeCanceledBlock($message);

        exit();
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
        $this->cursor->moveDown(2);
        $this->cursor->moveToColumn(0);

        $this->clearCurrentOutput();
    }

    protected function writeAnswer(): void
    {
        $this->cursor->moveToColumn(3);
        $this->cursor->clearLineAfter();
        $this->output->write($this->getAnswerDisplay());
    }
}
