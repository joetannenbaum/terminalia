<?php

namespace Terminalia\Helpers;

trait ListensForInput
{
    protected function inputListener(): InputListener
    {
        return new InputListener($this->inputStream);
    }
}
