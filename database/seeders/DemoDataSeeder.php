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
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password');

        // 1. Users / Members
        $usersData = [
            [
                'name' => 'Admin System',
                'email' => 'admin@klakoan.com',
                'is_admin' => true,
            ],
            [
                'name' => 'Hoiril Mochtar',
                'email' => 'hoiril@klakoan.com',
                'is_admin' => false,
            ],
            [
                'name' => 'Budi Santoso',
                'email' => 'budi@klakoan.com',
                'is_admin' => false,
            ],
            [
                'name' => 'Siti Rahma',
                'email' => 'siti@klakoan.com',
                'is_admin' => false,
            ],
            [
                'name' => 'Rudi Hermawan',
                'email' => 'rudi@klakoan.com',
                'is_admin' => false,
            ],
            [
                'name' => 'Dewi Lestari',
                'email' => 'dewi@klakoan.com',
                'is_admin' => false,
            ],
            [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'is_admin' => false,
            ],
        ];

        $users = collect();
        foreach ($usersData as $u) {
            $user = User::updateOrCreate(
                ['email' => $u['email']],
                [
                    'name' => $u['name'],
                    'password' => $password,
                    'is_admin' => $u['is_admin'],
                    'email_verified_at' => now(),
                ]
            );
            $users->push($user);
        }

        // Projects & Categories Template
        $projectTemplates = [
            ['name' => 'E-Commerce Mobile App', 'client_name' => 'Tokopedia Partner'],
            ['name' => 'Internal ERP Refactoring', 'client_name' => 'Internal Operations'],
            ['name' => 'Payment Gateway Integration', 'client_name' => 'Xendit Finance'],
            ['name' => 'Company Landing Page', 'client_name' => 'Marketing Team'],
            ['name' => 'Customer Portal API', 'client_name' => 'Enterprise Client'],
        ];

        $categoryTemplates = [
            'Backend Development',
            'Frontend & UI Design',
            'Database Optimization',
            'Code Review & Testing',
            'DevOps & Deployment',
            'Bug Fixing & Support',
            'Team Sync & Planning',
        ];

        $activityDetails = [
            'Backend Development' => [
                'Refactoring JWT authentication middleware',
                'Building REST API endpoints for user profile management',
                'Implementing OAuth2 login flow with Google & GitHub',
                'Optimizing Laravel Eloquent queries and eager loading',
                'Integrating Webhook handlers for payment processing',
            ],
            'Frontend & UI Design' => [
                'Designing responsive dashboard stats widgets using Alpine.js',
                'Refactoring Alpine.js components for modal dialogs',
                'Implementing Dark Mode toggle and Tailwind color palette',
                'Creating activity tracker sticky input bar UX',
                'Polishing chart transitions and SVG data visualizations',
            ],
            'Database Optimization' => [
                'Adding compound indexes on activities table (user_id, start_time)',
                'Analyzing PostgreSQL execution plans for monthly stats query',
                'Writing database migration for two-factor authentication',
                'Cleaning up orphan cache records and session table',
            ],
            'Code Review & Testing' => [
                'Reviewing Pull Request #42: Passkey registration workflow',
                'Writing Pest / PHPUnit test cases for activity calculations',
                'Executing end-to-end user registration regression tests',
                'Auditing security dependencies and composer package updates',
            ],
            'DevOps & Deployment' => [
                'Setting up GitHub Actions CI/CD workflow for automated tests',
                'Configuring Herd local environment and domain SSL certs',
                'Optimizing Docker Compose setup for production deployment',
                'Monitoring server log errors and memory usage metrics',
            ],
            'Bug Fixing & Support' => [
                'Fixing timer duration calculation bug across timezone shifts',
                'Resolving CSV export encoding issue for multi-language text',
                'Fixing session timeout edge-case on mobile safari browser',
                'Debugging Livewire wire:model race condition on form input',
            ],
            'Team Sync & Planning' => [
                'Daily Standup & Sprint Planning meeting with product manager',
                'Architecture review meeting for activity export service',
                'Weekly retrospective & developer workflow discussion',
            ],
        ];

        // Seed Projects, Categories, Activities per User
        foreach ($users as $user) {
            $userProjects = collect();
            foreach ($projectTemplates as $pt) {
                $proj = Project::create([
                    'user_id' => $user->id,
                    'name' => $pt['name'],
                    'client_name' => $pt['client_name'],
                ]);
                $userProjects->push($proj);
            }

            $userCategories = collect();
            foreach ($categoryTemplates as $catName) {
                $cat = Category::create([
                    'user_id' => $user->id,
                    'name' => $catName,
                ]);
                $userCategories->push($cat);
            }

            // Seed 14 Days of Activities for each user
            $today = Carbon::today();
            for ($dayOffset = 13; $dayOffset >= 0; $dayOffset--) {
                $currentDate = $today->copy()->subDays($dayOffset);
                
                // Skip weekends occasionally for realistic look
                if ($currentDate->isWeekend() && rand(0, 1) === 0) {
                    continue;
                }

                // 2 to 4 activities per day
                $activitiesCount = rand(2, 4);
                $startHour = 9; // Start at 9 AM

                for ($i = 0; $i < $activitiesCount; $i++) {
                    $category = $userCategories->random();
                    $project = $userProjects->random();
                    
                    $possibleDetails = $activityDetails[$category->name] ?? ['General activity tracking and development work'];
                    $detail = $possibleDetails[array_rand($possibleDetails)];

                    $durationMinutes = rand(45, 180); // 45m to 3h
                    $startTime = $currentDate->copy()->setHour($startHour)->setMinute(rand(0, 30))->setSecond(0);
                    $endTime = $startTime->copy()->addMinutes($durationMinutes);

                    // Move next activity start time forward
                    $startHour += floor(($durationMinutes + rand(15, 45)) / 60);
                    if ($startHour >= 18) {
                        $startHour = 18;
                    }

                    Activity::create([
                        'user_id' => $user->id,
                        'project_id' => $project->id,
                        'category_id' => $category->id,
                        'detail' => $detail,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'is_parallel' => rand(0, 5) === 0, // 20% parallel
                    ]);
                }
            }

            // For today: add 1 running activity (end_time = null) for active tracking demonstration
            $runningCategory = $userCategories->firstWhere('name', 'Frontend & UI Design') ?? $userCategories->first();
            $runningProject = $userProjects->first();
            
            Activity::create([
                'user_id' => $user->id,
                'project_id' => $runningProject->id,
                'category_id' => $runningCategory->id,
                'detail' => 'Polishing dashboard charts and live tracking animations',
                'start_time' => Carbon::now()->subMinutes(rand(25, 75)),
                'end_time' => null, // Active timer!
                'is_parallel' => false,
            ]);
        }

        // 5. System & User Issues
        $issuesData = [
            [
                'user_id' => $users->firstWhere('email', 'budi@klakoan.com')->id,
                'title' => 'Slow query performance on monthly activity chart export',
                'description' => 'When selecting 30-day period with >500 records, the chart render takes 2.5 seconds. Adding database index on (user_id, start_time) should optimize this.',
                'status' => 'open',
            ],
            [
                'user_id' => $users->firstWhere('email', 'siti@klakoan.com')->id,
                'title' => 'Dark mode contrast adjustment on sticky tracker bar',
                'description' => 'In dark mode, the input placeholder text has low contrast in certain Linux Chrome versions. Suggesting text-zinc-400 update.',
                'status' => 'in_progress',
            ],
            [
                'user_id' => $users->firstWhere('email', 'rudi@klakoan.com')->id,
                'title' => 'CSV Export missing client_name column header',
                'description' => 'Exported excel file contains project name but missing client_name field in column B.',
                'status' => 'resolved',
            ],
            [
                'user_id' => $users->firstWhere('email', 'dewi@klakoan.com')->id,
                'title' => 'Keyboard shortcut Cmd+Enter triggers double submit',
                'description' => 'Pressing Cmd+Enter fast causes dual activity insertion on slow internet connection. Need debounce modifier.',
                'status' => 'open',
            ],
            [
                'user_id' => $users->firstWhere('email', 'hoiril@klakoan.com')->id,
                'title' => 'Add category color badges on daily activity list',
                'description' => 'Feature request to add custom color badges for each category to improve visual scanning.',
                'status' => 'resolved',
            ],
        ];

        foreach ($issuesData as $issue) {
            Issue::create($issue);
        }

        // 6. Notifications (Unread & Read) for all users
        foreach ($users as $user) {
            Notification::create([
                'user_id' => $user->id,
                'title' => 'Welcome to Developer Activity Tracker 🚀',
                'body' => 'Your workspace account has been configured with demo projects, categories, and tracking statistics.',
                'type' => 'info',
                'read_at' => Carbon::now()->subDays(2),
            ]);

            Notification::create([
                'user_id' => $user->id,
                'title' => 'New Feature: Passkey & 2FA Support 🔐',
                'body' => 'You can now enable WebAuthn Passkeys and Two-Factor Authentication in your Account Settings.',
                'type' => 'success',
                'read_at' => Carbon::now()->subDays(1),
            ]);

            Notification::create([
                'user_id' => $user->id,
                'title' => 'Active Timer Reminder ⏱️',
                'body' => 'You have an active activity "Polishing dashboard charts and live tracking animations" currently running.',
                'type' => 'warning',
                'read_at' => null, // Unread notification!
            ]);

            Notification::create([
                'user_id' => $user->id,
                'title' => 'Weekly Activity Report Ready 📊',
                'body' => 'Your total logged work for this week is 34 hours 15 minutes across 5 projects.',
                'type' => 'info',
                'read_at' => null, // Unread notification!
            ]);
        }
    }
}
