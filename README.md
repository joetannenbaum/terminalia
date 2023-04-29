# Laravel Interactive Console

I challenged myself to re-create the user experience of the excellent [Clack](https://github.com/natemoo-re/clack) library within the Laravel console. This package is the result! Definitely not ready for production use, but it's a fun experiment and I encourage you to try it out if you feel so inclined.

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
    validator: ['required', 'numeric'],
);

$dontTell = $this->interactiveAsk(
    question: 'Tell me a secret:',
    validator: ['required'],
    hidden: true,
);

$seuss = $this->interactiveChoice(
    question: 'Pick a fish, any fish:',
    items: ['one fish', 'two fish', 'red fish', 'blue fish'],
    validator: ['required'],
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
    validator: ['required'],
);

$confirmed = $this->interactiveConfirm(
    question: 'Everything look good?',
);

$this->outro("Thank you for your response! Have a great day.");
```
