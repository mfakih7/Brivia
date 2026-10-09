<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/** Interactive bootstrap of the first owner. Passwords are never accepted as arguments. */
class CreateOwner extends Command
{
    protected $signature = 'brivia:create-owner';

    protected $description = 'Interactively create the first BRIVIA owner account';

    public function handle(AuditLogger $audit): int
    {
        if (User::where('role', Role::Owner->value)->exists()) {
            $this->error('An owner account already exists. Invite additional staff from the admin panel.');

            return self::FAILURE;
        }

        if (! $this->input->isInteractive()) {
            $this->error('This command must be run interactively.');

            return self::FAILURE;
        }

        $name = text('Full name', required: true, validate: fn ($v) => $this->check(['name' => $v], ['name' => ['required', 'string', 'min:2', 'max:120']]));
        $email = Str::lower(text('Email address', required: true, validate: fn ($v) => $this->check(['email' => Str::lower($v)], ['email' => ['required', 'email', 'max:254', 'unique:users,email']])));
        $password = password('Password (min. 12 characters, mixed case, number, symbol)', required: true, validate: fn ($v) => $this->check(['password' => $v], ['password' => [Password::defaults()]]));
        $confirmation = password('Confirm password', required: true);

        if (! hash_equals($password, $confirmation)) {
            $this->error('Passwords do not match. Nothing was created.');

            return self::FAILURE;
        }

        $user = new User(['name' => trim($name), 'email' => $email, 'password' => $password]);
        $user->forceFill(['role' => Role::Owner, 'is_active' => true, 'email_verified_at' => now()])->save();
        $audit->record('staff.owner_bootstrapped', 'user', $user->id, ['role' => Role::Owner], $user->id);

        $this->info("Owner account created for {$email}. Sign in at /admin/login.");

        return self::SUCCESS;
    }

    private function check(array $data, array $rules): ?string
    {
        $validator = Validator::make($data, $rules);

        return $validator->fails() ? $validator->errors()->first() : null;
    }
}
