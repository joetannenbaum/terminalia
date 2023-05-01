<?php

namespace InteractiveConsole\Helpers;

use Symfony\Component\Console\Cursor;

trait UsesTheCursor
{
    protected Cursor $cursor;

    protected function initCursor()
    {
        $this->cursor = new Cursor($this->output, $this->inputStream);
    }
}
