<?php

namespace App\Services;

use App\Models\Issue;
use App\Models\Task;
use App\Models\User;

class MorningGreetingGenerator
{
    /**
     * Curated motivational quotes and encouraging thoughts in Indonesian.
     */
    protected static array $motivationalQuotes = [
        'Fokus pada proses, hasil terbaik akan menyusul dengan sendirinya.',
        'Langkah kecil hari ini adalah awal dari pencapaian besar besok.',
        'Disiplin adalah jembatan antara tujuan dan pencapaian.',
        'Selesaikan hal penting dulu, baru hal yang mendesak.',
        'Setiap hari adalah kesempatan baru untuk menjadi lebih baik dari kemarin.',
        'Jangan menunggu kesempatan datang, ciptakanlah kesempatan itu.',
        'Konsistensi adalah kunci utama keberhasilan.',
        'Kerja keras dan kerja cerdas membawa hasil terbaik.',
        'Tantangan hari ini adalah peluang untuk tumbuh dan berkembang.',
        'Tetap tenang, fokus pada solusi, dan selesaikan satu per satu.',
    ];

    /**
     * Varied greeting titles.
     */
    protected static array $titleTemplates = [
        '🌞 Semangat Pagi, :name!',
        '🌅 Selamat Pagi & Have a Great Day, :name!',
        '🚀 Siap Menaklukkan Hari Ini, :name?',
        '✨ Awal Hari Yang Baru, :name!',
        '☀️ Fajar Baru, Semangat Baru, :name!',
        '💡 Inspirasi Pagi untuk :name',
    ];

    /**
     * Generate dynamic greeting content for a specific user.
     *
     * @return array{title: string, body: string, type: string}
     */
    public function generateForUser(User $user): array
    {
        $firstName = explode(' ', trim($user->name))[0] ?: 'Teman';

        // Select a title template
        $titleTemplate = static::$titleTemplates[array_rand(static::$titleTemplates)];
        $title = str_replace(':name', $firstName, $titleTemplate);

        $bodyParts = [];

        // 1. Contextual summary (tasks & issues)
        $activeTasks = Task::where('user_id', $user->id)
            ->whereIn('status', [Task::STATUS_NEW, Task::STATUS_ON_PROGRESS, Task::STATUS_ON_HOLD])
            ->get();

        $onProgressCount = $activeTasks->where('status', Task::STATUS_ON_PROGRESS)->count();
        $newCount = $activeTasks->where('status', Task::STATUS_NEW)->count();
        $onHoldCount = $activeTasks->where('status', Task::STATUS_ON_HOLD)->count();
        $totalActiveCount = $activeTasks->count();

        if ($totalActiveCount > 0) {
            if ($onProgressCount > 0) {
                $details = ["{$onProgressCount} sedang berjalan"];
                if ($newCount > 0) {
                    $details[] = "{$newCount} baru";
                }
                if ($onHoldCount > 0) {
                    $details[] = "{$onHoldCount} on hold";
                }
                $detailsStr = implode(', ', $details);
                $bodyParts[] = "📋 Info Hari Ini: Kamu punya {$totalActiveCount} task aktif ({$detailsStr}).";
            } elseif ($newCount > 0) {
                $details = ["{$newCount} task baru"];
                if ($onHoldCount > 0) {
                    $details[] = "{$onHoldCount} on hold";
                }
                $detailsStr = implode(', ', $details);
                $bodyParts[] = "📋 Info Hari Ini: Kamu punya {$totalActiveCount} task aktif ({$detailsStr}).";
            } else {
                $bodyParts[] = "📋 Info Hari Ini: Kamu punya {$totalActiveCount} task aktif ({$onHoldCount} task on hold).";
            }
        } else {
            $bodyParts[] = '📋 Info Hari Ini: Tidak ada task pending. Waktu yang pas untuk merencanakan target baru!';
        }

        // Check open issues if Issue model exists
        if (class_exists(Issue::class)) {
            $openIssuesCount = Issue::where('user_id', $user->id)
                ->whereIn('status', ['open', 'pending'])
                ->count();

            if ($openIssuesCount > 0) {
                $bodyParts[] = "⚠️ Ada {$openIssuesCount} issue terbuka yang perlu dipantau.";
            }
        }

        // 2. Motivational quote
        $quote = static::$motivationalQuotes[array_rand(static::$motivationalQuotes)];
        $bodyParts[] = "💡 Motivasi: \"{$quote}\"";

        // 3. Energetic closing note
        $bodyParts[] = 'Selamat beraktivitas dan semoga harimu produktif! 🔥';

        return [
            'title' => $title,
            'body' => implode("\n\n", $bodyParts),
            'type' => 'info',
        ];
    }
}
