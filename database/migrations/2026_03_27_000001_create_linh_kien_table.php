<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('linh_kien', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dich_vu_id')->constrained('danh_muc_dich_vu')->cascadeOnDelete();
            $table->string('ten_linh_kien');
            $table->string('hinh_anh')->nullable();
            $table->decimal('gia', 15, 2)->nullable();
            $table->timestamps();

            $table->index(['dich_vu_id', 'ten_linh_kien']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('linh_kien');
    }
};
