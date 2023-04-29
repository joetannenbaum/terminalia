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

<video class="video -2x " controls="" poster="https://brief.cleanshot.cloud/media/714/TS6olNSfzN0rMlMGMLoeXSW8Mc7bKLtFG3wqw4Eb.mp4?" controlslist="nodownload">
    <source src="https://media.cleanshot.cloud/media/714/TS6olNSfzN0rMlMGMLoeXSW8Mc7bKLtFG3wqw4Eb.mp4?Expires=1682813964&amp;Signature=qYIYlCnXIfgmu5RSPnVVKxBBbqYbBuggcQ1rtBQiForjNLlWEW6AjgNvfpxfhNxZLtqYnEuFLyBmHKXIlzA6I18DbPF77QmlOuJHU1sKuad~n~E02Cbr0SgSlWVcKysBp3cbsVT3WppKiK2seGuxiIn~Ho4trUZggC~kMyc7GIO89pG1bBFOyPks8ZQYsHfVzgkFZlh8aj4fLMiUn69qnjJWa4OpCMF5kfeZR26oYM5cmEbRGEEQrutSCiZRe9CNIwGFUEJJdv4-hWpB9yyRFNHV459zvEuAvXoXcgjFYhq8B67GTkgeo2wUhazWAh5f~4kH9rjRinxiYKZggElY5A__&amp;Key-Pair-Id=K269JMAT9ZF4GZ" type="video/mp4">
    Your browser does not support the video tag.
</video>

## Input Validation

The `validator` argument of these methods uses Laravel's built-in validator, so it accepts anything you are able to pass to `Validator::make`.

**Note:** If you're using validation within a [Laravel Zero](https://laravel-zero.com) app, remember to register your `ValidationServiceProvider::class` and `TranslationServiceProvider::class` in your `config/app.php` file and also include a `lang` directory in your project root.
