<?php

namespace Terminalia\Enums;

enum BlockSymbols
{
    case LINE;
    case END;
    case START;
    case ANSWERED;
    case ACTIVE;
    case WARNING;
    case CANCELED;
    case HORIZONTAL;
    case CORNER_TOP_RIGHT;
    case CORNER_BOTTOM_RIGHT;
    case CONNECT_LEFT;
    case RADIO_SELECTED;
    case RADIO_UNSELECTED;
    case CHECKBOX_SELECTED;
    case CHECKBOX_UNSELECTED;

    public function symbol(): string
    {
        return match ($this) {
            self::LINE                => '│',
            self::END                 => '└',
            self::START               => '┌',
            self::ANSWERED            => '◇',
            self::ACTIVE              => '◆',
            self::WARNING             => '▲',
            self::CANCELED            => '■',
            self::HORIZONTAL          => '─',
            self::CORNER_TOP_RIGHT    => '╮',
            self::CORNER_BOTTOM_RIGHT => '╯',
            self::CONNECT_LEFT        => '├',
            self::RADIO_SELECTED      => '●',
            self::RADIO_UNSELECTED    => '○',
            self::CHECKBOX_SELECTED   => '■',
            self::CHECKBOX_UNSELECTED => '□',
        };
    }
}
