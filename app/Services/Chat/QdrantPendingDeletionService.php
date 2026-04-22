<?php

namespace App\Services\Chat;

use App\Models\QdrantPendingDeletion;
use Illuminate\Support\Facades\Schema;
use Throwable;

class QdrantPendingDeletionService
{
    public function __construct(
        private readonly QdrantClientService $qdrantClient,
    ) {
    }

    public function queueDeletion(string $collection, int|string $pointId, ?string $reason = null): void
    {
        if (!$this->tableExists() || trim($collection) === '') {
            return;
        }

        QdrantPendingDeletion::query()->updateOrCreate(
            [
                'collection' => $collection,
                'point_id' => (string) $pointId,
            ],
            [
                'reason' => $reason,
                'attempt_count' => 0,
                'available_at' => now(),
                'last_error' => null,
            ]
        );
    }

    /**
     * @return array{processed: int, deleted: int, failed: int}
     */
    public function processPending(int $limit = 100): array
    {
        if (!$this->tableExists()) {
            return ['processed' => 0, 'deleted' => 0, 'failed' => 0];
        }

        $rows = QdrantPendingDeletion::query()
            ->where(function ($query): void {
                $query->whereNull('available_at')->orWhere('available_at', '<=', now());
            })
            ->orderBy('id')
            ->limit(max(1, $limit))
            ->get();

        $stats = ['processed' => 0, 'deleted' => 0, 'failed' => 0];

        foreach ($rows as $row) {
            $stats['processed']++;

            try {
                $this->qdrantClient->deletePoints($row->collection, [$this->castPointId($row->point_id)]);
                $row->delete();
                $stats['deleted']++;
            } catch (Throwable $exception) {
                $row->forceFill([
                    'attempt_count' => $row->attempt_count + 1,
                    'last_error' => mb_substr($exception->getMessage(), 0, 65535, 'UTF-8'),
                    'available_at' => now()->addMinutes(min(max(1, $row->attempt_count + 1), 30)),
                ])->save();

                $stats['failed']++;
            }
        }

        return $stats;
    }

    private function castPointId(string $pointId): int|string
    {
        return ctype_digit($pointId) ? (int) $pointId : $pointId;
    }

    private function tableExists(): bool
    {
        return Schema::hasTable((new QdrantPendingDeletion())->getTable());
    }
}
