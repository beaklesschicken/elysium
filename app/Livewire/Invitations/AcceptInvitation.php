<?php

namespace App\Livewire\Invitations;

use App\Models\Invitation;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.auth')]
#[Title('Invitation')]
class AcceptInvitation extends Component
{
    #[Locked]
    public string $token = '';

    public string $name = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(string $token): void
    {
        $this->token = $token;

        if (Auth::guest() && $this->invitation() !== null) {
            session()->put('url.intended', route('invitations.accept', $token));
        }
    }

    /**
     * Signed-in user whose email matches the invitation.
     */
    public function accept(): void
    {
        $invitation = $this->invitation();
        $user = Auth::user();

        abort_if($invitation === null, 404);
        abort_unless($user !== null && $invitation->isFor($user), 403);

        $invitation->accept($user);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }

    /**
     * New person: create their account from the invitation, then accept it.
     */
    public function register(): void
    {
        $invitation = $this->invitation();

        abort_if($invitation === null, 404);
        abort_if(Auth::check() || User::where('email', $invitation->email)->exists(), 403);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = DB::transaction(function () use ($invitation, $validated): User {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $invitation->email,
                'password' => $validated['password'],
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();

            $invitation->accept($user);

            return $user;
        });

        event(new Registered($user));
        Auth::login($user);
        session()->regenerate();

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }

    private function invitation(): ?Invitation
    {
        return Invitation::findPendingByToken($this->token);
    }

    public function render(): View
    {
        $invitation = $this->invitation();
        $user = Auth::user();

        return view('livewire.invitations.accept-invitation', [
            'invitation' => $invitation,
            'organizationName' => $invitation ? Organization::find($invitation->organization_id)?->name : null,
            'signedInUser' => $user,
            'emailMatches' => $user !== null && $invitation?->isFor($user),
            'hasAccount' => $invitation !== null && User::where('email', $invitation->email)->exists(),
        ]);
    }
}
