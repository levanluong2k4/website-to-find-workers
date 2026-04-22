<?php

namespace App\Observers;

use App\Models\CustomerFeedbackCase;
use App\Services\Chat\AiKnowledgeSyncService;

class CustomerFeedbackCaseObserver
{
    public function saved(CustomerFeedbackCase $case): void
    {
        app(AiKnowledgeSyncService::class)->syncSourceRecord('customer_feedback_case', $case->id);
    }

    public function deleted(CustomerFeedbackCase $case): void
    {
        app(AiKnowledgeSyncService::class)->deleteSourceRecord('customer_feedback_case', $case->id);
    }
}
