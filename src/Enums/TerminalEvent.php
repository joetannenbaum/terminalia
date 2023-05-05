<?php

namespace Terminalia\Enums;

enum TerminalEvent: string
{
    case EXIT = 'exit';
    case ESCAPE = 'escape';
}
