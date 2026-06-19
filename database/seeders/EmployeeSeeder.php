<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EmployeeSeeder extends Seeder
{
   public function run()
   {
      $csvFile = database_path('seeders/data/Nominas.csv');
      if (!file_exists($csvFile)) {
         Log::error("Archivo no encontrado: $csvFile");
         return;
      }

      // Mapeo de nombres de puesto a position_uuid (sin importar departamento)
      $positions = DB::table('positions')
         ->select('uuid as position_uuid', 'name as position_name')
         ->get();
      $positionMap = [];
      foreach ($positions as $p) {
         $positionMap[Str::upper($p->position_name)] = $p->position_uuid;
      }

      // Mapeo de nombres de departamento a department_uuid
      $departments = DB::table('departments')
         ->select('uuid as department_uuid', 'name as dept_name')
         ->get();
      $departmentMap = [];
      foreach ($departments as $d) {
         $departmentMap[Str::upper($d->dept_name)] = $d->department_uuid;
      }

      $handle = fopen($csvFile, 'r');
      $header = fgetcsv($handle);
      $now = now();

      $now = now();

      // Log::info($positionMap);
      // Log::info($departmentMap);
      while (($row = fgetcsv($handle)) !== false) {
         $data = array_combine($header, $row);

         $employeeCode = trim($data['Numero_Nomina']);
         $name = trim($data['Nombre']);        // ej: "GERARDO ALBERTO"
         $pLastName = trim($data['A. Paterno']);
         $mLastName = trim($data['A. Materno']) ?: null;
         $rfc = trim($data['RFC']) ?: null;
         $curp = trim($data['CURP']) ?: null;
         $hireDateStr = trim($data['Fecha Alta']);
         $gender = trim($data['Sexo']);
         $deptName = trim($data['Departamento']);
         $positionName = trim($data['Puesto']);

         if (empty($employeeCode) || empty($deptName) || empty($positionName)) {
            continue;
         }

         // Parse fecha
         try {
            $hireDate = Carbon::createFromFormat('F-d-Y', $hireDateStr)->format('Y-m-d');
         } catch (\Exception $e) {
            Log::warning("Fecha inválida para empleado $employeeCode: $hireDateStr");
            continue;
         }

         // Log::info(Str::upper($positionName));
         // Log::info(Str::upper($deptName));
         $positionUuid = $positionMap[Str::upper($positionName)] ?? null;
         $departmentUuid = $departmentMap[Str::upper($deptName)] ?? null;

         if (!$positionUuid) {
            Log::warning("Puesto no encontrado: $positionName para empleado $employeeCode");
            continue;
         }
         if (!$departmentUuid) {
            Log::warning("Departamento no encontrado: $deptName para empleado $employeeCode");
            continue;
         }

         // Crear employee
         $employeeId = DB::table('employees')->insertGetId([
            'employee_code' => $employeeCode,
            'hire_date' => $hireDate,
            'active' => true,
            'created_at' => $now,
            'updated_at' => $now,
         ]);

         // Detalles
         DB::table('employee_details')->insert([
            'employee_id' => $employeeId,
            'name' => $name,
            'plast_name' => $pLastName,
            'mlast_name' => $mLastName,
            'rfc' => $rfc,
            'curp' => $curp,
            'gender' => $gender,
            'cellphone' => null,
            'avatar' => null,
            'signature_image' => null,
            'start_date' => $hireDate,
            'end_date' => null,
            'active' => true,
            'created_at' => $now,
            'updated_at' => $now,
         ]);

         // Asignación
         DB::table('employee_assignments')->insert([
            'employee_id' => $employeeId,
            'department_uuid' => $departmentUuid,
            'position_uuid' => $positionUuid,
            'start_date' => $hireDate,
            'end_date' => null,
            'active' => true,
            'created_at' => $now,
            'updated_at' => $now,
         ]);
      }

      fclose($handle);
      Log::info('Empleados importados correctamente.');
   }
}