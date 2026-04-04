<?php

namespace Spatie\LaravelMobilePass\Builders\Apple\Validators;

abstract class ApplePassValidator
{
    protected function rules(): array
    {
        return [
            'description' => ['required', 'string'],
            'formatVersion' => ['required', 'integer', 'in:1'],
            'organizationName' => ['required', 'string'],
            'passTypeIdentifier' => ['required', 'string'],
            'serialNumber' => ['required', 'string'],
            'webServiceURL' => ['nullable', 'string'],
            'authenticationToken' => ['nullable', 'string', 'min:16'],
            'teamIdentifier' => ['required', 'string'],
            'logoText' => ['nullable', 'string'],

            'barcodes' => ['nullable', 'array'],
            'semantics' => ['nullable', 'array'],

            'foregroundColor' => ['nullable', 'string'],
            'backgroundColor' => ['nullable', 'string'],
            'labelColor' => ['nullable', 'string'],

            'iconImagePath' => ['nullable', 'string'],
            'icon@2xImagePath' => ['nullable', 'string'],
            'icon@3xImagePath' => ['nullable', 'string'],
            'logoImagePath' => ['nullable', 'string'],
            'logo@2xImagePath' => ['nullable', 'string'],
            'logo@3xImagePath' => ['nullable', 'string'],
        ];
    }

    public function validate(array $compiledData): array
    {
        return validator($compiledData, $this->rules())->validate();
    }
}
