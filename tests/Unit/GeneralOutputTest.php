<?php

use Terminalia\PromptTypes\Intro;
use Terminalia\PromptTypes\Note;
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

test('can write a note', function () {
    $intro = new Note($this->fake, 'This is a note', 'This is the note title');
    $intro->display();

    $this->fake->assertWritten([
        '<dim>│</dim> ',
        '<info>◇</info> This is the note title  <dim>───╮</dim>',
        '<dim>│</dim> <dim>                           │</dim>',
        '<dim>│</dim> <dim>                           │</dim>',
        '<dim>│</dim> <dim> This is a note            │</dim>',
        '<dim>│</dim> <dim>                           │</dim>',
        '<dim>│</dim> <dim>                           │</dim>',
        '<dim>├────────────────────────────╯</dim>',
    ]);
});

test('can write a note without a title', function () {
    $intro = new Note($this->fake, 'This is a note', '');
    $intro->display();

    $this->fake->assertWritten([
        '<dim>│</dim> ',
        '<info>◇</info> <dim>───────────────────╮</dim>',
        '<dim>│</dim> <dim>                   │</dim>',
        '<dim>│</dim> <dim>                   │</dim>',
        '<dim>│</dim> <dim> This is a note    │</dim>',
        '<dim>│</dim> <dim>                   │</dim>',
        '<dim>│</dim> <dim>                   │</dim>',
        '<dim>├────────────────────╯</dim>',
    ]);
});

test('can write a note and wrap longer text', function () {
    $intro = new Note(
        $this->fake,
        'This is a note and it is much longer it is so long in fact that it will be forced to wrap and that is that.',
        ''
    );
    $intro->display();

    $this->fake->assertWritten([
        '<dim>│</dim> ',
        '<info>◇</info> <dim>───────────────────────────────────────────────────────────────╮</dim>',
        '<dim>│</dim> <dim>                                                               │</dim>',
        '<dim>│</dim> <dim>                                                               │</dim>',
        '<dim>│</dim> <dim> This is a note and it is much longer it is so long in fact    │</dim>',
        '<dim>│</dim> <dim> that it will be forced to wrap and that is that.              │</dim>',
        '<dim>│</dim> <dim>                                                               │</dim>',
        '<dim>│</dim> <dim>                                                               │</dim>',
        '<dim>├────────────────────────────────────────────────────────────────╯</dim>',
    ]);
});
