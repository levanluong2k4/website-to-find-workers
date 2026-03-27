<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicateWorkerProfiles = DB::table('ho_so_tho')
            ->select('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('user_id');

        if ($duplicateWorkerProfiles->isNotEmpty()) {
            throw new \RuntimeException(
                'Khong the them unique cho ho_so_tho.user_id vi dang ton tai user co nhieu ho so: '
                . $duplicateWorkerProfiles->implode(', ')
            );
        }

        Schema::table('ho_so_tho', function (Blueprint $table) {
            $table->unique('user_id', 'ho_so_tho_user_id_unique');
        });

        if (!Schema::hasTable('don_dat_lich_dich_vu')) {
            throw new \RuntimeException('Bang don_dat_lich_dich_vu khong ton tai.');
        }

        if (!Schema::hasColumn('don_dat_lich', 'dich_vu_id')) {
            return;
        }

        $rows = DB::table('don_dat_lich')
            ->leftJoin('don_dat_lich_dich_vu as pivot', function ($join) {
                $join->on('pivot.don_dat_lich_id', '=', 'don_dat_lich.id')
                    ->on('pivot.dich_vu_id', '=', 'don_dat_lich.dich_vu_id');
            })
            ->whereNotNull('don_dat_lich.dich_vu_id')
            ->whereNull('pivot.id')
            ->select(
                'don_dat_lich.id as don_dat_lich_id',
                'don_dat_lich.dich_vu_id',
                'don_dat_lich.created_at',
                'don_dat_lich.updated_at'
            )
            ->get()
            ->map(function ($row) {
                return [
                    'don_dat_lich_id' => $row->don_dat_lich_id,
                    'dich_vu_id' => $row->dich_vu_id,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ];
            })
            ->all();

        if (!empty($rows)) {
            DB::table('don_dat_lich_dich_vu')->insertOrIgnore($rows);
        }

        Schema::table('don_dat_lich', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dich_vu_id');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('don_dat_lich', 'dich_vu_id')) {
            Schema::table('don_dat_lich', function (Blueprint $table) {
                $table->foreignId('dich_vu_id')->nullable()->after('tho_id');
            });

            $firstSelectedServices = DB::table('don_dat_lich_dich_vu')
                ->select('don_dat_lich_id', DB::raw('MIN(dich_vu_id) as dich_vu_id'))
                ->groupBy('don_dat_lich_id')
                ->get();

            foreach ($firstSelectedServices as $row) {
                DB::table('don_dat_lich')
                    ->where('id', $row->don_dat_lich_id)
                    ->update(['dich_vu_id' => $row->dich_vu_id]);
            }

            Schema::table('don_dat_lich', function (Blueprint $table) {
                $table->foreign('dich_vu_id', 'don_dat_lich_dich_vu_id_foreign')
                    ->references('id')
                    ->on('danh_muc_dich_vu')
                    ->onDelete('restrict');
            });
        }

        Schema::table('ho_so_tho', function (Blueprint $table) {
            $table->dropUnique('ho_so_tho_user_id_unique');
        });
    }
};
