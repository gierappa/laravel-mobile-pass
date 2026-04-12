<?php

namespace Spatie\LaravelMobilePass\Builders\Google;

use Chiiya\Passes\Google\Components\Common\TextModuleData;
use Chiiya\Passes\Google\Enumerators\State;
use Chiiya\Passes\Google\ServiceCredentials;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\LaravelMobilePass\Builders\Apple\Entities\FieldContent;
use Spatie\LaravelMobilePass\Builders\Apple\Entities\Image;
use Spatie\LaravelMobilePass\Enums\Platform;
use Spatie\LaravelMobilePass\Models\MobilePass;

abstract class GooglePassBuilder
{
    protected ?string $serialNumber = null;
    protected ?string $organisationName = null;
    protected ?string $description = null;
    protected ?Collection $headerFields = null;
    protected ?Collection $primaryFields = null;
    protected ?Collection $secondaryFields = null;
    protected ?Collection $auxiliaryFields = null;
    protected ?Image $logoImage = null;
    protected ?Image $iconImage = null;
    protected ?string $hexBackgroundColor = null;
    protected ?array $validTimeInterval = null;
    protected string $state = State::ACTIVE;

    public function __construct(
        protected array $data = [],
        protected array $images = [],
        protected ?MobilePass $model = null
    ) {
        $this->headerFields = collect();
        $this->primaryFields = collect();
        $this->secondaryFields = collect();
        $this->auxiliaryFields = collect();
        $this->serialNumber = $data['serialNumber'] ?? null;
        $this->organisationName = $data['organisationName'] ?? null;
        $this->description = $data['description'] ?? null;
        $this->hexBackgroundColor = $data['hexBackgroundColor'] ?? null;
        $this->state = $data['state'] ?? State::ACTIVE;
    }

    public static function make(array $data = [], array $images = [], ?MobilePass $model = null): static
    {
        return new static($data, $images, $model);
    }

    public static function name(): string
    {
        return Str::snake(class_basename(static::class));
    }

    public function platform(): Platform
    {
        return Platform::Google;
    }

    public function setState(string $state): self
    {
        $this->state = $state;

        return $this;
    }

    public function void(): void
    {
        $this->setState(State::EXPIRED)->generate();
        $this->save();
    }

    public function setSerialNumber(string $serialNumber): self
    {
        $this->serialNumber = $serialNumber;

        return $this;
    }

    public function setOrganisationName(string $organisationName): self
    {
        $this->organisationName = $organisationName;

        return $this;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function setHeaderFields(FieldContent ...$headerFields): self
    {
        $this->headerFields->push(...$headerFields);

        return $this;
    }

    public function setPrimaryFields(FieldContent ...$primaryFields): self
    {
        $this->primaryFields->push(...$primaryFields);

        return $this;
    }

    public function setSecondaryFields(FieldContent ...$secondaryFields): self
    {
        $this->secondaryFields->push(...$secondaryFields);

        return $this;
    }

    public function setAuxiliaryFields(FieldContent ...$auxiliaryFields): self
    {
        $this->auxiliaryFields->push(...$auxiliaryFields);

        return $this;
    }

    public function setLogoImage(Image $image): self
    {
        $this->logoImage = $image;

        return $this;
    }

    public function setIconImage(Image $image): self
    {
        $this->iconImage = $image;

        return $this;
    }

    public function setHexBackgroundColor(string $color): self
    {
        $this->hexBackgroundColor = $color;

        return $this;
    }

    public function setValidTimeInterval(\DateTimeInterface $start, \DateTimeInterface $end): self
    {
        $this->validTimeInterval = [
            'start' => $start,
            'end' => $end,
        ];

        return $this;
    }

    abstract public function generate(): string;

    protected function getServiceCredentials(): ServiceCredentials
    {
        $clientId = config('mobile-pass.google.client_id');
        $clientEmail = config('mobile-pass.google.client_email');
        $privateKey = config('mobile-pass.google.private_key');

        if (empty($clientId)) {
            throw new \RuntimeException('Missing Google Service Client ID in mobile-pass config.');
        }

        if (empty($clientEmail)) {
            throw new \RuntimeException('Missing Google Service Client Email in mobile-pass config.');
        }

        if (empty($privateKey)) {
            throw new \RuntimeException('Missing Google Service Private Key in mobile-pass config.');
        }

        return new ServiceCredentials(
            client_id: $clientId,
            client_email: $clientEmail,
            private_key: $privateKey
        );
    }

    protected function getIssuerId(): string
    {
        return config('mobile-pass.google.issuer_id', '3388000000023113223');
    }

    protected function mapFieldsToTextModules(): array
    {
        $modules = [];
        $allFields = collect()
            ->merge($this->headerFields)
            ->merge($this->primaryFields)
            ->merge($this->secondaryFields)
            ->merge($this->auxiliaryFields);

        foreach ($allFields as $field) {
            $modules[] = new TextModuleData(
                header: $field->label ?? '',
                body: $field->value ?? '',
                id: $field->key
            );
        }

        return $modules;
    }

    public function save(): MobilePass
    {
        $content = [
            'serialNumber' => $this->serialNumber,
            'organisationName' => $this->organisationName,
            'description' => $this->description,
            'hexBackgroundColor' => $this->hexBackgroundColor,
            'state' => $this->state,
            'headerFields' => $this->headerFields->map->toArray()->toArray(),
            'primaryFields' => $this->primaryFields->map->toArray()->toArray(),
            'secondaryFields' => $this->secondaryFields->map->toArray()->toArray(),
            'auxiliaryFields' => $this->auxiliaryFields->map->toArray()->toArray(),
        ];

        if ($this->model) {
            $this->model->update([
                'serial_number' => $this->serialNumber,
                'content' => $content,
                'images' => $this->images,
            ]);

            return $this->model;
        }

        return MobilePass::query()->create([
            'serial_number' => $this->serialNumber,
            'type' => 'coupon', // Tymczasowo na sztywno, bo Google ma inne typy niż Apple
            'platform' => static::platform(),
            'builder_name' => static::name(),
            'content' => $content,
            'images' => $this->images,
        ]);
    }
}
