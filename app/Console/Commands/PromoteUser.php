<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;

class PromoteUser extends Command
{
    protected $signature = 'user:promote {email : The email address of the user to promote}';

    protected $description = 'Promote a user to admin role by email address';

    public function handle(): int
    {
        $email = $this->argument('email');

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("User with email [{$email}] not found.");

            return self::FAILURE;
        }

        $role = Role::where('name', 'admin')->first();

        if (! $role) {
            $this->error('Admin role does not exist. Please run database seeders first.');

            return self::FAILURE;
        }

        if ($user->hasRole('admin')) {
            $this->warn("User [{$user->name}] already has the admin role.");

            return self::SUCCESS;
        }

        $user->roles()->attach($role);

        $this->info("User [{$user->name}] has been promoted to admin.");

        return self::SUCCESS;
    }
}
