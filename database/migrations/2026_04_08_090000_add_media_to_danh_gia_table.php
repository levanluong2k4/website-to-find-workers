<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('danh_gia', function (Blueprint $table) {
            if (!Schema::hasColumn('danh_gia', 'hinh_anh_danh_gia')) {
                $table->json('hinh_anh_danh_gia')->nullable()->after('nhan_xet');
            }

            if (!Schema::hasColumn('danh_gia', 'video_danh_gia')) {
                $table->string('video_danh_gia')->nullable()->after('hinh_anh_danh_gia');
            }
        });
    }

    public function down(): void
    {
        Schema::table('danh_gia', function (Blueprint $table) {
            $dropColumns = [];

            if (Schema::hasColumn('danh_gia', 'video_danh_gia')) {
                $dropColumns[] = 'video_danh_gia';
            }

            if (Schema::hasColumn('danh_gia', 'hinh_anh_danh_gia')) {
                $dropColumns[] = 'hinh_anh_danh_gia';
            }

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
