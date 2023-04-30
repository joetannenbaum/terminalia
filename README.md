# Laravel Interactive Console

I challenged myself to re-create the user experience of the excellent [Clack](https://github.com/natemoo-re/clack) library within the Laravel console. This package is the result!

Definitely not ready for production use, but it's a fun experiment and I encourage you to try it out if you feel so inclined.

It's a WIP, so the API might change unexpectedly and I'll be adding features as I need them.

## Installation

```bash
composer require joetannenbaum/laravel-interactive-console
```

## Usage

This package works using a Console mixin, which should be automatically registered when the package is installed. If the service provider doesn't automatically reigster, add the following to your `config/app.php` file:

```php
'providers' => [
    // ...
    InteractiveConsole\Providers\InteractiveConsoleServiceProvider::class,
],
```

Once the service provider is registered, you'll have access to a couple of new methods within your Artisan commands:

```php
$this->intro("Welcome! Let's get started.");

$bigAnswer = $this->interactiveAsk(
    question: 'The answer to the life, the universe, and everything is:',
    rules: ['required', 'numeric'],
);

$dontTell = $this->interactiveAsk(
    question: 'Tell me a secret:',
    rules: ['required'],
    hidden: true,
);

$seuss = $this->interactiveChoice(
    question: 'Pick a fish, any fish:',
    items: ['one fish', 'two fish', 'red fish', 'blue fish'],
    rules: ['required'],
);

$favoriteThings = $this->interactiveChoice(
    question: 'Which are your favorite things:',
    items: [
        'raindrops on roses',
        'whiskers on kittens',
        'bright copper kettles',
        'warm woolen mittens',
    ],
    multiple: true,
    rules: ['required'],
);

$confirmed = $this->interactiveConfirm(
    question: 'Everything look good?',
);

$this->outro("Thank you for your response! Have a great day.");
```

![Demo](https://media.cleanshot.cloud/media/714/5XAx8TAbSuXTC7qnj2uzNT0MU6lfwtRfLvkUpOGE.gif?Expires=1682814417&Signature=j3ZHDjn1QjcXXt1pKOJKy6Z4F53CTihxsIEfIiI7rmeVO1rT3IstL5T3DNagPIF7Kulzx753BLGA7BXOYI2MHtX4vmqr52cosUIHXRJzv3QcDq--lZOn3VoYjXi1DfD1MXXdhPeOs7II4PGYa-BWquzuG-pykL47JoWXDYdQm58wqfLi5IpFjuN1XYeZ~1iKQ7fYwtxIgEhtX7jPXZDro~ogHPBnkmyDEne2o40UO20RooixNHrKzDTL1E6lQXFJXQCGfThprHr36U~CBeAQ-LcOSUL1Sk126CKnWRtY7295JGQHQakooBoOCOXEwIZR3u6N0fSKHwDBWFaEC0qYVg__&Key-Pair-Id=K269JMAT9ZF4GZ)

## Input Validation

The `rules` argument of these methods uses Laravel's built-in validator, so it accepts anything you are able to pass to `Validator::make`.

**Note:** If you're using validation within a [Laravel Zero](https://laravel-zero.com) app, remember to register your `ValidationServiceProvider::class` and `TranslationServiceProvider::class` in your `config/app.php` file and also include a `lang` directory in your project root.
