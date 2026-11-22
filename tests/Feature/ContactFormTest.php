<?php

use App\Livewire\ContactForm;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function () {
    config([
        'mail.contact_to' => 'owner@example.test',
        'mail.from.address' => 'no-reply@example.test',
    ]);
    RateLimiter::clear('contact-form:127.0.0.1');
});

/**
 * @return array<string, string>
 */
function validContact(): array
{
    return [
        'name' => 'Pat Doe',
        'email' => 'pat@example.test',
        'business' => 'Pat\'s Diner',
        'zipcode' => '43215',
        'comment' => 'We would like a quote, please.',
    ];
}

function fillContactForm(array $fields): Testable
{
    $component = Livewire::test(ContactForm::class);

    foreach ($fields as $field => $value) {
        $component->set($field, $value);
    }

    return $component;
}

it('emails a valid message to the configured address with a reply-to', function () {
    $sent = [];
    Event::listen(MessageSent::class, function (MessageSent $event) use (&$sent) {
        $sent[] = $event->message;
    });

    fillContactForm(validContact())
        ->call('contactFormSubmit')
        ->assertHasNoErrors()
        ->assertSet('name', '')
        ->assertSet('success', fn ($value) => str_contains($value, 'Thank you'));

    expect($sent)->toHaveCount(1);
    $email = $sent[0];
    expect($email->getTo()[0]->getAddress())->toBe('owner@example.test')
        ->and($email->getFrom()[0]->getAddress())->toBe('no-reply@example.test')
        ->and($email->getReplyTo()[0]->getAddress())->toBe('pat@example.test')
        ->and($email->getHtmlBody())->toContain('Pat Doe');
});

it('escapes HTML in the emailed message', function () {
    $sent = [];
    Event::listen(MessageSent::class, function (MessageSent $event) use (&$sent) {
        $sent[] = $event->message;
    });

    fillContactForm([...validContact(), 'comment' => '<script>alert(1)</script> hello'])
        ->call('contactFormSubmit')
        ->assertHasNoErrors();

    expect($sent[0]->getHtmlBody())->not->toContain('<script>')
        ->toContain('&lt;script&gt;');
});

it('rejects invalid input', function (string $field, string $value) {
    fillContactForm([...validContact(), $field => $value])
        ->call('contactFormSubmit')
        ->assertHasErrors($field);
})->with([
    'missing name' => ['name', ''],
    'newline in name (header injection)' => ['name', "Pat\r\nBcc: victim@example.test"],
    'tag in business' => ['business', '<b>Diner</b>'],
    'bad email' => ['email', 'not-an-email'],
    'newline in email' => ['email', "pat@example.test\nBcc: x@example.test"],
    'bad zipcode' => ['zipcode', 'abcde'],
    'short comment' => ['comment', 'hi'],
    'huge comment' => ['comment', str_repeat('a', 5001)],
    'huge name' => ['name', str_repeat('a', 101)],
]);

it('sends nothing when validation fails', function () {
    Event::fake([MessageSent::class]);

    fillContactForm([...validContact(), 'email' => 'nope'])->call('contactFormSubmit');

    Event::assertNotDispatched(MessageSent::class);
});

it('quietly drops honeypot submissions', function () {
    Event::fake([MessageSent::class]);

    fillContactForm([...validContact(), 'website' => 'http://spam.example'])
        ->call('contactFormSubmit')
        ->assertHasNoErrors()
        ->assertSet('success', fn ($value) => str_contains($value, 'Thank you'));

    Event::assertNotDispatched(MessageSent::class);
});

it('limits each IP to a few messages per hour', function () {
    Event::fake([MessageSent::class]);

    foreach (range(1, ContactForm::MAX_SUBMISSIONS_PER_HOUR) as $attempt) {
        fillContactForm(validContact())->call('contactFormSubmit')->assertHasNoErrors();
    }

    fillContactForm(validContact())
        ->call('contactFormSubmit')
        ->assertHasErrors('comment');

    Event::assertDispatchedTimes(MessageSent::class, ContactForm::MAX_SUBMISSIONS_PER_HOUR);
});

it('does not count failed validation against the limit', function () {
    Event::fake([MessageSent::class]);

    foreach (range(1, 5) as $attempt) {
        fillContactForm([...validContact(), 'email' => 'nope'])->call('contactFormSubmit');
    }

    fillContactForm(validContact())->call('contactFormSubmit')->assertHasNoErrors();

    Event::assertDispatchedTimes(MessageSent::class, 1);
});
