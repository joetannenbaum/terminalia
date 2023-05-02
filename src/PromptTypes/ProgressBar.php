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
            $this->writeBlock('');
        }

        $this->writeEndBlock('');

        $this->cursor->moveUp(2);

        $this->writeProgressBar();
    }

    public function advance(int $step = 1)
    {
        $this->current += $step;

        $this->writeProgressBar();
    }

    public function finish()
    {
        $this->cursor->moveToColumn(0);
        $this->cursor->moveUp($this->title ? 2 : 1);
        $this->cursor->clearOutput();
        $this->writeInactiveBlock();
        $this->writeBlock($this->title ?? '', BlockSymbols::ANSWERED);

        if ($this->title) {
            $this->writeInactiveBlock();
        }

        $this->writeInactiveBlock();
        $this->cursor->moveUp(2);
        $this->writeProgressBar();
        $this->cursor->moveDown();
        $this->cursor->moveToColumn(0);
        $this->cursor->show();
    }

    public function onCancel(string $message = 'Canceled'): void
    {
        $this->canceled = true;

        $this->cursor->moveDown(2);
        $this->cursor->moveToColumn(0);
        $this->clearCurrentOutput();

        $this->writeInactiveBlock();

        $this->writeTitleBlock(
            $this->dim($this->title ?? ''),
        );

        if ($this->title) {
            $this->writeBlock('');
        }

        $this->cursor->moveUp();

        $this->writeProgressBar();

        $this->cursor->moveToColumn(0);
        $this->cursor->moveDown();

        $this->writeCanceledBlock($message);

        exit;
    }

    protected function writeProgressBar()
    {
        $this->cursor->moveToColumn(3);
        $this->cursor->clearLineAfter();

        $percentage = min(round(($this->current / $this->total) * 100), 100);
        $percentageRounded = floor($percentage / 10) * 10;

        $percentageFilled = $percentageRounded / 10;
        $percentageEmpty = 10 - $percentageFilled;

        $this->output->write(
            ($percentageFilled > 0 ? str_repeat(self::BAR_CHARACTER, $percentageFilled) : '')
                . ($percentageEmpty > 0 ? $this->dim(str_repeat(self::EMPTY_BAR_CHARACTER, $percentageEmpty)) : '')
                . $this->dim(" {$percentage}% ({$this->current}/{$this->total})"),
        );
    }
}
