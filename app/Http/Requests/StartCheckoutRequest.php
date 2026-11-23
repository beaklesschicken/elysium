<?php

namespace App\Http\Requests;

use App\Enums\BillingInterval;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StartCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        $organization = $this->user()?->currentOrganization;

        return $organization !== null && $this->user()->can('manageBilling', $organization);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'interval' => ['required', Rule::enum(BillingInterval::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'interval.required' => __('Choose monthly or annual billing.'),
            'interval.enum' => __('Choose monthly or annual billing.'),
        ];
    }

    public function billingInterval(): BillingInterval
    {
        return BillingInterval::from($this->validated('interval'));
    }
}
