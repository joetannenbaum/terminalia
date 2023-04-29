<?php

namespace InteractiveConsole\Enums;

enum ControlSequence: string
{
    case UP = 'A';
    case DOWN = 'B';
    case LEFT = 'C';
    case RIGHT = 'D';
    case BACKSPACE = "\177";
}
