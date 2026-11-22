<?php

namespace App\Livewire\Concerns;

use Illuminate\Support\Str;

/**
 * Optional street address fields shared by organizations and units.
 */
trait HasAddressFields
{
    public string $address = '';

    public string $city = '';

    public string $state_province = '';

    public string $postcode = '';

    /**
     * @return array<string, array<int, string>>
     */
    protected function addressRules(): array
    {
        return [
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state_province' => ['nullable', 'string', 'regex:/^[A-Za-z]{2}$/'],
            'postcode' => ['nullable', 'string', 'regex:/^\d{5}(-\d{4})?$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function addressMessages(): array
    {
        return [
            'state_province.regex' => __('Use the two-letter state code, like IN.'),
            'postcode.regex' => __('Please enter a 5-digit zipcode.'),
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function normalizeAddress(array $validated): array
    {
        foreach (['address', 'city', 'state_province', 'postcode'] as $field) {
            $validated[$field] = filled($validated[$field] ?? null) ? trim($validated[$field]) : null;
        }

        if ($validated['state_province'] !== null) {
            $validated['state_province'] = Str::upper($validated['state_province']);
        }

        return $validated;
    }

    /**
     * @param  object{address: ?string, city: ?string, state_province: ?string, postcode: ?string}  $model
     */
    protected function fillAddressFrom(object $model): void
    {
        $this->address = $model->address ?? '';
        $this->city = $model->city ?? '';
        $this->state_province = $model->state_province ?? '';
        $this->postcode = $model->postcode ?? '';
    }
}
