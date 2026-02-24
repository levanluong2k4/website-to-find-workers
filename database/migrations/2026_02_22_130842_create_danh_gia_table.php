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
        Schema::create('danh_gia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('don_dat_lich_id')->constrained('don_dat_lich')->onDelete('cascade');
            $table->foreignId('nguoi_danh_gia_id')->constrained('users')->onDelete('cascade'); // ID khách hàng
            $table->foreignId('nguoi_bi_danh_gia_id')->constrained('users')->onDelete('cascade'); // ID thợ
            $table->integer('so_sao')->comment('Từ 1 đến 5');
            $table->text('nhan_xet')->nullable();
            $table->integer('chuyen_mon')->nullable()->comment('Từ 1 đến 5');
            $table->integer('thai_do')->nullable()->comment('Từ 1 đến 5');
            $table->integer('dung_gio')->nullable()->comment('Từ 1 đến 5');
            $table->integer('gia_ca')->nullable()->comment('Từ 1 đến 5');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('danh_gia');
    }
};
