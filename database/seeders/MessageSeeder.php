<?php

// database/seeders/MessageSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Message;
use Illuminate\Support\Str;

class MessageSeeder extends Seeder
{
    public function run(): void
    {
        Message::factory()->count(25)->create();
    }
}
