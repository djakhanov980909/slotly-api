<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\User;
use App\Models\WorkingHour;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->admin()->create(['name' => 'Admin', 'email' => 'admin@slotly.test']);

        $services = collect([
            ['Стрижка', 30, 5000],
            ['Окрашивание', 120, 20000],
            ['Маникюр', 60, 8000],
        ])->map(fn (array $s) => Service::create([
            'name' => $s[0], 'duration_minutes' => $s[1], 'price' => $s[2],
        ]));

        User::factory(3)->specialist()->create()->each(function (User $specialist) use ($services) {
            $specialist->services()->attach($services->pluck('id'));

            foreach (range(1, 5) as $weekday) {
                foreach ([['09:00', '13:00'], ['14:00', '18:00']] as [$start, $end]) {
                    WorkingHour::create([
                        'specialist_id' => $specialist->id,
                        'weekday' => $weekday,
                        'start_time' => $start,
                        'end_time' => $end,
                    ]);
                }
            }
        });
    }
}
