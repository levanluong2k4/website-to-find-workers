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
        Schema::create('ho_so_tho', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('cccd')->unique();
            $table->text('kinh_nghiem')->nullable();
            $table->string('chung_chi')->nullable(); // Hình ảnh/pdf chứng chỉ
            $table->string('bang_gia_tham_khao')->nullable(); // Hình ảnh/pdf bảng giá
            $table->decimal('vi_do', 10, 7)->nullable(); // Latitude (GPS)
            $table->decimal('kinh_do', 10, 7)->nullable(); // Longitude (GPS)
            $table->integer('ban_kinh_phuc_vu')->default(10); // Bán kính (km)
            $table->enum('trang_thai_duyet', ['cho_duyet', 'da_duyet', 'tu_choi'])->default('cho_duyet');
            $table->text('ghi_chu_admin')->nullable(); // Lý do từ chối hoặc ghi chú riêng
            $table->boolean('dang_hoat_dong')->default(true); // Trạng thái sẵn sàng nhận việc
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ho_so_tho');
    }
};
