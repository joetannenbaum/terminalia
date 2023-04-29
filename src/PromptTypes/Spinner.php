<?php

namespace InteractiveConsole\PromptTypes;

use InteractiveConsole\Helpers\IsCancelable;
use InteractiveConsole\Helpers\WritesOutput;
use Symfony\Component\Console\Cursor;
use Symfony\Component\Console\Style\OutputStyle;
use Spatie\Fork\Connection;
use Spatie\Fork\Fork;

class Spinner
{
    use WritesOutput, IsCancelable;

    protected Cursor $cursor;

    public function __construct(
        protected OutputStyle $output,
        protected string $title,
        protected $task,
        protected $inputStream = null,
        protected string|callable $message = null,
        protected string|callable $success = null,
        protected array $longProcessMessages = [],
    ) {
        $this->inputStream = $this->inputStream ?? fopen('php://stdin', 'rb');
        $this->cursor = new Cursor($this->output, $this->inputStream);
        $this->registerStyles();
    }

    public function spin(): void
    {
        $this->cursor->hide();

        // Create a pair of socket connections so the two tasks can communicate
        [$socketToTask, $socketToSpinner] = Connection::createPair();

        $result = app(Fork::class)
            ->run(
                function () use ($socketToSpinner) {
                    $output = ($this->task)();

                    $socketToSpinner->write(1);

                    // Wait for the next cycle of the spinner so that it stops
                    usleep(200_000);

                    $display = '';

                    if (is_callable($this->message)) {
                        $display = ($this->message)($output);
                    } elseif (is_string($this->message)) {
                        $display = $this->message;
                    } elseif (is_string($output)) {
                        $display = $output;
                    }

                    if (is_callable($this->success)) {
                        $wasSuccessful = ($this->success)($output);
                    } elseif (is_bool($this->success)) {
                        $wasSuccessful = $this->success;
                    } else {
                        // At this point we just assume things worked out
                        $wasSuccessful = true;
                    }

                    $successIndicator = $wasSuccessful ? '✓' : '✗';

                    $finalMessage = $display === '' || $display === null
                        ? $this->wrapInTag($title, 'info')
                        : $this->wrapInTag($title, 'info') . ' ' . $this->wrapInTag($display, 'comment');

                    // $this->overwriteLine(
                    //     "<comment>{$successIndicator}</comment> {$finalMessage}",
                    //     true,
                    // );

                    return $output;
                },
                function () use ($socketToTask) {
                    $animation = collect(['◒', '◐', '◓', '◑']);
                    $startTime = time();

                    $index = 0;

                    $reversedLongProcessMessages = collect($this->longProcessMessages)
                        ->reverse()
                        ->map(fn ($v) => ': ' . $this->wrapInTag($v, 'comment'));

                    $socketResults = '';

                    while (!$socketResults) {
                        foreach ($socketToTask->read() as $output) {
                            $socketResults .= $output;
                        }

                        $runningTime = 0;
                        $runningTime = time() - $startTime;

                        $longProcessMessage = $reversedLongProcessMessages->first(
                            fn ($v, $k) => $runningTime >= $k
                        ) ?? '';

                        $index = ($index === $animation->count() - 1) ? 0 : $index + 1;

                        $this->overwriteLine(
                            "<comment>{$animation->get($index)}</comment> <info>{$title}{$longProcessMessage}</info>"
                        );

                        usleep(200_000);
                    }
                }
            );

        $this->cursor->show();

        $socketToSpinner->close();
        $socketToTask->close();

        return $result[0];
    }

    public function onCancel($message = 'Cancel')
    {
        $this->writeCanceledBlock($message);
        die();
    }
}
