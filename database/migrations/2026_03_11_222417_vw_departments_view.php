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
                dir.assignment_id AS assignment_id,
                dir.employee_id AS director_employee_id,
                dir.employee_code AS director_employee_code,
                dir.employee_avatar AS director_avatar,
                dir.employee_signature_image AS director_signature_image,
                dir.director_name,
                dir.position_uuid AS position_uuid,
                dir.position_name AS position_name,
                dir.assignment_start AS assignment_start,
                dir.assignment_end AS assignment_end,
                dir.assignment_active AS assignment_active
            FROM departments d
            LEFT JOIN organizations o ON d.organization_id = o.id
            LEFT JOIN (
                SELECT
                    ANY_VALUE(ea.id) AS assignment_id,
                    ea.department_uuid,
                    ANY_VALUE(ea.employee_id) AS employee_id,
                    ANY_VALUE(e.employee_code) AS employee_code,
                    ANY_VALUE(ed.avatar) AS employee_avatar,
                    ANY_VALUE(ed.signature_image) AS employee_signature_image,
                    ANY_VALUE(CONCAT(ed.name, ' ', IFNULL(ed.plast_name, ''), ' ', IFNULL(ed.mlast_name, ''))) AS director_name,
                    ANY_VALUE(p.uuid) AS position_uuid,
                    ANY_VALUE(p.name) AS position_name,
                    ANY_VALUE(ea.start_date) AS assignment_start,
                    ANY_VALUE(ea.end_date) AS assignment_end,
                    ANY_VALUE(ea.active) AS assignment_active
                FROM employee_assignments ea
                INNER JOIN positions p ON ea.position_uuid = p.uuid AND p.end_date IS NULL AND p.name LIKE '%DIRECTOR%' AND p.name NOT LIKE '%SUB%DIRECTOR%'
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
