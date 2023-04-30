<?php

namespace InteractiveConsole\Enums;

enum BlockSymbols: string
{
    case LINE = '│';
    case END = '└';
    case START = '┌';
    case ANSWERED = '◇';
    case ACTIVE = '◆';
    case WARNING = '▲';
    case CANCELED = '■';
}