<?php

namespace Terminalia\Helpers;

use Illuminate\Support\Collection;

class Choices
{
    protected Collection $items;

    public function __construct(
        iterable $items,
        protected mixed $displayKey,
        protected mixed $returnKey = null,
    ) {
        $this->items = collect($items);
    }

    public static function from(
        iterable $items,
        string|callable $displayKey,
        string|callable $returnKey = null
    ): static {
        return new static($items, $displayKey, $returnKey);
    }

    public function choices(): Collection
    {
        if (is_string($this->displayKey)) {
            return $this->items->pluck($this->displayKey);
        }

        return $this->items->map($this->displayKey);
    }

    public function getKeysFromValues(Collection $values): Collection
    {
        return $this->value($this->items->keys())->filter(fn ($i) => $values->contains($i))->keys();
    }

    public function value(Collection $indexes): mixed
    {
        $answer = $this->items->only($indexes);

        if ($this->returnKey === null) {
            return $answer;
        }

        if (is_string($this->returnKey)) {
            return $answer->pluck($this->returnKey);
        }

        if (is_callable($this->returnKey)) {
            return $answer->map($this->returnKey);
        }

        return $answer;
    }
}
