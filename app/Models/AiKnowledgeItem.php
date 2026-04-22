<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiKnowledgeItem extends Model
{
    protected $fillable = [
        'source_type',
        'source_id',
        'source_key',
        'primary_service_id',
        'service_name',
        'title',
        'content',
        'normalized_content',
        'symptom_text',
        'cause_text',
        'solution_text',
        'price_context',
        'rating_avg',
        'quality_score',
        'metadata',
        'is_active',
        'published_at',
        'qdrant_document_hash',
        'qdrant_synced_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
        'published_at' => 'datetime',
        'qdrant_synced_at' => 'datetime',
        'rating_avg' => 'decimal:2',
        'quality_score' => 'decimal:4',
    ];

    public function primaryService()
    {
        return $this->belongsTo(DanhMucDichVu::class, 'primary_service_id');
    }
}
