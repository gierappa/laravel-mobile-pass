<?php

namespace Spatie\LaravelMobilePass\Builders\Apple\Entities;

use Illuminate\Contracts\Support\Arrayable;

class Location implements Arrayable
{
    public function __construct(
        public float $latitude,
        public float $longitude,
        public ?string $relevantText = null,
    ) {}

    public static function make(
        float $latitude,
        float $longitude,
        ?string $relevantText = null,
    ): self {
        return new self(
            latitude: $latitude,
            longitude: $longitude,
            relevantText: $relevantText,
        );
    }

    public static function fromArray(array $values): self
    {
        return new self(
            latitude: (float) $values['latitude'],
            longitude: (float) $values['longitude'],
            relevantText: $values['relevantText'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'relevantText' => $this->relevantText,
        ], fn ($v) => $v !== null);
    }
}
