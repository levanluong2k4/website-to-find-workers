<?php

namespace App\Observers;

use App\Models\User;
use App\Services\Chat\AiKnowledgeSyncService;
use Illuminate\Support\Facades\Schema;

class UserObserver
{
    public function saved(User $user): void
    {
        if (!Schema::hasTable('ho_so_tho')) {
            return;
        }

        if ($user->hoSoTho()->exists()) {
            app(AiKnowledgeSyncService::class)->syncSourceRecord('worker_profile', $user->id);
        }
    }

    public function deleted(User $user): void
    {
        app(AiKnowledgeSyncService::class)->deleteSourceRecord('worker_profile', $user->id);
    }
}
