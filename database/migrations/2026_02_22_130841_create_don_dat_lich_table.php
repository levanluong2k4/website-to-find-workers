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
        Schema::create('don_dat_lich', function (Blueprint $table) {
            $table->id();
            $table->foreignId('khach_hang_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('tho_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('dich_vu_id')->constrained('danh_muc_dich_vu')->onDelete('restrict');
            $table->foreignId('bai_dang_id')->nullable()->constrained('bai_dang')->onDelete('set null'); // Nếu từ bài đăng
            
            $table->dateTime('thoi_gian_hen');
            $table->string('dia_chi');
            $table->decimal('vi_do', 10, 7)->nullable();
            $table->decimal('kinh_do', 10, 7)->nullable();
            $table->text('mo_ta_van_de')->nullable();
            
            $table->enum('trang_thai', ['cho_xac_nhan', 'da_xac_nhan', 'dang_lam', 'cho_hoan_thanh', 'da_xong', 'da_huy'])->default('cho_xac_nhan');
            $table->text('ly_do_huy')->nullable();
            $table->decimal('tong_tien', 15, 2)->nullable();
            $table->enum('phuong_thuc_thanh_toan', ['cod'])->default('cod');
            $table->boolean('trang_thai_thanh_toan')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('don_dat_lich');
    }
};
