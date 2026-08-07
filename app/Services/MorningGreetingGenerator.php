<?php

namespace App\Services;

use App\Models\Issue;
use App\Models\Task;
use App\Models\User;

class MorningGreetingGenerator
{
    /**
     * Curated motivational quotes and encouraging thoughts in English.
     */
    protected static array $motivationalQuotes = [
        'Focus on the process, the best results will follow naturally.',
        'Small steps today lead to big achievements tomorrow.',
        'Discipline is the bridge between goals and accomplishment.',
        'Focus on what is important first, then what is urgent.',
        'Every day is a new opportunity to be better than yesterday.',
        'Don\'t wait for opportunity to come, create it.',
        'Consistency is the key to success.',
        'Hard work and smart work bring the best results.',
        'Today\'s challenges are opportunities to grow and develop.',
        'Stay calm, focus on solutions, and accomplish one task at a time.',
    ];

    /**
     * Varied greeting titles in English.
     */
    protected static array $titleTemplates = [
        '🌞 Good Morning, :name!',
        '🌅 Good Morning & Have a Great Day, :name!',
        '🚀 Ready to Conquer Today, :name?',
        '✨ A New Day Begins, :name!',
        '☀️ New Dawn, New Energy, :name!',
        '💡 Morning Inspiration for :name',
    ];

    /**
     * Generate dynamic greeting content for a specific user.
     *
     * @return array{title: string, body: string, type: string}
     */
    public function generateForUser(User $user): array
    {
        $firstName = explode(' ', trim($user->name))[0] ?: 'Friend';

        // Select a title template
        $titleTemplate = static::$titleTemplates[array_rand(static::$titleTemplates)];
        $title = str_replace(':name', $firstName, $titleTemplate);

        $bodyParts = [];

        // 1. Contextual summary (tasks & issues) formatted as bullet points
        $activeTasks = Task::where('user_id', $user->id)
            ->whereIn('status', [Task::STATUS_NEW, Task::STATUS_ON_PROGRESS, Task::STATUS_ON_HOLD])
            ->get();

        $onProgressCount = $activeTasks->where('status', Task::STATUS_ON_PROGRESS)->count();
        $newCount = $activeTasks->where('status', Task::STATUS_NEW)->count();
        $onHoldCount = $activeTasks->where('status', Task::STATUS_ON_HOLD)->count();
        $totalActiveCount = $activeTasks->count();

        if ($totalActiveCount > 0) {
            $taskBreakdown = [];
            if ($onProgressCount > 0) {
                $taskBreakdown[] = "• {$onProgressCount} in progress";
            }
            if ($newCount > 0) {
                $taskBreakdown[] = "• {$newCount} new";
            }
            if ($onHoldCount > 0) {
                $taskBreakdown[] = "• {$onHoldCount} on hold";
            }

            $taskLabel = $totalActiveCount === 1 ? 'active task' : 'active tasks';
            $bodyParts[] = "📋 Today's Overview:\nYou have {$totalActiveCount} {$taskLabel}:\n" . implode("\n", $taskBreakdown);
        } else {
            $bodyParts[] = "📋 Today's Overview:\nNo active tasks pending. Great time to plan new goals!";
        }

        // Check open issues if Issue model exists
        if (class_exists(Issue::class)) {
            $openIssuesCount = Issue::where('user_id', $user->id)
                ->whereIn('status', ['open', 'pending'])
                ->count();

            if ($openIssuesCount > 0) {
                $issueLabel = $openIssuesCount === 1 ? 'issue' : 'issues';
                $bodyParts[] = "⚠️ Open Issues:\n• {$openIssuesCount} {$issueLabel} requiring monitoring";
            }
        }

        // 2. Motivational quote
        $quote = static::$motivationalQuotes[array_rand(static::$motivationalQuotes)];
        $bodyParts[] = "💡 Motivation:\n\"{$quote}\"";

        // 3. Energetic closing note
        $bodyParts[] = 'Have a great day and stay productive! 🔥';

        return [
            'title' => $title,
            'body' => implode("\n\n", $bodyParts),
            'type' => 'info',
        ];
    }
}

