<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trieu_chung', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dich_vu_id')->constrained('danh_muc_dich_vu')->cascadeOnDelete();
            $table->string('ten_trieu_chung');
            $table->timestamps();

            $table->unique(['dich_vu_id', 'ten_trieu_chung'], 'service_symptom_unique');
            $table->index(['dich_vu_id', 'ten_trieu_chung'], 'service_symptom_name_idx');
        });

        Schema::create('nguyen_nhan', function (Blueprint $table) {
            $table->id();
            $table->string('ten_nguyen_nhan');
            $table->timestamps();

            $table->index('ten_nguyen_nhan', 'cause_name_idx');
        });

        Schema::create('trieu_chung_nguyen_nhan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trieu_chung_id')->constrained('trieu_chung')->cascadeOnDelete();
            $table->foreignId('nguyen_nhan_id')->constrained('nguyen_nhan')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['trieu_chung_id', 'nguyen_nhan_id'], 'symptom_cause_unique');
        });

        Schema::create('huong_xu_ly', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nguyen_nhan_id')->constrained('nguyen_nhan')->cascadeOnDelete();
            $table->string('ten_huong_xu_ly');
            $table->decimal('gia_tham_khao', 15, 2)->nullable();
            $table->text('mo_ta_cong_viec')->nullable();
            $table->timestamps();

            $table->unique(['nguyen_nhan_id', 'ten_huong_xu_ly'], 'cause_resolution_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('huong_xu_ly');
        Schema::dropIfExists('trieu_chung_nguyen_nhan');
        Schema::dropIfExists('nguyen_nhan');
        Schema::dropIfExists('trieu_chung');
    }
};
