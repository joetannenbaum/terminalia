<?php

use Terminalia\PromptTypes\Intro;
use Terminalia\PromptTypes\Output;
use Terminalia\PromptTypes\Outro;
use Terminalia\Tests\Doubles\OutputFake;

beforeEach(function () {
    $this->fake = new OutputFake();
});

test('can write an intro', function () {
    $intro = new Intro($this->fake, 'This is an intro');
    $intro->display();

    $this->fake->assertWritten([PHP_EOL, '<dim>┌</dim> <intro> This is an intro </intro>']);
});

test('can write an outro', function () {
    $intro = new Outro($this->fake, 'This is an outro');
    $intro->display();

    $this->fake->assertWritten(['<dim>│</dim> ', '<dim>└</dim> This is an outro', PHP_EOL]);
});

test('can write a warning', function () {
    $intro = new Output($this->fake, 'This is a warning', 'warning');
    $intro->display();

    $this->fake->assertWritten(['<dim>│</dim> ', '<warning>▲ This is a warning</warning>']);
});

test('can write info', function () {
    $intro = new Output($this->fake, 'This is info', 'info');
    $intro->display();

    $this->fake->assertWritten(['<dim>│</dim> ', '<info>◇ This is info</info>']);
});

test('can write an error', function () {
    $intro = new Output($this->fake, 'This is an error', 'error');
    $intro->display();

    $this->fake->assertWritten(['<dim>│</dim> ', '<canceled>■ This is an error</canceled>']);
});

test('can write a comment', function () {
    $intro = new Output($this->fake, 'This is a comment', 'comment');
    $intro->display();

    $this->fake->assertWritten(['<dim>│</dim> ', '<comment>● This is a comment</comment>']);
});
