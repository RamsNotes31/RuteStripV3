<?php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@rutestrip.web.id'],
            [
                'name'     => 'Admin RuteStrip',
                'email'    => 'admin@rutestrip.web.id',
                'password' => Hash::make('admin123'),
                'role'     => 'admin',
            ]
        );

        $this->command->info('Admin user created/updated:');
        $this->command->info('Email: admin@rutestrip.web.id');
        $this->command->info('Password: admin123');
    }
}
