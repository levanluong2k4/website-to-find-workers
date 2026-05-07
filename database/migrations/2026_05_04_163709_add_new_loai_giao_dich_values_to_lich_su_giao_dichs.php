<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * MySQL không support ALTER COLUMN cho ENUM trực tiếp qua Blueprint,
     * dùng raw SQL để thay đổi định nghĩa enum.
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE lich_su_giao_dichs
            MODIFY COLUMN loai_giao_dich ENUM(
                'nap_tien',
                'rut_tien',
                'tru_tien_linh_kien',
                'tru_thue_nha_nuoc',
                'tru_phi_nen_tang',
                'nhan_doanh_thu_cong',
                'hoan_thanh_don',
                'nhan_phi_di_lai'
            ) NOT NULL
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE lich_su_giao_dichs
            MODIFY COLUMN loai_giao_dich ENUM(
                'nap_tien',
                'rut_tien',
                'tru_tien_linh_kien',
                'tru_thue_nha_nuoc',
                'tru_phi_nen_tang',
                'nhan_doanh_thu_cong'
            ) NOT NULL
        ");
    }
};
