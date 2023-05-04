<?php

namespace InteractiveConsole\Mixins;

use InteractiveConsole\PromptTypes\Choices;
use InteractiveConsole\PromptTypes\Confirm;
use InteractiveConsole\PromptTypes\Intro;
use InteractiveConsole\PromptTypes\Note;
use InteractiveConsole\PromptTypes\Outro;
use InteractiveConsole\PromptTypes\ProgressBar;
use InteractiveConsole\PromptTypes\Question;
use InteractiveConsole\PromptTypes\Spinner;

class InteractiveConsole
{
    public function interactiveChoice()
    {
        return function (
            string $question,
            array $items,
            $default = null,
            $multiple = false,
            $rules = null,
            $filterable = false,
            $filterThreshold = 5,
        ) {
            $helper = new Choices(
                output: $this->output,
                question: $question,
                items: collect($items),
                default: $default ?? [],
            );

            if ($rules) {
                $helper->setRules($rules);
            }

            if ($multiple) {
                $helper->setMultiple();
            }

            if ($filterable && count($items) >= $filterThreshold) {
                $helper->setFilterable();
            }

            $this->trap(SIGINT, fn () => $helper->onCancel());

            return $helper->prompt();
        };
    }

    public function interactiveConfirm()
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

    public function interactiveAsk()
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

    public function spinner()
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

    public function withInteractiveProgressBar()
    {
        return function (
            iterable $items,
            callable $callback,
            ?string $title = null,
        ) {
            $progress = $this->createInteractiveProgressBar(count($items), $title);

            $progress->start();

            foreach ($items as $item) {
                $callback($item);
                $progress->advance();
            }

            $progress->finish();
        };
    }

    public function createInteractiveProgressBar()
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

    public function note()
    {
        return function (string|array $text, string $title = '') {
            (new Note($this->output, $text, $title))->display();
        };
    }

    public function intro()
    {
        return function (string $text) {
            (new Intro($this->output, $text))->display();
        };
    }

    public function outro()
    {
        return function (string $text) {
            (new Outro($this->output, $text))->display();
        };
    }
}
