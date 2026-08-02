<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\MorningGreetingGenerator;
use Illuminate\Console\Command;

class SendMorningGreetings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-morning-greetings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send dynamic morning greeting notifications to all users';

    /**
     * Execute the console command.
     */
    public function handle(MorningGreetingGenerator $generator): int
    {
        $users = User::all();
        $count = 0;

        foreach ($users as $user) {
            $greeting = $generator->generateForUser($user);

            $user->notifications()->create([
                'title' => $greeting['title'],
                'body' => $greeting['body'],
                'type' => $greeting['type'],
            ]);

            $count++;
        }

        $this->info("Morning greetings sent successfully to {$count} users.");

        return Command::SUCCESS;
    }
}
