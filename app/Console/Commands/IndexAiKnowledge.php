<?php

namespace App\Console\Commands;

use App\Models\AiKnowledgeItem;
use App\Services\Chat\QdrantKnowledgeIndexService;
use Illuminate\Console\Command;

class IndexAiKnowledge extends Command
{
    protected $signature = 'app:index-ai-knowledge
        {--source-type= : Limit to a source_type}
        {--id= : Limit to one ai_knowledge_items.id}
        {--limit=0 : Limit number of records for debugging}
        {--force : Re-embed and re-index even when the document hash is unchanged}';

    protected $description = 'Index ai_knowledge_items into Qdrant using Gemini embeddings.';

    public function handle(QdrantKnowledgeIndexService $indexService): int
    {
        $sourceType = trim((string) $this->option('source-type'));
        $itemId = $this->option('id');
        $itemId = $itemId !== null && $itemId !== '' ? (int) $itemId : null;
        $limit = max(0, (int) $this->option('limit'));
        $force = (bool) $this->option('force');

        $query = AiKnowledgeItem::query()->orderBy('id');

        if ($sourceType !== '') {
            $query->where('source_type', $sourceType);
        }

        if ($itemId !== null) {
            $query->whereKey($itemId);
        }

        if ($limit > 0) {
            $query->limit($limit);
        }

        $items = $query->get();

        if ($items->isEmpty()) {
            $this->warn('Khong co ai_knowledge_items nao phu hop de index.');
            return self::SUCCESS;
        }

        $indexService->ensureCollection();

        $stats = [
            'indexed' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        $bar = $this->output->createProgressBar($items->count());
        $bar->start();

        foreach ($items as $item) {
            try {
                $result = $indexService->indexItem($item, $force);
                $stats[$result] = ($stats[$result] ?? 0) + 1;
            } catch (\Throwable $exception) {
                $stats['failed']++;
                $this->newLine();
                $this->error(sprintf(
                    'Index fail cho item #%d (%s): %s',
                    $item->id,
                    $item->source_key,
                    $exception->getMessage()
                ));
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('Index Qdrant hoan tat.');
        $this->line('indexed: ' . $stats['indexed']);
        $this->line('skipped: ' . $stats['skipped']);
        $this->line('failed: ' . $stats['failed']);

        return $stats['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
