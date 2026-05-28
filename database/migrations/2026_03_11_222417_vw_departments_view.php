<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::statement("
            CREATE VIEW vw_departments AS
            SELECT
                d.id,
                d.uuid,
                d.code,
                d.name,
                d.organization_id,
                o.name AS organization_name,
                o.code AS organization_code,
                d.seal_image,
                d.start_date,
                d.end_date,
                d.active,
                dir.employee_id AS director_employee_id,
                dir.employee_code AS director_employee_code,
                dir.director_name,
                dir.position_name AS director_position,
                dir.director_since
            FROM departments d
            LEFT JOIN organizations o ON d.organization_id = o.id
            LEFT JOIN (
                SELECT
                    ea.department_uuid,
                    ANY_VALUE(ea.employee_id) AS employee_id,
                    ANY_VALUE(e.employee_code) AS employee_code,
                    ANY_VALUE(CONCAT(ed.name, ' ', IFNULL(ed.plast_name, ''), ' ', IFNULL(ed.mlast_name, ''))) AS director_name,
                    ANY_VALUE(p.name) AS position_name,
                    ANY_VALUE(ea.start_date) AS director_since
                FROM employee_assignments ea
                INNER JOIN positions p ON ea.position_uuid = p.uuid AND p.end_date IS NULL AND p.name LIKE '%DIRECTOR%'
                LEFT JOIN employee_details ed ON ea.employee_id = ed.employee_id AND ed.end_date IS NULL
                INNER JOIN employees e ON ed.employee_id = e.id
                WHERE ea.end_date IS NULL
                GROUP BY ea.department_uuid
            ) dir ON d.uuid = dir.department_uuid
        ");
    }

    public function down()
    {
        DB::statement("DROP VIEW IF EXISTS vw_departments");
    }
};
