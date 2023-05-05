<?php

namespace Terminalia\PromptTypes;

use Illuminate\Support\Str;
use Spatie\Fork\Connection;
use Spatie\Fork\Fork;
use Symfony\Component\Console\Style\OutputStyle;
use Terminalia\Enums\BlockSymbols;
use Terminalia\Helpers\IsCancelable;
use Terminalia\Helpers\SpinnerMessenger;
use Terminalia\Helpers\Stty;
use Terminalia\Helpers\UsesTheCursor;
use Terminalia\Helpers\WritesOutput;

class Spinner
{
    use WritesOutput, IsCancelable, UsesTheCursor;

    protected const SLEEP_TIME = 200_000;

    protected Connection $socketToSpinner;

    protected Connection $socketToTask;

    protected bool $isChildProcess = false;

    protected string $stopKey;

    public function __construct(
        protected OutputStyle $output,
        protected string $title,
        protected $task,
        protected mixed $message = null,
        protected array $longProcessMessages = [],
        protected $inputStream = null,
    ) {
        $this->inputStream = $this->inputStream ?? fopen('php://stdin', 'rb');
        $this->initCursor();
        $this->registerStyles();
    }

    public function spin(): mixed
    {
        $stty = new Stty();

        $stty->disableEcho();

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

        $stty->restore();

        return $result[0];
    }

    public function onCancel(string $message = 'Canceled'): void
    {
        if ($this->isChildProcess) {
            exit;
        }

        $this->canceled = true;

        $this->socketToSpinner->close();
        $this->socketToTask->close();

        $this->clearCurrentOutput();
        $this->writeTitleBlock($this->dim($this->title));
        $this->writeCanceledBlock($message);

        exit;
    }

    protected function showSpinner(): void
    {
        $this->isChildProcess = true;

        $animation = collect(['◒', '◐', '◓', '◑']);
        $startTime = time();

        $index = 0;

        $reversedLongProcessMessages = collect($this->longProcessMessages)->reverse();

        $message = '';
        $socketResults = '';

        // Scaffold out the general layout of the spinner
        $this->writeInactiveBlock('');
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

            $longProcessMessage = trim($message) ?: $reversedLongProcessMessages->first(
                fn ($v, $k) => $runningTime >= $k
            ) ?: '';

            $this->cursor->moveToColumn(0);
            $this->cursor->clearLine();

            $this->output->write(
                $this->pending($animation->loop($index++))
                    . ' '
                    . $this->title
                    . Str::of($longProcessMessage)->whenNotEmpty(
                        fn ($s) => ' ' . $this->dim($s)
                    ),
            );

            usleep(self::SLEEP_TIME);
        }
    }

    protected function runTask(): mixed
    {
        $this->isChildProcess = true;

        $output = ($this->task)(new SpinnerMessenger($this->socketToSpinner));

        $this->socketToSpinner->write($this->stopKey);

        // Wait for the next cycle of the spinner so that it stops
        usleep(self::SLEEP_TIME * 2);

        $this->cursor->moveToColumn(0);
        $this->cursor->clearLine();
        $this->cursor->moveUp();

        // TODO: This feels like a title block? Answered flag maybe?
        $this->writeLine($this->dim(BlockSymbols::LINE->symbol()));
        $this->writeLine(
            $this->active(BlockSymbols::ANSWERED->symbol())
                . ' '
                . $this->dim(
                    $this->getFinalDisplay($output),
                )
        );

        return $output;
    }

    protected function getFinalDisplay($output): string
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
