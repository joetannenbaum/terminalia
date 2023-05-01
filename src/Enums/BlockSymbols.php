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
    case HORIZONTAL = '─';
    case CORNER_TOP_RIGHT = '╮';
    case CORNER_BOTTOM_RIGHT = '╯';
    case CONNECT_LEFT = '├';
}
