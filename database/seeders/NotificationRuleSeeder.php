<?php

namespace Database\Seeders;

use App\Models\NotificationRule;
use Illuminate\Database\Seeder;

class NotificationRuleSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [
            [
                'name'           => 'H-7',
                'days_before'    => 7,
                'send_time'      => '08:00',
                'channel'        => 'whatsapp',
                'recipient_type' => 'all',
                'is_active'      => true,
            ],
            [
                'name'           => 'H-3',
                'days_before'    => 3,
                'send_time'      => '08:00',
                'channel'        => 'whatsapp',
                'recipient_type' => 'all',
                'is_active'      => true,
            ],
            [
                'name'           => 'H-1',
                'days_before'    => 1,
                'send_time'      => '08:00',
                'channel'        => 'whatsapp',
                'recipient_type' => 'all',
                'is_active'      => true,
            ],
            [
                'name'           => 'Hari H',
                'days_before'    => 0,
                'send_time'      => '08:00',
                'channel'        => 'whatsapp',
                'recipient_type' => 'all',
                'is_active'      => true,
            ],
        ];

        foreach ($rules as $rule) {
            NotificationRule::updateOrCreate(
                ['days_before' => $rule['days_before'], 'channel' => $rule['channel']],
                $rule
            );
        }

        $this->command->info('Notification Rules seeded successfully.');
    }
}
