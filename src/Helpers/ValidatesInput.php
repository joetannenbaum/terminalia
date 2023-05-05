<?php

namespace Terminalia\Helpers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

trait ValidatesInput
{
    protected string|array $rules = [];

    public function setRules(string|array $rules): static
    {
        $this->rules = $rules;

        return $this;
    }

    protected function validate($value): ?string
    {
        if (!isset($this->rules)) {
            return null;
        }

        if ($this->rules) {
            try {
                Validator::make(
                    ['value' => $value],
                    ['value' => $this->rules],
                )->validate();
            } catch (ValidationException $e) {
                return $e->getMessage();
            }
        }

        return null;
    }
}
