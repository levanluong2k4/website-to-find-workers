<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('don_dat_lich_dich_vu', function (Blueprint $table) {
            $table->id();
            $table->foreignId('don_dat_lich_id')->constrained('don_dat_lich')->onDelete('cascade');
            $table->foreignId('dich_vu_id')->constrained('danh_muc_dich_vu')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['don_dat_lich_id', 'dich_vu_id'], 'booking_service_unique');
        });

        $existingBookings = DB::table('don_dat_lich')
            ->select('id', 'dich_vu_id', 'created_at', 'updated_at')
            ->whereNotNull('dich_vu_id')
            ->get();

        if ($existingBookings->isEmpty()) {
            return;
        }

        $rows = $existingBookings->map(function ($booking) {
            return [
                'don_dat_lich_id' => $booking->id,
                'dich_vu_id' => $booking->dich_vu_id,
                'created_at' => $booking->created_at,
                'updated_at' => $booking->updated_at,
            ];
        })->all();

        DB::table('don_dat_lich_dich_vu')->insert($rows);
    }

    public function down(): void
    {
        Schema::dropIfExists('don_dat_lich_dich_vu');
    }
};
