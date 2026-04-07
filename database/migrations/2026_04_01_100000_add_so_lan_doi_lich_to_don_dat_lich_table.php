<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('don_dat_lich', 'so_lan_doi_lich')) {
            Schema::table('don_dat_lich', function (Blueprint $table) {
                $table->unsignedTinyInteger('so_lan_doi_lich')
                    ->default(0)
                    ->after('khung_gio_hen');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('don_dat_lich', 'so_lan_doi_lich')) {
            Schema::table('don_dat_lich', function (Blueprint $table) {
                $table->dropColumn('so_lan_doi_lich');
            });
        }
    }
};
