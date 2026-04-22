<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ClearExpiredLocksCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auth:clear-locks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clears expired locks for user accounts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $expiredUsers = \App\Models\User::where('is_active', false)
            ->whereNotNull('locked_until')
            ->where('locked_until', '<=', now())
            ->get();

        foreach ($expiredUsers as $user) {
            $user->update([
                'is_active' => true,
                'locked_until' => null,
            ]);
            $this->info("Unlocked user ID: {$user->id}");
        }

        $this->info("Cleared {$expiredUsers->count()} expired locks.");
    }
}
