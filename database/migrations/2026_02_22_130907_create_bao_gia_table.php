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
        Schema::create('bao_gia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bai_dang_id')->constrained('bai_dang')->onDelete('cascade');
            $table->foreignId('tho_id')->constrained('users')->onDelete('cascade');
            $table->decimal('muc_gia', 15, 2);
            $table->text('ghi_chu')->nullable();
            $table->enum('trang_thai', ['cho_duyet', 'da_chon', 'tu_choi'])->default('cho_duyet');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bao_gia');
    }
};
