<?php

namespace InteractiveConsole\Helpers;

enum TerminalEvent: string
{
    case EXIT = 'exit';
    case ESCAPE = 'escape';
}
