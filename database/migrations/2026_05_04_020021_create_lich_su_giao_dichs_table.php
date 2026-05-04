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
        Schema::create('lich_su_giao_dichs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ma_vi')->constrained('vi_dien_tus')->onDelete('cascade');
            $table->decimal('so_tien', 15, 2);
            $table->enum('loai_giao_dich', [
                'nap_tien', 
                'rut_tien', 
                'tru_tien_linh_kien', 
                'tru_thue_nha_nuoc', 
                'tru_phi_nen_tang', 
                'nhan_doanh_thu_cong'
            ]);
            $table->unsignedBigInteger('ma_don_hang')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lich_su_giao_dichs');
    }
};
