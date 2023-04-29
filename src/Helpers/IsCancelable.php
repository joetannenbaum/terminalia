<?php

namespace InteractiveConsole\Helpers;

trait IsCancelable
{
    protected bool $canceled = false;

    protected function writeCanceledBlock(string $text): void
    {
        $this->writeBlock('');
        $this->writeEndBlock("<canceled>{$text}</canceled>");
        $this->output->newLine();
    }
}
