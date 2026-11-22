<x-layouts.auth :title="__('No organization yet')">
    <div class="flex flex-col gap-6">
        <x-auth-header
            :title="__('You\'re not part of an organization yet')"
            :description="__('Access is by invitation for now. Ask your manager or owner to invite :email, then open the link in that email.', ['email' => auth()->user()->email])" />

        <form method="POST" action="{{ route('logout') }}" class="text-center">
            @csrf
            <flux:button type="submit" variant="ghost">{{ __('Log out') }}</flux:button>
        </form>
    </div>
</x-layouts.auth>
