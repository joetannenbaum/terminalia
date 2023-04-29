<?php

namespace InteractiveConsole\Helpers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

trait ValidatesInput
{
    public function setValidator(string|array $validator): static
    {
        $this->validator = $validator;

        return $this;
    }

    protected function validate($value): ?string
    {
        if (!isset($this->validator)) {
            return null;
        }

        if ($this->validator) {
            try {
                Validator::make(
                    ['value' => $value],
                    ['value' => $this->validator],
                )->validate();
            } catch (ValidationException $e) {
                return $e->getMessage();
            }
        }

        return null;
    }
}
