<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Category;
use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
        ]);

        $admin = User::where('email', 'admin@klakoan.com')->first();
        if (! $admin) {
            return;
        }

        // Dummy Categories
        $categories = ['Design', 'Development', 'Meeting', 'Research'];
        $catModels = [];
        foreach ($categories as $cat) {
            $catModels[] = Category::firstOrCreate(
                ['name' => $cat, 'user_id' => $admin->id]
            );
        }

        // Dummy Projects
        $projects = ['Website Redesign', 'Mobile App', 'Marketing Campaign'];
        $projModels = [];
        foreach ($projects as $proj) {
            $projModels[] = Project::firstOrCreate(
                ['name' => $proj, 'user_id' => $admin->id]
            );
        }

        // Generate dummy activities for the last 7 days to populate the chart
        if (Activity::where('user_id', $admin->id)->count() === 0) {
            for ($i = 0; $i < 20; $i++) {
                $daysAgo = rand(0, 6);
                $startHour = rand(8, 16);
                $durationHours = rand(1, 3);

                $startTime = Carbon::today()->subDays($daysAgo)->setHour($startHour)->setMinute(rand(0, 59));
                $endTime = (clone $startTime)->addHours($durationHours)->addMinutes(rand(0, 59));

                Activity::create([
                    'user_id' => $admin->id,
                    'project_id' => $projModels[array_rand($projModels)]->id,
                    'category_id' => $catModels[array_rand($catModels)]->id,
                    'detail' => 'Working on dummy task '.($i + 1),
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'is_parallel' => false,
                ]);
            }
        }
    }
}
