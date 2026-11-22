<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class ContactForm extends Component
{
    public const MAX_SUBMISSIONS_PER_HOUR = 3;

    public string $name = '';

    public string $email = '';

    public string $business = '';

    public string $zipcode = '';

    public string $comment = '';

    /** Honeypot: hidden from people, bots fill it in. */
    public string $website = '';

    public ?string $success = null;

    /**
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        $singleLine = 'not_regex:/[\r\n<>]/';

        return [
            'name' => ['required', 'string', 'max:100', $singleLine],
            'email' => ['required', 'string', 'email:rfc', 'max:150', $singleLine],
            'business' => ['required', 'string', 'max:150', $singleLine],
            'zipcode' => ['required', 'string', 'regex:/^\d{5}(-\d{4})?$/'],
            'comment' => ['required', 'string', 'min:5', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            '*.not_regex' => 'Please remove line breaks and < > characters.',
            'zipcode.regex' => 'Please enter a 5-digit zipcode.',
        ];
    }

    public function contactFormSubmit(): void
    {
        $this->success = null;

        if ($this->website !== '') {
            $this->pretendSuccess();

            return;
        }

        $rateLimitKey = 'contact-form:'.request()->ip();

        if (RateLimiter::tooManyAttempts($rateLimitKey, self::MAX_SUBMISSIONS_PER_HOUR)) {
            throw ValidationException::withMessages([
                'comment' => 'Too many messages from your connection. Please try again later.',
            ]);
        }

        $details = $this->validate();

        RateLimiter::hit($rateLimitKey, 3600);

        Mail::send('email', $details, function ($message) use ($details): void {
            $message->from(config('mail.from.address'), config('mail.from.name'))
                ->to(config('mail.contact_to'))
                ->replyTo($details['email'], $details['name'])
                ->subject('Contact Form');
        });

        $this->pretendSuccess();
    }

    private function pretendSuccess(): void
    {
        $this->reset('name', 'email', 'business', 'zipcode', 'comment', 'website');
        $this->success = 'Thank you for reaching out to us! We\'ll be in touch!';
    }

    public function render()
    {
        return view('livewire.contact-form');
    }
}
