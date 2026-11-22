<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * Makes someone a site admin, creating their account first if needed.
 * The password is typed at a hidden prompt and never passed on the command line.
 */
class CreateSiteAdmin extends Command
{
    protected $signature = 'users:site-admin {email : The site admin\'s email address}';

    protected $description = 'Create a site admin account, or promote an existing user to site admin';

    public function handle(): int
    {
        $email = strtolower(trim($this->argument('email')));

        if (Validator::make(['email' => $email], ['email' => ['required', 'email:rfc', 'max:255']])->fails()) {
            $this->error('That is not a valid email address.');

            return self::FAILURE;
        }

        $user = User::firstWhere('email', $email);

        if ($user === null) {
            $name = text(label: 'Name', required: true, validate: ['name' => 'max:255']);
            $password = password(label: 'Password', required: true, validate: ['password' => Password::defaults()]);

            $user = User::create(['name' => $name, 'email' => $email, 'password' => $password]);
            $this->info("Created {$email}.");
        }

        $user->forceFill([
            'is_site_admin' => true,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        $this->info("{$email} is a site admin. Log in and create the first organization.");

        return self::SUCCESS;
    }
}
