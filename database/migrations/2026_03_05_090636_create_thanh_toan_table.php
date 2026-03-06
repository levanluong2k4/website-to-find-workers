<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Alter DonDatLich Trang Thai ENUM (MySQL Only) to add 'cho_thanh_toan' without dropping original values
        DB::statement("ALTER TABLE don_dat_lich MODIFY COLUMN trang_thai ENUM('cho_xac_nhan', 'da_xac_nhan', 'dang_lam', 'cho_hoan_thanh', 'cho_thanh_toan', 'da_xong', 'da_huy') NOT NULL DEFAULT 'cho_xac_nhan'");

        Schema::create('thanh_toan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('don_dat_lich_id')->constrained('don_dat_lich')->onDelete('cascade');
            $table->decimal('so_tien', 12, 2);
            $table->enum('phuong_thuc', ['cash', 'vnpay', 'momo', 'zalopay'])->default('cash');
            $table->string('ma_giao_dich')->nullable()->comment('Mã giao dịch từ VNPay/Momo');
            $table->enum('trang_thai', ['pending', 'success', 'failed'])->default('pending');
            $table->json('thong_tin_extra')->nullable()->comment('Lưu raw response của Gateway');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('thanh_toan');

        // Revert back
        DB::statement("ALTER TABLE don_dat_lich MODIFY COLUMN trang_thai ENUM('cho_xac_nhan', 'da_xac_nhan', 'dang_lam', 'cho_hoan_thanh', 'da_xong', 'da_huy') NOT NULL DEFAULT 'cho_xac_nhan'");
    }
};
