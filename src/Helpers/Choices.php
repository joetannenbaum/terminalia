<?php

namespace Terminalia\Helpers;

use Illuminate\Support\Collection;

class Choices
{
    protected Collection $items;

    protected bool $returnAsArray = false;

    protected Collection $cachedChoices;

    public function __construct(
        iterable $items,
        protected mixed $displayKey,
        protected mixed $returnKey = null,
    ) {
        $this->returnAsArray = is_array($items);
        $this->items = collect($items);
    }

    public static function from(
        iterable $items,
        string|callable $displayKey = null,
        string|callable $returnKey = null
    ): static {
        return new static($items, $displayKey, $returnKey);
    }

    public function choices(): Collection
    {
        if (isset($this->cachedChoices)) {
            return $this->cachedChoices;
        }

        if ($this->displayKey === null) {
            $this->cachedChoices = $this->items;

            return $this->cachedChoices;
        }

        if (is_string($this->displayKey)) {
            $this->cachedChoices = $this->items->pluck($this->displayKey);

            return $this->cachedChoices;
        }

        $this->cachedChoices = $this->items->map($this->displayKey);

        return $this->cachedChoices;
    }

    public function returnAsArray(): bool
    {
        return $this->returnAsArray;
    }

    public function getSelectedFromDefault(iterable|string $default): Collection
    {
        $default = collect(is_array($default) ? $default : [$default]);

        return $this->value($this->items->keys())->filter(fn ($i) => $default->contains($i))->keys();
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
