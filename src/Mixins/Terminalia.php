<?php

namespace Terminalia\Mixins;

use Illuminate\Support\Collection;
use Terminalia\Helpers\Choices;
use Terminalia\PromptTypes\Choice;
use Terminalia\PromptTypes\Confirm;
use Terminalia\PromptTypes\Intro;
use Terminalia\PromptTypes\Note;
use Terminalia\PromptTypes\Output;
use Terminalia\PromptTypes\Outro;
use Terminalia\PromptTypes\ProgressBar;
use Terminalia\PromptTypes\Question;
use Terminalia\PromptTypes\Spinner;

class Terminalia
{
    public function termChoice()
    {
        return function (
            string $question,
            array|Choices|Collection $items,
            $default = null,
            $multiple = false,
            $rules = null,
            $filterable = false,
            $minFilterLength = 5,
        ) {
            $helper = new Choice(
                output: $this->output,
                question: $question,
                items: collect($items instanceof Choices ? [] : $items),
                returnAsArray: is_array($items) || ($items instanceof Choices && $items->returnAsArray()),
                choices: $items instanceof Choices ? $items : null,
                default: $default ?? [],
            );

            if ($rules) {
                $helper->setRules($rules);
            }

            if ($multiple) {
                $helper->setMultiple();
            }

            if ($filterable && count($items) >= $minFilterLength) {
                $helper->setFilterable();
            }

            $this->trap(SIGINT, fn () => $helper->onCancel());

            return $helper->prompt();
        };
    }

    public function termConfirm()
    {
        return function (string $question, $default = false) {
            $helper = new Confirm(
                output: $this->output,
                question: $question,
                default: $default,
            );

            $this->trap(SIGINT, fn () => $helper->onCancel());

            return $helper->prompt();
        };
    }

    public function termAsk()
    {
        return function (string $question, string $default = null, $rules = null, bool $hidden = false) {
            $helper = new Question(
                output: $this->output,
                question: $question,
                default: $default,
                hidden: $hidden,
            );

            if ($rules) {
                $helper->setRules($rules);
            }

            $this->trap(SIGINT, fn () => $helper->onCancel());

            return $helper->prompt();
        };
    }

    public function termSpinner()
    {
        return function (
            string $title,
            callable $task,
            string|callable $message = null,
            array $longProcessMessages = []
        ) {
            $helper = new Spinner(
                output: $this->output,
                title: $title,
                task: $task,
                message: $message,
                longProcessMessages: $longProcessMessages,
            );

            $this->trap(SIGINT, fn () => $helper->onCancel());

            $helper->spin();
        };
    }

    public function withTermProgressBar()
    {
        return function (
            iterable $items,
            callable $callback,
            ?string $title = null,
        ) {
            $progress = $this->createTermProgressBar(count($items), $title);

            $progress->start();

            foreach ($items as $item) {
                $callback($item);
                $progress->advance();
            }

            $progress->finish();
        };
    }

    public function createTermProgressBar()
    {
        return function (
            int $total,
            ?string $title = null,
        ) {
            $helper = new ProgressBar(
                output: $this->output,
                total: $total,
                title: $title,
            );

            $this->trap(SIGINT, fn () => $helper->onCancel());

            return $helper;
        };
    }

    public function termNote()
    {
        return function (string|array $text, string $title = '') {
            (new Note($this->output, $text, $title))->display();
        };
    }

    public function termIntro()
    {
        return function (string $text) {
            (new Intro($this->output, $text))->display();
        };
    }

    public function termOutro()
    {
        return function (string $text) {
            (new Outro($this->output, $text))->display();
        };
    }

    public function termComment()
    {
        return function (string|array $text) {
            (new Output($this->output, $text, 'comment'))->display();
        };
    }

    public function termInfo()
    {
        return function (string|array $text) {
            (new Output($this->output, $text, 'info'))->display();
        };
    }

    public function termWarning()
    {
        return function (string|array $text) {
            (new Output($this->output, $text, 'warning'))->display();
        };
    }

    public function termError()
    {
        return function (string|array $text) {
            (new Output($this->output, $text, 'error'))->display();
        };
    }
}
