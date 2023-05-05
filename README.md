![Terminalia](images/terminalia.jpg)

The UX of [Clack](https://github.com/natemoo-re/clack), the DX of [Laravel](https://laravel.com) for your Artisan commands.

## Features

-   Inline input validation using Laravel's built-in validator
-   Interactive prompts for text, choice, and confirmation
-   A spinner for long-running processes

## Demo

![Demo](examples/full-v2.gif)

## Installation

```bash
composer require joetannenbaum/terminalia
```

This package implements a Console mixin, which should be automatically registered when the package is installed.

If the service provider doesn't automatically register (i.e. if you are using [Laravel Zero](https://laravel-zero.com)), add the following to your `config/app.php` file:

```php
'providers' => [
    // ...
    Terminalia\Providers\TerminaliaServiceProvider::class,
],
```

## Usage

Once the service provider is registered, you'll have access to a couple of new methods within your Artisan commands:

```php
$this->termIntro("Welcome! Let's get started.");

$bigAnswer = $this->termAsk(
    question: 'The answer to the life, the universe, and everything is:',
    rules: ['required', 'numeric'],
);

$dontTell = $this->termAsk(
    question: 'Tell me a secret:',
    rules: ['required'],
    hidden: true,
);

$spun = $this->termSpinner(
    title: 'Processing',
    task: function (SpinnerMessenger $messenger) {
        sleep(2);
        $messenger->send("Still cookin'");
        sleep(2);
        $messenger->send('Almost there');
        sleep(2);

        return null;
    },
    message: 'Secret has been processed!'
);

$seuss = $this->termChoice(
    question: 'Pick a fish, any fish:',
    items: ['one fish', 'two fish', 'red fish', 'blue fish'],
    rules: ['required'],
);

$favoriteThings = $this->termChoice(
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

$confirmed = $this->termConfirm(
    question: 'Everything look good?',
);

$this->termNote(
    'You really did it. We are so proud of you. Thank you for telling us all about yourself. We can\'t wait to get to know you better.',
    'Congratulations',
);

$this->termOutro("Thank you for your response! Have a great day.");
```

![Demo](examples/full-v2.gif)

## Input Validation

The `rules` argument of these methods uses Laravel's built-in validator, so it accepts anything you are able to pass to `Validator::make`.

**Note:** If you're using validation within a [Laravel Zero](https://laravel-zero.com) app, remember to register your `ValidationServiceProvider::class` and `TranslationServiceProvider::class` in your `config/app.php` file and also include a `lang` directory in your project root.

## General Output

Terminalia provides methods for outputting `info`, `comment`, `warning`, and `error` messages to the output consistent with the rest of its output:

```php
$this->termInfo('Here is the URL: https://bellows.dev');

$this->termComment([
    'This is a multi-line comment! I have a lot to say, and it is easier to write as an array.',
    'Here is the second part of what I have to say. Not to worry, Terminalia will handle all of the formatting.',
]);

$this->termError('Whoops! That did not go so well.');

$this->termWarning('Heads up! Output may be *too* beautiful.');
```

## Filtering Choices

If you have a longer list of choices, you can allow the user to filter them using the `filter` argument. This will allow the user to type in a search term and the list will be filtered to only show items that match the search term.

```php
$favoriteThings = $this->termChoice(
    question: 'Which are your favorite things:',
    items: [
        'raindrops on roses',
        'whiskers on kittens',
        'bright copper kettles',
        'warm woolen mittens',
        'brown paper packages tied up with strings',
        'cream colored ponies',
        'crisp apple strudels',
        'doorbells',
        'sleigh bells',
        'schnitzel with noodles',
    ],
    multiple: true,
    rules: ['required'],
    filterable: true,
);
```

![Demo](examples/choice-filtering.gif)

By default, the `filter` argument will only have an effect if you have over 5 items in your list. You can change this by passing a different number to the `minFilterLength` argument:

```php
$favoriteThings = $this->termChoice(
    question: 'Which are your favorite things:',
    items: [
        'raindrops on roses',
        'whiskers on kittens',
        'bright copper kettles',
        'warm woolen mittens',
        'brown paper packages tied up with strings',
        'cream colored ponies',
        'crisp apple strudels',
        'doorbells',
        'sleigh bells',
        'schnitzel with noodles',
    ],
    multiple: true,
    rules: ['required'],
    filterable: true,
    minFilterLength: 3,
);
```

## Spinner

The `spinner` method allows you to show a spinner while an indefinite process is running. It allows customization to you can inform your user of what's happening as the process runs. The result of the spinner will be whatever is returned from the `task` argument.

It's important to note that the `task` runs in a forked process, so the task itself shouldn't create any side effects in your application. It should just process something and return a result.

### Examples

**Simple:**

```php
$site = $this->termSpinner(
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

**Displays a variable final message based on the result of the task:**

```php
$site = $this->termSpinner(
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

**Updates user of progress as it works:**

```php
$site = $this->termSpinner(
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

**Sends users encouraging messages while they wait:**

```php
$site = $this->termSpinner(
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

## Progress Bars

Progress bars have a very similar API to [Laravel console progress bars](https://laravel.com/docs/artisan#progress-bars), with one small addition: You can pass in an optional title for the bar.

```php
$this->withTermProgressBar(collect(range(1, 20)), function () {
    usleep(300_000);
}, 'Progress is being made...');
```

![Demo](examples/progress-with-title.gif)

```php
$items = range(1, 10);
$progress = $this->createTermProgressBar(count($items), 'Updating users...');

$progress->start();

foreach ($items as $item) {
    $progress->advance();
    usleep(300_000);
}

$progress->finish();
```

![Demo](examples/progress-with-title-manual.gif)

```php
$this->withTermProgressBar(collect(range(1, 20)), function () {
    usleep(300_000);
});
```

![Demo](examples/progress-without-title.gif)

## Note

The `note` method allows you to display a message to the user. You can include an optional title as the second argument, and if you have multiple lines you can pass in an array of strings as the first argument.

```php
// Regular note
$this->termNote(
    "You really did it. We are so proud of you. Thank you for telling us all about yourself. We can't wait to get to know you better.",
    'Congratulations',
);

// Multiple lines via an array
$this->termNote(
    [
        'You really did it. We are so proud of you. Thank you for telling us all about yourself.',
        "We can't wait to get to know you better."
    ],
    'Congratulations',
);

// No title
$this->termNote(
    [
        'You really did it. We are so proud of you. Thank you for telling us all about yourself.',
        "We can't wait to get to know you better."
    ],
);
```
