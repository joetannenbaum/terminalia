<?php

namespace InteractiveConsole\Helpers;

use InteractiveConsole\Enums\ControlSequence;
use InteractiveConsole\Enums\TerminalEvent;

class InputListener
{
    protected $runOnExit;

    protected $runAfterKeyPress;

    protected $stopOnEnterPress = true;

    protected array $keyPressListeners = [];

    protected array $controlSequenceListeners = [];

    protected array $eventListeners = [];

    protected bool $stopListening = false;

    protected Stty $stty;

    public function __construct(protected $inputStream)
    {
        $this->stty = new Stty();
    }

    public function on(string|ControlSequence|TerminalEvent|array $key, callable $cb): static
    {
        if (!is_array($key)) {
            $key = [$key];
        }

        foreach ($key as $k) {
            if (is_string($k) || is_numeric($k)) {
                $this->keyPressListeners[(string) $k] = $cb;
            }

            if ($k instanceof ControlSequence) {
                $this->controlSequenceListeners[$k->value] = $cb;
            }

            if ($k instanceof TerminalEvent) {
                $this->eventListeners[$k->value] = $cb;
            }
        }

        return $this;
    }

    public function afterKeyPress(callable $cb): static
    {
        $this->runAfterKeyPress = $cb;

        return $this;
    }

    public function listen(): void
    {
        $this->stopListening = false;

        $isStdin = 'php://stdin' === (stream_get_meta_data($this->inputStream)['uri'] ?? null);
        $r = [$this->inputStream];
        $w = [];

        $this->stty->disableEcho();

        // Read a keypress
        while (!$this->stopListening) {
            while ($isStdin && 0 === @stream_select($r, $w, $w, 0, 100)) {
                // Give signal handlers a chance to run
                $r = [$this->inputStream];
            }

            $c = fread($this->inputStream, 1);

            if (!$this->handleInput($c)) {
                $this->stopListening = true;
                break;
            }

            if (isset($this->runAfterKeyPress)) {
                ($this->runAfterKeyPress)();
            }
        }

        $this->stty->restore();
    }

    public function stop()
    {
        $this->stopListening = true;
    }

    public function setStopOnEnter(bool $stopOnEnterPress): static
    {
        $this->stopOnEnterPress = $stopOnEnterPress;

        return $this;
    }

    protected function run(string $toRun): void
    {
        $property = 'run' . ucwords($toRun);

        if (isset($this->{$property})) {
            ($this->{$property})();
        }
    }

    protected function handleEvent(string $key): bool
    {
        if (isset($this->eventListeners[$key])) {
            ($this->eventListeners[$key])();

            return true;
        }

        return false;
    }

    protected function handleControlSequence(string $key): bool
    {
        if (isset($this->controlSequenceListeners[$key])) {
            ($this->controlSequenceListeners[$key])();

            return true;
        }

        return false;
    }

    protected function handleKeyPress(string $key): bool
    {
        if (isset($this->keyPressListeners[$key])) {
            ($this->keyPressListeners[$key])();

            return true;
        }

        if (isset($this->keyPressListeners['*'])) {
            ($this->keyPressListeners['*'])($key);

            return true;
        }

        return false;
    }

    protected function handleInput(string $c): bool
    {
        // As opposed to fgets(), fread() returns an empty string when the stream content is empty, not false.
        if ($c === false || $c === '') {
            $this->run('onExit');

            $this->stty->restore();

            throw new \Exception('Aborted.');
        }

        if ($c === ControlSequence::BACKSPACE->value) {
            $this->handleControlSequence(ControlSequence::BACKSPACE->value);

            return true;
        }

        if ($c === "\033") {
            $meta = stream_get_meta_data($this->inputStream);

            if ($meta['unread_bytes'] === 0) {
                // They pressed escape, probably
                $this->handleEvent(TerminalEvent::ESCAPE->value);

                return true;
            }

            $c .= fread($this->inputStream, 2);

            if (isset($c[2])) {
                $this->handleControlSequence($c[2]);
            }

            return true;
        }

        if (
            $c === "\n" && $this->stopOnEnterPress
        ) {
            return false;
        }

        if ($this->handleKeyPress($c)) {
            return true;
        }

        return true;
    }
}
