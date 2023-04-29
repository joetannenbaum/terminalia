<?php

namespace InteractiveConsole\Mixins;

use InteractiveConsole\Helpers\ChoicesHelper;
use InteractiveConsole\Helpers\ConfirmHelper;
use InteractiveConsole\Helpers\IntroHelper;
use InteractiveConsole\Helpers\OutroHelper;
use InteractiveConsole\Helpers\QuestionHelper;

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
            $inputStream = fopen('php://stdin', 'rb');

            $helper = new ChoicesHelper(
                output: $this->output,
                inputStream: $inputStream,
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
            $inputStream = fopen('php://stdin', 'rb');

            $helper = new ConfirmHelper(
                output: $this->output,
                inputStream: $inputStream,
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

            $helper = new QuestionHelper(
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

    public function intro()
    {
        return function (string $text) {
            (new IntroHelper($this->output, $text))->display();
        };
    }

    public function outro()
    {
        return function (string $text) {
            (new OutroHelper($this->output, $text))->display();
        };
    }
}
