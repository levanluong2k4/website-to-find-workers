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
        Schema::create('bai_dang', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Khách hàng
            $table->foreignId('dich_vu_id')->constrained('danh_muc_dich_vu')->onDelete('restrict');
            $table->string('tieu_de');
            $table->text('mo_ta_chi_tiet');
            $table->string('dia_chi');
            $table->decimal('vi_do', 10, 7)->nullable(); // GPS
            $table->decimal('kinh_do', 10, 7)->nullable(); // GPS
            $table->enum('trang_thai', ['dang_mo', 'da_dong', 'da_huy'])->default('dang_mo');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bai_dang');
    }
};
