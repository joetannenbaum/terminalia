<?php

namespace InteractiveConsole\PromptTypes;

use InteractiveConsole\Enums\BlockSymbols;
use InteractiveConsole\Helpers\IsCancelable;
use InteractiveConsole\Helpers\UsesTheCursor;
use InteractiveConsole\Helpers\WritesOutput;
use Symfony\Component\Console\Style\OutputStyle;

class ProgressBar
{
    use WritesOutput, IsCancelable, UsesTheCursor;

    public const BAR_CHARACTER = '▰';

    public const EMPTY_BAR_CHARACTER = '▱';

    protected $current = 0;

    public function __construct(
        protected OutputStyle $output,
        protected int $total,
        protected ?string $title = null,
        protected $inputStream = null,
    ) {
        $this->inputStream = $this->inputStream ?? fopen('php://stdin', 'rb');
        $this->initCursor();
        $this->registerStyles();
    }

    public function start()
    {
        $this->cursor->hide();

        $this->writeTitleBlock($this->title ?? '');

        if ($this->title) {
            // Leave a space for the progres bar to overwrite
            $this->writeBlock('');
        }

        // Mark where the progress bar should go, 2 lines up from here
        $position = $this->cursor->getCurrentPosition();
        $this->bookmark('progressBar', [3, $position[1] - 2]);

        $this->writeEndBlock('');
        $this->writeProgressBar();
    }

    public function advance(int $step = 1)
    {
        $this->current += $step;
        $this->writeProgressBar();
    }

    public function finish()
    {
        $this->clearCurrentOutput();
        $this->writeInactiveBlock();
        $this->writeBlock($this->title ?? '', BlockSymbols::ANSWERED);

        if ($this->title) {
            // Leave a space for the progres bar to overwrite
            // TODO: Another instance of maybe needing an $answered flag so that we can style automatically?
            $this->writeInactiveBlock();
        }

        $this->writeInactiveBlock();
        $this->writeProgressBar();

        // Put the cursor back in place for the next question
        $this->cursor->moveDown();
        $this->cursor->moveToColumn(0);

        $this->cursor->show();
    }

    public function onCancel(string $message = 'Canceled'): void
    {
        $this->canceled = true;

        $this->clearCurrentOutput();
        $this->writeTitleBlock($this->dim($this->title ?? ''));

        if ($this->title) {
            $this->writeInactiveBlock('');
        }

        $this->writeProgressBar();

        // Put the cursor back in place to accomodate for the cancel block
        $this->cursor->moveDown();
        $this->cursor->moveToColumn(0);

        $this->writeCanceledBlock($message);

        exit;
    }

    protected function writeProgressBar()
    {
        $this->moveToBookmark('progressBar');
        $this->cursor->clearLineAfter();

        $percentage = min(round(($this->current / $this->total) * 100), 100);
        $percentageRounded = floor($percentage / 10) * 10;

        $percentageFilled = $percentageRounded / 10;
        $percentageEmpty = 10 - $percentageFilled;

        $filled = $percentageFilled > 0 ? str_repeat(self::BAR_CHARACTER, $percentageFilled) : '';
        $empty = $percentageEmpty > 0 ? str_repeat(self::EMPTY_BAR_CHARACTER, $percentageEmpty) : '';

        $this->output->write(
            $filled . $this->dim("{$empty} {$percentage}% ({$this->current}/{$this->total})"),
        );
    }
}
