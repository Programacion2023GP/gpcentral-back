<?php

// database/migrations/2025_01_01_000020_create_vw_users_view.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::statement("
            CREATE VIEW vw_users AS
            SELECT
                u.id AS id,
                u.username,
                u.email,
                u.active,
                e.id AS employee_id,
                e.employee_code,
                e.hire_date,
                e.active AS employee_active,
                ed.name,
                ed.plast_name,
                ed.mlast_name,
                CONCAT(ed.name,' ',IFNULL(ed.plast_name,''), ' ', IFNULL(ed.mlast_name,'')) as full_name, 
                CONCAT(IFNULL(ed.plast_name,''), ' ', IFNULL(ed.mlast_name,''), ' ', ed.name) as full_name_reverse,
                ed.rfc,
                ed.curp,
                ed.phone,
                ed.gender,
                ed.avatar,
                ed.signature_image,
                ea.id AS assignment_id,
                ea.position_uuid,
                p.name AS position_name,
                d.uuid AS department_uuid,
                d.name AS department_name,
                d.logo AS department_logo,
                d.seal_image,
                o.id AS organization_id,
                o.name AS organization_name,
                a.id AS administration_id,
                a.name AS administration_name,
                a.president_name,
                a.logo AS administration_logo
            FROM users u
            LEFT JOIN employees e ON u.employee_id = e.id
            LEFT JOIN employee_details ed ON e.id = ed.employee_id AND ed.end_date IS NULL
            -- LEFT JOIN employee_assignments ea ON e.id = ea.employee_id AND ea.end_date IS NULL
            LEFT JOIN (
				SELECT ea1.*
				FROM employee_assignments ea1
				INNER JOIN (
					SELECT employee_id, MAX(created_at) AS max_created_at
					FROM employee_assignments
					GROUP BY employee_id
				) ea2 ON ea1.employee_id = ea2.employee_id AND ea1.created_at = ea2.max_created_at
			) ea ON e.id = ea.employee_id
            LEFT JOIN positions p ON ea.position_uuid = p.uuid AND p.end_date IS NULL
            LEFT JOIN departments d ON ea.department_uuid = d.uuid AND d.end_date IS NULL
            LEFT JOIN organizations o ON d.organization_id = o.id
            LEFT JOIN administrations a ON a.end_date IS NULL
            -- INNER JOIN roles r ON r.id = u.role_id
        ");
    }

    public function down()
    {
        DB::statement("DROP VIEW IF EXISTS vw_users");
    }
};
