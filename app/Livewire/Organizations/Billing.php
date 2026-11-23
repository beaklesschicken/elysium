<?php

namespace App\Livewire\Organizations;

use App\Enums\BillingInterval;
use App\Models\Organization;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Billing')]
class Billing extends Component
{
    public string $interval = 'monthly';

    /** Set to "success" when Stripe Checkout sends the owner back. */
    #[Url(as: 'checkout', except: '')]
    public string $checkoutResult = '';

    public function mount(): void
    {
        $this->authorize('manageBilling', $this->organization());
    }

    public function updatedInterval(): void
    {
        $this->validate(['interval' => ['required', Rule::enum(BillingInterval::class)]]);
    }

    protected function organization(): Organization
    {
        return Auth::user()->currentOrganization;
    }

    public function render(): View
    {
        $organization = $this->organization();
        $selectedInterval = BillingInterval::tryFrom($this->interval) ?? BillingInterval::Monthly;
        $unitCount = $organization->billableUnitCount();

        return view('livewire.organizations.billing', [
            'organization' => $organization,
            'plan' => $organization->plan(),
            'subscription' => $organization->activeSubscription(),
            'currentInterval' => $organization->billingInterval(),
            'selectedInterval' => $selectedInterval,
            'intervals' => BillingInterval::cases(),
            'unitCount' => $unitCount,
            'activeEmployeeCount' => $organization->activeEmployeeCount(),
            'estimatedTotal' => $unitCount * $selectedInterval->unitPrice(),
            'freeUnitLimit' => (int) config('billing.free.units'),
            'freeEmployeeLimit' => (int) config('billing.free.employees'),
        ]);
    }
}
