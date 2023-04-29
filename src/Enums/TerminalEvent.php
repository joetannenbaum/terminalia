<?php

namespace InteractiveConsole\Enums;

enum TerminalEvent: string
{
    case EXIT = 'exit';
    case ESCAPE = 'escape';
}
