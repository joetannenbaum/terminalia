<?php

namespace InteractiveConsole\Mixins;

use InteractiveConsole\PromptTypes\Choices;
use InteractiveConsole\PromptTypes\Confirm;
use InteractiveConsole\PromptTypes\Intro;
use InteractiveConsole\PromptTypes\Outro;
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
            $validator = null,
            bool $filterable = false,
        ) {

            $helper = new Choices(
                output: $this->output,
                question: $question,
                items: collect($items),
                default: $default ?? [],
            );

            if ($validator) {
                $helper->setValidator($validator);
            }

            if ($filterable) {
                $helper->setFilterable();
            }

            if ($multiple) {
                $helper->setMultiple();
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
        return function (string $question, string $default = null, $validator = null, bool $hidden = false) {
            $inputStream = fopen('php://stdin', 'rb');

            $helper = new Question(
                output: $this->output,
                inputStream: $inputStream,
                question: $question,
                default: $default,
                hidden: $hidden,
            );

            if ($validator) {
                $helper->setValidator($validator);
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
            string|callable $success = null,
            array $longProcessMessages = []
        ) {
            $helper = new Spinner(
                output: $this->output,
                title: $title,
                task: $task,
                message: $message,
                success: $success,
                longProcessMessages: $longProcessMessages,
            );

            $this->trap(SIGINT, fn () => $helper->onCancel());

            $helper->spin();
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
