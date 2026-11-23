<?php

use App\Enums\BillingInterval;
use App\Enums\OrganizationRole;
use App\Enums\Plan;
use App\Jobs\SyncSubscriptionQuantity;
use App\Livewire\Employees\Import;
use App\Livewire\Organizations\Billing;
use App\Livewire\Units\UnitForm;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\Support\FakeStripeClient;

beforeEach(function () {
    config([
        'billing.prices.monthly' => 'price_monthly_test',
        'billing.prices.annual' => 'price_annual_test',
        'cashier.webhook.secret' => null,
    ]);
});

afterEach(function () {
    FakeStripeClient::uninstall();
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function stripeSubscriptionPayload(Organization $organization, string $type, array $overrides = []): array
{
    return [
        'id' => 'evt_test',
        'type' => $type,
        'data' => ['object' => array_merge([
            'id' => 'sub_test',
            'object' => 'subscription',
            'customer' => $organization->stripe_id,
            'status' => 'active',
            'metadata' => ['type' => 'default'],
            'cancel_at_period_end' => false,
            'items' => ['data' => [[
                'id' => 'si_test',
                'quantity' => 3,
                'price' => ['id' => 'price_monthly_test', 'product' => 'prod_elysium'],
            ]]],
        ], $overrides)],
    ];
}

describe('plans', function () {
    it('starts new organizations on a 14 day trial with no limits', function () {
        $organization = Organization::factory()->create();

        expect($organization->plan())->toBe(Plan::Trial)
            ->and($organization->trial_ends_at->isSameDay(now()->addDays(14)))->toBeTrue()
            ->and($organization->canAddUnit())->toBeTrue();

        Unit::factory()->for($organization)->count(5)->create();
        expect($organization->canAddUnit())->toBeTrue();
    });

    it('falls back to the free plan when the trial is over', function () {
        $organization = Organization::factory()->onFreePlan()->create();

        expect($organization->plan())->toBe(Plan::Free)
            ->and($organization->canAddUnit())->toBeTrue();

        Unit::factory()->for($organization)->create();

        expect($organization->canAddUnit())->toBeFalse();
    });

    it('counts only active employees against the free limit', function () {
        config(['billing.free.employees' => 2]);
        $organization = Organization::factory()->onFreePlan()->create();
        Employee::factory()->for($organization)->create();
        Employee::factory()->for($organization)->create(['status' => 'inactive']);

        expect($organization->canAddEmployees())->toBeTrue()
            ->and($organization->canAddEmployees(2))->toBeFalse();
    });

    it('has no limits on an active subscription', function () {
        Queue::fake();
        $organization = Organization::factory()->subscribed(BillingInterval::Annual)->create();
        Unit::factory()->for($organization)->count(3)->create();

        expect($organization->plan())->toBe(Plan::Standard)
            ->and($organization->billingInterval())->toBe(BillingInterval::Annual)
            ->and($organization->canAddUnit())->toBeTrue()
            ->and($organization->canAddEmployees(500))->toBeTrue();
    });

    it('treats past due and unpaid subscriptions as the free plan', function (string $status) {
        $organization = Organization::factory()->subscribed(status: $status)->create();

        expect($organization->plan())->toBe(Plan::Free);
    })->with(['past_due', 'unpaid', 'incomplete']);

    it('treats a subscription that has ended as the free plan', function () {
        $organization = Organization::factory()->subscribed(status: 'canceled')->create();
        $organization->subscriptions()->first()->update(['ends_at' => now()->subDay()]);

        expect($organization->fresh()->plan())->toBe(Plan::Free);
    });

    it('keeps a cancelled subscription until the end of the paid period', function () {
        $organization = Organization::factory()->subscribed()->create();
        $organization->subscriptions()->first()->update(['ends_at' => now()->addWeek()]);

        expect($organization->fresh()->plan())->toBe(Plan::Standard);
    });
});

describe('limits in policies', function () {
    beforeEach(function () {
        $this->organization = Organization::factory()->onFreePlan()->create();
        $this->admin = User::factory()->inOrganization($this->organization, OrganizationRole::Admin)->create();
        Unit::factory()->for($this->organization)->create();
    });

    it('stops admins adding a unit beyond the free limit', function () {
        $this->actingAs($this->admin);

        expect($this->admin->can('create', Unit::class))->toBeFalse();
        $this->get(route('units.create'))->assertForbidden();
    });

    it('lets admins add units again once subscribed', function () {
        Queue::fake();
        $organization = Organization::factory()->subscribed()->create();
        $admin = User::factory()->inOrganization($organization, OrganizationRole::Admin)->create();
        Unit::factory()->for($organization)->create();
        $this->actingAs($admin);

        Livewire::test(UnitForm::class)->set('name', 'Second Bar')->call('save')->assertHasNoErrors();

        expect($organization->units()->withoutGlobalScope('organization')->count())->toBe(2);
    });

    it('stops adding employees beyond the free limit', function () {
        config(['billing.free.employees' => 1]);
        Employee::factory()->for($this->organization)->create();
        $this->actingAs($this->admin);

        expect($this->admin->can('create', Employee::class))->toBeFalse();
        $this->get(route('employees.create'))->assertForbidden();
    });

    it('rejects a csv import that would go over the free limit', function () {
        config(['billing.free.employees' => 2]);
        $this->actingAs($this->admin);

        Livewire::test(Import::class)
            ->set('file', UploadedFile::fake()->createWithContent('employees.csv', "Name\nAva\nBen\nCal\n"))
            ->call('import')
            ->assertSet('importedCount', 0)
            ->assertSee('The Free plan allows 2 active employees.');

        expect(Employee::count())->toBe(0);
    });

    it('still lets members edit existing records over the limit', function () {
        config(['billing.free.employees' => 1]);
        $employee = Employee::factory()->for($this->organization)->create();
        Employee::factory()->for($this->organization)->create();
        $this->actingAs($this->admin);

        expect($this->admin->can('update', $employee))->toBeTrue();
    });

    it('shows the limit callout on the units page', function () {
        $owner = User::factory()->inOrganization($this->organization, OrganizationRole::Owner)->create();

        $this->actingAs($owner)->get(route('units.index'))
            ->assertSuccessful()
            ->assertSee('The Free plan includes 1 unit.')
            ->assertSee(route('organization.billing'));
    });
});

describe('billing page', function () {
    beforeEach(function () {
        $this->organization = Organization::factory()->create(['name' => 'Hoosier Hospitality']);
        $this->owner = User::factory()->inOrganization($this->organization, OrganizationRole::Owner)->create();
        Unit::factory()->for($this->organization)->count(2)->create();
    });

    it('is only for owners', function (OrganizationRole $role) {
        $user = User::factory()->inOrganization($this->organization, $role)->create();

        $this->actingAs($user)->get(route('organization.billing'))->assertForbidden();
        $this->actingAs($user)->post(route('billing.checkout'), ['interval' => 'monthly'])->assertForbidden();
        $this->actingAs($user)->get(route('billing.portal'))->assertForbidden();
    })->with([OrganizationRole::Admin, OrganizationRole::Manager]);

    it('shows the trial and per unit price estimate', function () {
        $this->actingAs($this->owner);

        Livewire::test(Billing::class)
            ->assertSee('Free trial')
            ->assertSee('$29')
            ->assertSee('2 units today: $58 / month.')
            ->set('interval', 'annual')
            ->assertSee('$290')
            ->assertSee('2 units today: $580 / year.');
    });

    it('rejects an unknown interval', function () {
        $this->actingAs($this->owner);

        Livewire::test(Billing::class)->set('interval', 'weekly')->assertHasErrors('interval');
        $this->post(route('billing.checkout'), ['interval' => 'weekly'])->assertSessionHasErrors('interval');
    });

    it('shows the subscription and a portal link when subscribed', function () {
        $organization = Organization::factory()->subscribed(BillingInterval::Monthly, 2)->create();
        $owner = User::factory()->inOrganization($organization, OrganizationRole::Owner)->create();
        $this->actingAs($owner);

        Livewire::test(Billing::class)
            ->assertSee('Standard')
            ->assertSee('2 units billed monthly.')
            ->assertSee(route('billing.portal'))
            ->assertDontSee('Subscribe');
    });

    it('appears in the sidebar for owners only', function () {
        $admin = User::factory()->inOrganization($this->organization, OrganizationRole::Admin)->create();

        $this->actingAs($this->owner)->get(route('dashboard'))->assertSee(route('organization.billing'));
        $this->actingAs($admin)->get(route('dashboard'))->assertDontSee(route('organization.billing'));
    });
});

describe('stripe checkout and portal', function () {
    beforeEach(function () {
        $this->organization = Organization::factory()->create(['name' => 'Hoosier Hospitality']);
        $this->owner = User::factory()->inOrganization($this->organization, OrganizationRole::Owner)->create(['email' => 'owner@example.com']);
        Unit::factory()->for($this->organization)->count(3)->create();
    });

    it('creates a customer and sends the owner to checkout for each unit', function () {
        $stripe = FakeStripeClient::install([
            'POST /v1/customers' => ['id' => 'cus_test', 'object' => 'customer'],
            'POST /v1/checkout/sessions' => ['id' => 'cs_test', 'object' => 'checkout.session', 'url' => 'https://checkout.stripe.com/c/pay/cs_test'],
        ]);

        $this->actingAs($this->owner)
            ->post(route('billing.checkout'), ['interval' => 'annual'])
            ->assertRedirect('https://checkout.stripe.com/c/pay/cs_test');

        expect($this->organization->fresh()->stripe_id)->toBe('cus_test');

        $customer = $stripe->lastRequestTo('POST', '/v1/customers');
        expect($customer['params']['name'])->toBe('Hoosier Hospitality')
            ->and($customer['params']['email'])->toBe('owner@example.com');

        $session = $stripe->lastRequestTo('POST', '/v1/checkout/sessions');
        expect($session['params']['mode'])->toBe('subscription')
            ->and($session['params']['customer'])->toBe('cus_test')
            ->and($session['params']['line_items'][0])->toMatchArray(['price' => 'price_annual_test', 'quantity' => 3])
            ->and($session['params']['subscription_data']['trial_end'])->toBe($this->organization->trial_ends_at->getTimestamp())
            ->and($session['params']['success_url'])->toContain('checkout=success');
    });

    it('charges right away when the trial is over', function () {
        $this->organization->forceFill(['trial_ends_at' => now()->subDay(), 'stripe_id' => 'cus_existing'])->save();
        $stripe = FakeStripeClient::install([
            'GET /v1/customers/cus_existing' => ['id' => 'cus_existing', 'object' => 'customer'],
            'POST /v1/checkout/sessions' => ['id' => 'cs_test', 'object' => 'checkout.session', 'url' => 'https://checkout.stripe.com/c/pay/cs_test'],
        ]);

        $this->actingAs($this->owner)->post(route('billing.checkout'), ['interval' => 'monthly'])->assertRedirect();

        $session = $stripe->lastRequestTo('POST', '/v1/checkout/sessions');
        expect($session['params']['subscription_data'] ?? [])->not->toHaveKey('trial_end')
            ->and($stripe->lastRequestTo('POST', '/v1/customers'))->toBeNull();
    });

    it('does not start a second subscription', function () {
        $organization = Organization::factory()->subscribed()->create();
        $owner = User::factory()->inOrganization($organization, OrganizationRole::Owner)->create();
        $stripe = FakeStripeClient::install();

        $this->actingAs($owner)->post(route('billing.checkout'), ['interval' => 'monthly'])->assertRedirect(route('organization.billing'));

        expect($stripe->requests)->toBe([]);
    });

    it('explains when stripe prices are not configured', function () {
        config(['billing.prices.monthly' => null]);
        $stripe = FakeStripeClient::install();

        $this->actingAs($this->owner)->post(route('billing.checkout'), ['interval' => 'monthly'])
            ->assertRedirect(route('organization.billing'))
            ->assertSessionHasErrors('interval');

        expect($stripe->requests)->toBe([]);
    });

    it('opens the stripe customer portal', function () {
        $this->organization->forceFill(['stripe_id' => 'cus_existing'])->save();
        $stripe = FakeStripeClient::install([
            'POST /v1/billing_portal/sessions' => ['id' => 'bps_test', 'object' => 'billing_portal.session', 'url' => 'https://billing.stripe.com/p/session/test'],
        ]);

        $this->actingAs($this->owner)->get(route('billing.portal'))->assertRedirect('https://billing.stripe.com/p/session/test');

        expect($stripe->lastRequestTo('POST', '/v1/billing_portal/sessions')['params'])
            ->toMatchArray(['customer' => 'cus_existing', 'return_url' => route('organization.billing')]);
    });

    it('sends owners without a stripe customer back to the billing page', function () {
        $this->actingAs($this->owner)->get(route('billing.portal'))->assertRedirect(route('organization.billing'));
    });
});

describe('subscription quantity', function () {
    it('queues a quantity sync when units are added or deleted', function () {
        Queue::fake();
        $organization = Organization::factory()->subscribed()->create();

        $unit = Unit::factory()->for($organization)->create();
        $unit->delete();

        Queue::assertPushed(SyncSubscriptionQuantity::class, 2);
        Queue::assertPushed(SyncSubscriptionQuantity::class, fn (SyncSubscriptionQuantity $job): bool => $job->organizationId === $organization->id);
    });

    it('updates the stripe quantity to the unit count', function () {
        Queue::fake();
        $organization = Organization::factory()->subscribed(quantity: 1)->create();
        Unit::factory()->for($organization)->count(3)->create();
        $subscription = $organization->subscriptions()->first();
        $stripe = FakeStripeClient::install([
            "POST /v1/subscriptions/{$subscription->stripe_id}" => ['id' => $subscription->stripe_id, 'object' => 'subscription', 'status' => 'active', 'quantity' => 3],
        ]);

        (new SyncSubscriptionQuantity($organization->id))->handle();

        expect($stripe->lastRequestTo('POST', '/v1/subscriptions/')['params']['quantity'])->toBe(3)
            ->and($subscription->fresh()->quantity)->toBe(3)
            ->and($subscription->items()->first()->quantity)->toBe(3);
    });

    it('does nothing when the quantity already matches or there is no subscription', function () {
        Queue::fake();
        $subscribed = Organization::factory()->subscribed(quantity: 2)->create();
        Unit::factory()->for($subscribed)->count(2)->create();
        $trial = Organization::factory()->create();
        $stripe = FakeStripeClient::install();

        (new SyncSubscriptionQuantity($subscribed->id))->handle();
        (new SyncSubscriptionQuantity($trial->id))->handle();

        expect($stripe->requests)->toBe([]);
    });
});

describe('stripe webhooks', function () {
    beforeEach(function () {
        $this->organization = Organization::factory()->create();
        $this->organization->forceFill(['stripe_id' => 'cus_webhook'])->save();
    });

    it('records the subscription when checkout completes and ends the trial', function () {
        $this->postJson('/stripe/webhook', stripeSubscriptionPayload($this->organization, 'customer.subscription.created'))->assertSuccessful();

        $organization = $this->organization->fresh();
        expect($organization->plan())->toBe(Plan::Standard)
            ->and($organization->trial_ends_at)->toBeNull()
            ->and($organization->activeSubscription()->quantity)->toBe(3)
            ->and($organization->billingInterval())->toBe(BillingInterval::Monthly);
    });

    it('drops to the free plan when a payment fails', function () {
        $this->postJson('/stripe/webhook', stripeSubscriptionPayload($this->organization, 'customer.subscription.created'))->assertSuccessful();
        $this->postJson('/stripe/webhook', stripeSubscriptionPayload($this->organization, 'customer.subscription.updated', ['status' => 'past_due']))->assertSuccessful();

        expect($this->organization->fresh()->plan())->toBe(Plan::Free);
    });

    it('drops to the free plan when the subscription is deleted', function () {
        $this->postJson('/stripe/webhook', stripeSubscriptionPayload($this->organization, 'customer.subscription.created'))->assertSuccessful();
        $this->postJson('/stripe/webhook', stripeSubscriptionPayload($this->organization, 'customer.subscription.deleted', ['status' => 'canceled']))->assertSuccessful();

        expect($this->organization->fresh()->plan())->toBe(Plan::Free);
    });

    it('ignores events for unknown customers', function () {
        $payload = stripeSubscriptionPayload($this->organization, 'customer.subscription.created', ['customer' => 'cus_someone_else']);

        $this->postJson('/stripe/webhook', $payload)->assertSuccessful();

        expect($this->organization->fresh()->subscriptions()->count())->toBe(0);
    });

    it('rejects unsigned webhooks when a signing secret is set', function () {
        config(['cashier.webhook.secret' => 'whsec_test']);

        $this->postJson('/stripe/webhook', stripeSubscriptionPayload($this->organization, 'customer.subscription.created'))->assertForbidden();

        expect($this->organization->fresh()->subscriptions()->count())->toBe(0);
    });
});
