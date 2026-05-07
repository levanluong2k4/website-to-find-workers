<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label')->nullable();         // e.g. "Nhà riêng", "Văn phòng"
            $table->string('tinh');                      // Province name
            $table->string('xa');                        // Ward name
            $table->string('so_nha');                    // Street detail
            $table->string('dia_chi_day_du');            // Full composed address
            $table->boolean('la_mac_dinh')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_addresses');
    }
};
