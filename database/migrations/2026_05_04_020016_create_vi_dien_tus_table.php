<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vi_dien_tus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ma_tho')->constrained('users')->onDelete('cascade');
            $table->decimal('so_du', 15, 2)->default(0);
            $table->enum('trang_thai', ['hoat_dong', 'cho_nap_tien'])->default('hoat_dong');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vi_dien_tus');
    }
};
