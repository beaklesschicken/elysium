<div>
    <section>
        @if ($success)
            <div class="mb-6 border-l-2 border-gold bg-gold/10 px-4 py-3">
                <p class="text-sm font-medium text-gold-light">Thank you</p>
                <p class="mt-1 text-sm text-silver/80">{{ $success }}</p>
            </div>
        @endif
        <form wire:submit.prevent="contactFormSubmit" action="/contact" method="POST" class="grid gap-5 sm:grid-cols-2">
            @csrf
            <div class="hidden" aria-hidden="true">
                <label for="contact-website">Leave this field empty</label>
                <input id="contact-website" type="text" wire:model="website" name="website" tabindex="-1" autocomplete="off" />
            </div>
            <div class="sm:col-span-2">
                <flux:input icon="envelope" type="email" wire:model="email" name="email" placeholder="Email address" />
                @error('email')
                    <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <flux:input icon="user" type="text" wire:model="name" placeholder="Your name" />
                @error('name')
                    <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <flux:input icon="building" type="text" wire:model="business" placeholder="Business name" />
                @error('business')
                    <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>
            <div class="sm:col-span-2">
                <flux:input icon="map-pinned" type="text" wire:model="zipcode" placeholder="Zipcode" />
                @error('zipcode')
                    <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>
            <div class="sm:col-span-2">
                <flux:textarea wire:model="comment" name="comment" rows="6" placeholder="Your message here..." />
                @error('comment')
                    <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>
            <div class="sm:col-span-2">
                <button type="submit" wire:loading.attr="disabled" class="bg-gold px-8 py-3 text-sm font-medium tracking-wide text-ink transition hover:bg-gold-light focus:outline-none focus-visible:ring-2 focus-visible:ring-gold-light focus-visible:ring-offset-2 focus-visible:ring-offset-ink disabled:opacity-60">
                    <span wire:loading.remove wire:target="contactFormSubmit">Send message</span>
                    <span wire:loading wire:target="contactFormSubmit">Sending…</span>
                </button>
            </div>
        </form>
    </section>
</div>
