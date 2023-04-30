<?php

namespace InteractiveConsole\PromptTypes;

use Bellows\Console\BlockSymbols;
use Illuminate\Support\Str;
use InteractiveConsole\Helpers\IsCancelable;
use InteractiveConsole\Helpers\SpinnerMessenger;
use InteractiveConsole\Helpers\WritesOutput;
use Spatie\Fork\Connection;
use Spatie\Fork\Fork;
use Symfony\Component\Console\Cursor;
use Symfony\Component\Console\Style\OutputStyle;

class Spinner
{
    use WritesOutput, IsCancelable;

    protected Cursor $cursor;

    protected Connection $socketToSpinner;

    protected Connection $socketToTask;

    protected string $stopKey;

    public function __construct(
        protected OutputStyle $output,
        protected string $title,
        protected $task,
        protected $inputStream = null,
        protected mixed $message = null,
        protected array $longProcessMessages = [],
    ) {
        $this->inputStream = $this->inputStream ?? fopen('php://stdin', 'rb');
        $this->cursor = new Cursor($this->output, $this->inputStream);
        $this->registerStyles();
    }

    public function spin(): mixed
    {
        // Create a pair of socket connections so the two tasks can communicate
        [$this->socketToTask, $this->socketToSpinner] = Connection::createPair();

        $this->stopKey = Str::random();

        $this->cursor->hide();

        $result = app(Fork::class)->run(
            $this->runTask(...),
            $this->showSpinner(...),
        );

        $this->cursor->show();

        $this->socketToSpinner->close();
        $this->socketToTask->close();

        return $result[0];
    }

    public function onCancel($message = 'Cancel')
    {
        $this->socketToSpinner->close();
        $this->socketToTask->close();
        $this->writeCanceledBlock($message);
        exit();
    }

    protected function showSpinner()
    {
        $animation = collect(['◒', '◐', '◓', '◑']);
        $startTime = time();

        $index = 0;

        $reversedLongProcessMessages = collect($this->longProcessMessages)->reverse();

        $message = '';
        $socketResults = '';

        // Scaffold out the general layout of the spinner
        $this->writeBlock('');
        $this->writeBlock('');
        $this->writeEndBlock('');

        $this->cursor->moveUp(2);

        while (Str::contains($socketResults, $this->stopKey) === false) {
            foreach ($this->socketToTask->read() as $output) {
                $socketResults .= $output;

                if (!Str::contains($output, $this->stopKey)) {
                    $message = $output;
                }
            }

            $runningTime = 0;
            $runningTime = time() - $startTime;

            $longProcessMessage = $message ?? $reversedLongProcessMessages->first(
                fn ($v, $k) => $runningTime >= $k
            ) ?? '';

            $index = ($index === $animation->count() - 1) ? 0 : $index + 1;

            $this->cursor->moveToColumn(0);
            $this->cursor->clearLine();

            $this->output->write(
                $this->wrapInTag($animation->get($index), 'spinner')
                    . ' '
                    . $this->title
                    . Str::of($longProcessMessage)->whenNotEmpty(
                        fn ($s) => ' ' . $this->wrapInTag($s, 'unfocused')
                    ),
            );

            usleep(200_000);
        }
    }

    protected function runTask()
    {
        $output = ($this->task)(new SpinnerMessenger($this->socketToSpinner));

        $this->socketToSpinner->write($this->stopKey);

        // Wait for the next cycle of the spinner so that it stops
        usleep(200_000);

        $this->cursor->moveToColumn(0);
        $this->cursor->clearLine();
        $this->cursor->moveUp();

        $this->writeLine($this->wrapInTag(BlockSymbols::LINE->value, 'unfocused'));
        $this->writeLine(
            $this->wrapInTag(BlockSymbols::ANSWERED->value, 'info')
                . ' '
                . $this->wrapInTag(
                    $this->getFinalDisplay($output),
                    'unfocused'
                )
        );

        return $output;
    }

    protected function getFinalDisplay($output)
    {
        if (is_callable($this->message)) {
            return ($this->message)($output);
        }

        if (is_string($this->message)) {
            return $this->message;
        }

        if (is_string($output)) {
            return $output;
        }

        return $this->title;
    }
}
