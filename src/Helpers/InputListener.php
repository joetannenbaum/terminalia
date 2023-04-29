<?php

namespace InteractiveConsole\Helpers;

class InputListener
{
    protected $runOnExit;

    protected $runAfterKeyPress;

    protected $stopOnEnterPress = true;

    protected array $keyPressListeners = [];

    protected array $controlSequenceListeners = [];

    protected array $eventListeners = [];

    protected string $sttyMode;

    public function __construct(protected $inputStream)
    {
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

    public function listen()
    {
        $this->sttyMode = shell_exec('stty -g');
        $isStdin = 'php://stdin' === (stream_get_meta_data($this->inputStream)['uri'] ?? null);
        $r = [$this->inputStream];
        $w = [];

        shell_exec('stty -icanon -echo');

        // Read a keypress
        while (!feof($this->inputStream)) {
            while ($isStdin && 0 === @stream_select($r, $w, $w, 0, 100)) {
                // Give signal handlers a chance to run
                $r = [$this->inputStream];
            }

            $c = fread($this->inputStream, 1);

            if (!$this->handleInput($c)) {
                break;
            }

            if (isset($this->runAfterKeyPress)) {
                ($this->runAfterKeyPress)();
            }
        }

        shell_exec('stty ' . $this->sttyMode);
    }

    protected function run(string $toRun)
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

            shell_exec('stty ' . $this->sttyMode);

            throw new \Exception('Aborted.');
        }

        if ($c === ControlSequence::BACKSPACE->value) {
            $this->handleControlSequence(ControlSequence::BACKSPACE->value);

            return true;
        }

        if ($c === "\033") {
            $c .= fread($this->inputStream, 2);

            if (!isset($c[2])) {
                // They pressed escape, probably
                $this->handleEvent(TerminalEvent::ESCAPE->value);

                return true;
            }

            $this->handleControlSequence($c[2]);

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
