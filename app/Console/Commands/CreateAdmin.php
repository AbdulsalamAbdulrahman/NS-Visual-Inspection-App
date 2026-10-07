<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\User;
use App\Support\TemporaryPassword;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CreateAdmin extends Command
{
    /**
     * @var string
     */
    protected $signature = 'app:create-admin {email} {name}';

    /**
     * @var string
     */
    protected $description = 'Create an NSD admin and print a temporary password (changed on first sign-in)';

    public function handle(): int
    {
        $email = Str::lower((string) $this->argument('email'));
        $name = trim((string) $this->argument('name'));

        $validator = Validator::make(compact('email', 'name'), [
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $password = TemporaryPassword::generate();

        User::query()->create([
            'name' => $name,
            'email' => $email,
            'role' => Role::Admin,
            'status' => UserStatus::Invited,
            'must_change_password' => true,
            'password' => $password,
        ]);

        $this->info("Admin {$name} <{$email}> created.");
        $this->line("Temporary password: <comment>{$password}</comment>");
        $this->line('They will be asked to choose their own password on first sign-in.');

        return self::SUCCESS;
    }
}
