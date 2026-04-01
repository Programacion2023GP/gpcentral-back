<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // public function up(): void
    // {
    //     DB::statement(`SELECT
    //                 e.employee_code,
    //                 ed.avatar,
    //                 ed.name,
    //                 ed.plast_name,
    //                 p.name AS position_name,
    //                 d.name AS department_name,
    //                 o.name AS organization_name,
    //                 a.name AS administration_name,
    //                 a.logo,
    //                 a.president_name
    //             FROM employees e
    //             JOIN employee_details ed
    //                 ON e.id = ed.employee_id
    //                 AND ed.start_date <= '2022-05-15'
    //                 AND (ed.end_date IS NULL OR ed.end_date > '2022-05-15')
    //             JOIN employee_assignments ea
    //                 ON e.id = ea.employee_id
    //                 AND ea.start_date <= '2022-05-15'
    //                 AND (ea.end_date IS NULL OR ea.end_date > '2022-05-15')
    //             JOIN positions p
    //                 ON p.uuid = ea.position_uuid
    //                 AND p.start_date <= '2022-05-15'
    //                 AND (p.end_date IS NULL OR p.end_date > '2022-05-15')
    //             LEFT JOIN departments d
    //                 ON d.uuid = p.department_uuid
    //                 AND d.start_date <= '2022-05-15'
    //                 AND (d.end_date IS NULL OR d.end_date > '2022-05-15')
    //             LEFT JOIN organizations o
    //                 ON o.id = d.organization_id   -- organization no versionada
    //             LEFT JOIN administrations a
    //                 ON a.start_date <= '2023-06-01'
    //                 AND (a.end_date IS NULL OR a.end_date > '2023-06-01')
    //             WHERE e.employee_code = 'EMP123';`);
    // }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};