<?php

namespace InteractiveConsole\Helpers;

trait ListensForInput
{
    protected function inputListener()
    {
        return new InputListener($this->inputStream);
    }
}
