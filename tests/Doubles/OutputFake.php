<?php

namespace Terminalia\Tests\Doubles;

use PHPUnit\Framework\Assert;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Output\OutputInterface;

class OutputFake implements OutputInterface
{
    protected $written = [];

    public function setVerbosity(int $level)
    {
    }

    public function getVerbosity(): int
    {
        return 0;
    }

    public function isQuiet(): bool
    {
        return false;
    }

    public function isVerbose(): bool
    {
        return false;
    }

    public function isVeryVerbose(): bool
    {
        return false;
    }

    public function isDebug(): bool
    {
        return false;
    }

    public function setDecorated(bool $decorated)
    {
    }

    public function isDecorated(): bool
    {
        return false;
    }

    public function setFormatter(OutputFormatterInterface $formatter)
    {
    }

    public function getFormatter(): OutputFormatterInterface
    {
        return new OutputFormatter();
    }

    public function newLine()
    {
        array_push($this->written, PHP_EOL);
    }

    /**
     * {@inheritdoc}
     */
    public function write(string|iterable $messages, bool $newline = false, int $options = 0)
    {
        array_push($this->written, $messages);
    }

    /**
     * {@inheritdoc}
     */
    public function writeln(string|iterable $messages, int $type = self::OUTPUT_NORMAL)
    {
        array_push($this->written, $messages);
    }

    public function assertWritten(string|iterable $messages)
    {
        if (is_string($messages)) {
            $messages = [$messages];
        }

        // ray(
        //     $messages,
        //     $this->written,
        //     '[' . PHP_EOL . collect($this->written)->map(fn ($w) => "'" . $w . "'")->join(',' . PHP_EOL) . PHP_EOL . ']'
        // );

        Assert::assertEquals($messages, $this->written);
    }
}
