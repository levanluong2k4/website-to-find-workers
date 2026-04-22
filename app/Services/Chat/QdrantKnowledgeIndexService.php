<?php

namespace App\Services\Chat;

use App\Models\AiKnowledgeItem;

class QdrantKnowledgeIndexService
{
    public function __construct(
        private readonly GeminiEmbeddingService $embeddingService,
        private readonly QdrantClientService $qdrantClient,
    ) {
    }

    public function ensureCollection(): void
    {
        $collection = $this->collectionName();

        $this->qdrantClient->ensureCollection(
            $collection,
            (int) config('services.qdrant.vector_size', 768),
            (string) config('services.qdrant.distance', 'Cosine'),
        );

        $this->qdrantClient->ensurePayloadIndex($collection, 'is_active', 'bool');
        $this->qdrantClient->ensurePayloadIndex($collection, 'source_type', 'keyword');
        $this->qdrantClient->ensurePayloadIndex($collection, 'primary_service_id', 'integer');
    }

    public function indexItem(AiKnowledgeItem $item, bool $force = false): string
    {
        $document = $this->buildDocument($item);
        $payload = $this->buildPayload($item);
        $hash = hash('sha256', json_encode([
            'document' => $document,
            'payload' => $payload,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        if (!$force && $item->qdrant_document_hash === $hash && $item->qdrant_synced_at !== null) {
            return 'skipped';
        }

        $vector = $this->embeddingService->embedDocument($document);

        $this->qdrantClient->upsertPoints($this->collectionName(), [[
            'id' => (int) $item->id,
            'vector' => $vector,
            'payload' => $payload,
        ]]);

        $item->forceFill([
            'qdrant_document_hash' => $hash,
            'qdrant_synced_at' => now(),
        ])->saveQuietly();

        return 'indexed';
    }

    /**
     * @return array<string, mixed>
     */
    public function buildPayload(AiKnowledgeItem $item): array
    {
        return [
            'knowledge_item_id' => (int) $item->id,
            'source_type' => (string) $item->source_type,
            'source_id' => $item->source_id !== null ? (int) $item->source_id : null,
            'source_key' => (string) $item->source_key,
            'primary_service_id' => $item->primary_service_id !== null ? (int) $item->primary_service_id : null,
            'service_name' => $item->service_name,
            'title' => (string) $item->title,
            'quality_score' => (float) $item->quality_score,
            'rating_avg' => $item->rating_avg !== null ? (float) $item->rating_avg : null,
            'is_active' => (bool) $item->is_active,
            'published_at' => optional($item->published_at)->toIso8601String(),
            'updated_at' => optional($item->updated_at)->toIso8601String(),
            'metadata' => is_array($item->metadata) ? $item->metadata : [],
        ];
    }

    private function collectionName(): string
    {
        return (string) config('services.qdrant.collection', 'ai_knowledge_items_v1');
    }

    private function buildDocument(AiKnowledgeItem $item): string
    {
        return implode("\n\n", array_filter([
            'Title: ' . trim((string) $item->title),
            $item->service_name ? 'Service: ' . trim((string) $item->service_name) : null,
            $item->symptom_text ? 'Symptom: ' . trim((string) $item->symptom_text) : null,
            $item->cause_text ? 'Cause: ' . trim((string) $item->cause_text) : null,
            $item->solution_text ? 'Solution: ' . trim((string) $item->solution_text) : null,
            $item->price_context ? 'Price context: ' . trim((string) $item->price_context) : null,
            trim((string) $item->content),
        ]));
    }
}
