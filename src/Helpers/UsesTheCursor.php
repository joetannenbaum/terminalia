<?php

namespace InteractiveConsole\Helpers;

use Symfony\Component\Console\Cursor;

trait UsesTheCursor
{
    protected Cursor $cursor;

    protected $bookmarks = [];

    protected function initCursor()
    {
        $this->cursor = new Cursor($this->output, $this->inputStream);
        $this->bookmark('start', $this->getCurrentCursorPosition());
    }

    protected function getCurrentCursorPosition()
    {
        [$x, $y] = $this->cursor->getCurrentPosition();

        return [$x, $y - 1];
    }

    protected function bookmark(string $name, array $position = null)
    {
        $this->bookmarks[$name] = $position ?? $this->getCurrentCursorPosition();
    }

    protected function hasBookmark(string $name): bool
    {
        return isset($this->bookmarks[$name]);
    }

    protected function moveToBookmark(string $name)
    {
        if (!isset($this->bookmarks[$name])) {
            throw new \Exception("Bookmark {$name} does not exist");
        }

        $this->cursor->moveToPosition(...$this->bookmarks[$name]);
    }

    protected function clearCurrentOutput(): void
    {
        $this->moveToBookmark('start');
        $this->cursor->clearOutput();
    }

    protected function clearContentAfterTitle(): void
    {
        $this->moveToBookmark('endOfTitle');
        $this->cursor->clearOutput();
    }
}
