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

![Demo](examples/full.gif)

## Input Validation

The `rules` argument of these methods uses Laravel's built-in validator, so it accepts anything you are able to pass to `Validator::make`.

**Note:** If you're using validation within a [Laravel Zero](https://laravel-zero.com) app, remember to register your `ValidationServiceProvider::class` and `TranslationServiceProvider::class` in your `config/app.php` file and also include a `lang` directory in your project root.

## Spinner

The `spinner` method allows you to show a spinner while an indefinite process is running. It allows customization to you can inform your user of what's happening as the process runs. The result of the spinner will be whatever is returned from the `task` argument.

It's important to note that the `task` runs in a forked process, so the task itself shouldn't create any side effects in your application. It should just process something and return a result.

### Examples

Simple:

```php
$site = $this->spinner(
    title: 'Creating site...',
    task: function () {
        // Do something here that takes a little while
        $site = Site::create();
        $site->deploy();

        return $site;
    },
    message: 'Site created!',
);
```

![Demo](examples/spinner-simple.gif)

Displays a variable message based on the result of the task:

```php
$site = $this->spinner(
    title: 'Creating site...',
    task: function () {
        // Do something here that takes a little while
        $site = Site::create();
        $site->deploy();

        return $site->wasDeployed;
    },
    message: fn($result) => $result ? 'Site created!' : 'Error creating site.',
);
```

![Demo](examples/spinner-custom-message.gif)

Updates user of progress as it works:

```php
$site = $this->spinner(
    title: 'Creating site...',
    task: function (SpinnerMessenger $messenger) {
        // Do something here that takes a little while
        $site = Site::create();

        $messenger->send('Site created, deploying');
        $site->deploy();

        $messenger->send('Verifying deployment');
        $site->verifyDeployment();

        return $site->wasDeployed;
    },
    message: fn($result) => $result ? 'Site created!' : 'Error creating site.',
);
```

![Demo](examples/spinner-update-messages.gif)

Sends users encouraging messages while they wait:

```php
$site = $this->spinner(
    title: 'Creating site...',
    task: function () {
        // Do something here that takes a little while
        $site = Site::create();
        $site->deploy();
        $site->verifyDeployment();

        return $site->wasDeployed;
    },
    // seconds => message
    longProcessMessages: [
        3  => 'One moment',
        7  => 'Almost done',
        11 => 'Wrapping up',
    ],
);
```

![Demo](examples/spinner-long-processing-messages.gif)
