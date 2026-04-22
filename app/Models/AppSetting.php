<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class AppSetting extends Model
{
    protected $table = 'app_settings';

    protected $fillable = [
        'key',
        'value',
        'updated_by',
    ];

    protected $casts = [
        'value' => 'array',
    ];

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function tableExists(): bool
    {
        static $exists;

        if ($exists !== null) {
            return $exists;
        }

        try {
            $exists = Schema::hasTable((new static())->getTable());
        } catch (\Throwable) {
            $exists = false;
        }

        return $exists;
    }
}
