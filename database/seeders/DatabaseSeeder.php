<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Category;
use App\Models\Issue;
use App\Models\Notification;
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

        $users = User::all();

        foreach ($users as $user) {
            $this->seedUserData($user);
        }
    }

    private function seedUserData(User $user): void
    {
        // 1. Seed Categories
        $categoriesData = [
            'UI/UX Design',
            'Frontend Dev',
            'Backend API',
            'Database & Migration',
            'Code Review',
            'Meeting & Sync',
            'Bug Fixing',
            'Research & Docs',
        ];

        $categories = [];
        foreach ($categoriesData as $catName) {
            $categories[] = Category::firstOrCreate([
                'name' => $catName,
                'user_id' => $user->id,
            ]);
        }

        // 2. Seed Projects
        $projectsData = [
            'Activity Tracker V2',
            'E-Commerce Mobile App',
            'Customer Dashboard',
            'Internal API Gateway',
            'Brand Identity Redesign',
        ];

        $projects = [];
        foreach ($projectsData as $projName) {
            $projects[] = Project::firstOrCreate([
                'name' => $projName,
                'user_id' => $user->id,
            ]);
        }

        // 3. Seed Activities (Past 30 days)
        $sampleDetails = [
            'Designing responsive navigation component',
            'Refactoring Livewire component state management',
            'Fixing database indexing on core tables',
            'Implementing OAuth 2.0 Google login integration',
            'Sprint planning and task breakdown with engineering team',
            'Writing unit & integration tests for activity export',
            'Optimizing PHPStan static analysis level to 7',
            'Reviewing pull requests for member management feature',
            'Benchmarking SQL query performance under high load',
            'Setting up automated CI/CD pipeline on GitHub Actions',
            'Troubleshooting WebSocket real-time notification sync',
            'Drafting user guide documentation for activity tracker',
        ];

        // Seed 45 historical completed activities over the last 30 days
        for ($i = 0; $i < 45; $i++) {
            $daysAgo = rand(0, 30);
            $startHour = rand(8, 17);
            $durationMinutes = rand(25, 240);

            $startTime = Carbon::now()->subDays($daysAgo)->setHour($startHour)->setMinute(rand(0, 59))->setSecond(0);
            $endTime = (clone $startTime)->addMinutes($durationMinutes);

            Activity::create([
                'user_id' => $user->id,
                'project_id' => $projects[array_rand($projects)]->id,
                'category_id' => $categories[array_rand($categories)]->id,
                'detail' => $sampleDetails[array_rand($sampleDetails)],
                'start_time' => $startTime,
                'end_time' => $endTime,
                'is_parallel' => (rand(1, 5) === 1), // 20% parallel tasks
            ]);
        }

        // 4. Seed an Active Session (Currently Running Activity)
        $activeCount = Activity::where('user_id', $user->id)->whereNull('end_time')->count();
        if ($activeCount === 0) {
            Activity::create([
                'user_id' => $user->id,
                'project_id' => $projects[0]->id,
                'category_id' => $categories[1]->id,
                'detail' => 'Refactoring Livewire real-time tracking session',
                'start_time' => Carbon::now()->subMinutes(rand(12, 45)),
                'end_time' => null,
                'is_parallel' => false,
            ]);
        }

        // 5. Seed Issues (Tickets)
        $issuesData = [
            [
                'title' => 'Kemudahan ekspor laporan aktivitas bulanan ke Excel',
                'description' => 'Membutuhkan opsi filter rentang tanggal kustom sebelum mengunduh berkas .xlsx.',
                'status' => 'resolved',
            ],
            [
                'title' => 'Tampilan grafik durasi aktivitas pada tema gelap',
                'description' => 'Warna legend grafik terlihat sedikit kurang jelas pada kontras latar belakang gelap.',
                'status' => 'in_progress',
            ],
            [
                'title' => 'Integrasi notifikasi email otomatis harian',
                'description' => 'Memastikan email rangkuman dikirim setiap hari jam 18:00 WIB.',
                'status' => 'open',
            ],
            [
                'title' => 'Sinkronisasi status timer ketika berpindah perangkat',
                'description' => 'Timer yang sedang berjalan di desktop perlu otomatis terupdate di browser seluler.',
                'status' => 'open',
            ],
        ];

        foreach ($issuesData as $issue) {
            Issue::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'title' => $issue['title'],
                ],
                [
                    'description' => $issue['description'],
                    'status' => $issue['status'],
                ]
            );
        }

        // 6. Seed Notifications
        $notificationsData = [
            [
                'title' => '👋 Selamat Datang di Activity Tracker!',
                'body' => 'Sistem pemantauan aktivitas Anda sudah siap digunakan. Mulai catat tugas harian Anda!',
                'type' => 'success',
                'read_at' => Carbon::now()->subDays(2),
            ],
            [
                'title' => '📊 Laporan Mingguan Siap',
                'body' => 'Anda telah menyelesaikan 24 jam aktivitas kerja minggu ini. Bagus sekali!',
                'type' => 'info',
                'read_at' => null, // Unread
            ],
            [
                'title' => '⚡ Pengingat Aktivitas Berjalan',
                'body' => 'Aktivitas "Refactoring Livewire real-time tracking session" sedang berjalan lebih dari 30 menit.',
                'type' => 'warning',
                'read_at' => null, // Unread
            ],
            [
                'title' => '🚀 Pembaruan Sistem V2.4 Released',
                'body' => 'Fitur indeks database baru dan peningkatan kecepatan analisa telah diterapkan.',
                'type' => 'success',
                'read_at' => Carbon::now()->subHours(5),
            ],
        ];

        foreach ($notificationsData as $notif) {
            Notification::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'title' => $notif['title'],
                ],
                [
                    'body' => $notif['body'],
                    'type' => $notif['type'],
                    'read_at' => $notif['read_at'],
                ]
            );
        }
    }
}
