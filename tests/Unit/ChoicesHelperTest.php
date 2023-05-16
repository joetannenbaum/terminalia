<?php

use Illuminate\Support\Collection;
use Terminalia\Helpers\Choices;

test('can accept an array of choices', function () {
    $choices = Choices::from(['foo', 'bar', 'baz']);

    expect($choices->choices())->toBeInstanceOf(Collection::class);
    expect($choices->choices()->toArray())->toBe(['foo', 'bar', 'baz']);
    expect($choices->returnAsArray())->toBeTrue();
    expect($choices->getSelectedDisplay(collect([0, 1]))->toArray())->toBe(['foo', 'bar']);
    expect($choices->getSelectedIndexesFromDefault(['bar', 'baz'])->toArray())->toBe([1, 2]);
    expect($choices->value(collect([0, 1]))->toArray())->toBe(['foo', 'bar']);
});

test('can accept a collection of choices', function () {
    $choices = Choices::from(collect(['foo', 'bar', 'baz']));

    expect($choices->choices())->toBeInstanceOf(Collection::class);
    expect($choices->choices()->toArray())->toBe(['foo', 'bar', 'baz']);
    expect($choices->returnAsArray())->toBeFalse();
    expect($choices->getSelectedDisplay(collect([0, 1]))->toArray())->toBe(['foo', 'bar']);
    expect($choices->getSelectedIndexesFromDefault(['bar', 'baz'])->toArray())->toBe([1, 2]);
    expect($choices->value(collect([0, 1]))->toArray())->toBe(['foo', 'bar']);
});

test('can pluck a display key', function () {
    $choices = Choices::from(collect([
        [
            'id'   => 1,
            'name' => 'foo',
        ],
        [
            'id'   => 2,
            'name' => 'bar',
        ],
        [
            'id'   => 3,
            'name' => 'baz',
        ],
    ]), 'name');

    expect($choices->choices())->toBeInstanceOf(Collection::class);
    expect($choices->choices()->toArray())->toBe(['foo', 'bar', 'baz']);
    expect($choices->returnAsArray())->toBeFalse();
    expect($choices->getSelectedDisplay(collect([0, 1]))->toArray())->toBe(['foo', 'bar']);
    expect($choices->getSelectedIndexesFromDefault(['bar', 'baz'])->toArray())->toBe([1, 2]);
    expect($choices->value(collect([0, 1]))->toArray())->toBe([
        [
            'id'   => 1,
            'name' => 'foo',
        ],
        [
            'id'   => 2,
            'name' => 'bar',
        ],
    ]);
});

test('can pluck a display key using a callable', function () {
    $choices = Choices::from(collect([
        [
            'id'   => 1,
            'name' => 'foo',
        ],
        [
            'id'   => 2,
            'name' => 'bar',
        ],
        [
            'id'   => 3,
            'name' => 'baz',
        ],
    ]), fn ($item) => $item['name']);

    expect($choices->choices())->toBeInstanceOf(Collection::class);
    expect($choices->choices()->toArray())->toBe(['foo', 'bar', 'baz']);
    expect($choices->returnAsArray())->toBeFalse();
    expect($choices->getSelectedDisplay(collect([0, 1]))->toArray())->toBe(['foo', 'bar']);
    expect($choices->getSelectedIndexesFromDefault(['bar', 'baz'])->toArray())->toBe([1, 2]);
    expect($choices->value(collect([0, 1]))->toArray())->toBe([
        [
            'id'   => 1,
            'name' => 'foo',
        ],
        [
            'id'   => 2,
            'name' => 'bar',
        ],
    ]);
});

test('can pluck a display key and return a key as the value', function () {
    $choices = Choices::from(collect([
        [
            'id'   => 1,
            'name' => 'foo',
        ],
        [
            'id'   => 2,
            'name' => 'bar',
        ],
        [
            'id'   => 3,
            'name' => 'baz',
        ],
    ]), 'name', 'id');

    expect($choices->choices())->toBeInstanceOf(Collection::class);
    expect($choices->choices()->toArray())->toBe(['foo', 'bar', 'baz']);
    expect($choices->returnAsArray())->toBeFalse();
    expect($choices->getSelectedDisplay(collect([0, 1]))->toArray())->toBe(['foo', 'bar']);
    expect($choices->getSelectedIndexesFromDefault([2, 3])->toArray())->toBe([1, 2]);
    expect($choices->value(collect([0, 1]))->toArray())->toBe([1, 2]);
});

test('can pluck a display key and return a key as the value as a callable', function () {
    $choices = Choices::from(collect([
        [
            'id'   => 1,
            'name' => 'foo',
        ],
        [
            'id'   => 2,
            'name' => 'bar',
        ],
        [
            'id'   => 3,
            'name' => 'baz',
        ],
    ]), 'name', fn ($item) => $item['id']);

    expect($choices->choices())->toBeInstanceOf(Collection::class);
    expect($choices->choices()->toArray())->toBe(['foo', 'bar', 'baz']);
    expect($choices->returnAsArray())->toBeFalse();
    expect($choices->getSelectedDisplay(collect([0, 1]))->toArray())->toBe(['foo', 'bar']);
    expect($choices->getSelectedIndexesFromDefault([2, 3])->toArray())->toBe([1, 2]);
    expect($choices->value(collect([0, 1]))->toArray())->toBe([1, 2]);
});


test('can pluck a display key as a callable and return a key as the value as a callable', function () {
    $choices = Choices::from(collect([
        [
            'id'   => 1,
            'name' => 'foo',
        ],
        [
            'id'   => 2,
            'name' => 'bar',
        ],
        [
            'id'   => 3,
            'name' => 'baz',
        ],
    ]), fn ($item) => $item['name'], fn ($item) => $item['id']);

    expect($choices->choices())->toBeInstanceOf(Collection::class);
    expect($choices->choices()->toArray())->toBe(['foo', 'bar', 'baz']);
    expect($choices->returnAsArray())->toBeFalse();
    expect($choices->getSelectedDisplay(collect([0, 1]))->toArray())->toBe(['foo', 'bar']);
    expect($choices->getSelectedIndexesFromDefault([2, 3])->toArray())->toBe([1, 2]);
    expect($choices->value(collect([0, 1]))->toArray())->toBe([1, 2]);
});
