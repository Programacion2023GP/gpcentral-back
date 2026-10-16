<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
   /**
    * Limpia acentos y caracteres especiales.
    */
   private function removeAccents($string)
   {
      $accents = [
         'á' => 'a',
         'é' => 'e',
         'í' => 'i',
         'ó' => 'o',
         'ú' => 'u',
         'Á' => 'A',
         'É' => 'E',
         'Í' => 'I',
         'Ó' => 'O',
         'Ú' => 'U',
         'ü' => 'u',
         'Ü' => 'U',
         'ñ' => 'n',
         'Ñ' => 'N'
      ];
      return strtr($string, $accents);
   }

   /**
    * Genera username según reglas.
    */
   private function generateUsername($firstNameFull, $lastNameP, $lastNameM)
   {
      // Primer nombre (primer segmento)
      $firstPart = explode(' ', trim($firstNameFull))[0];
      $firstNameCapitalized = ucfirst(strtolower($firstPart));
      $firstNameClean = $this->removeAccents($firstNameCapitalized);
      // $firstLetter = strtoupper(substr($firstNameClean, 0, 1));

      $paternalClean = $this->removeAccents($lastNameP);
      if (!empty($lastNameM)) {
         $maternalClean = $this->removeAccents($lastNameM);
         $firstMaternal = strtoupper(substr($maternalClean, 0, 1));
         $usernameBase = $firstNameClean . strtoupper(substr($paternalClean, 0, 1)) . $firstMaternal;
      } else {
         // dos primeras letras del apellido paterno
         $twoPaternal = strtoupper(substr($paternalClean, 0, 2));
         $usernameBase = $firstNameClean . $twoPaternal;
      }
      return $usernameBase;
   }

   public function run()
   {
      $defaultRoleId = DB::table('roles')->where('slug', 'administrador-rh')->value('id');
      $employees = DB::table('employees as e')
         ->join('employee_details as d', function ($join) {
            $join->on('d.employee_id', '=', 'e.id')->whereNull('d.end_date');
         })
         ->select('e.id', 'e.employee_code', 'd.name', 'd.plast_name', 'd.mlast_name')
         ->get();

      // Índice en memoria: evita una consulta SQL por cada intento de username.
      $usedUsernames = DB::table('users')->pluck('username')
         ->filter()
         ->mapWithKeys(fn($username) => [strtolower($username) => true])
         ->all();
      $insertData = [];
      $now = now();
      // Las cuentas importadas nacen sin contraseña conocida. Deben activarse
      // mediante recuperación o asignación explícita por un administrador.
      $inactivePasswordHash = Hash::make(Str::random(64));

      foreach ($employees as $employee) {
         $usernameBase = $this->generateUsername(
            $employee->name,
            $employee->plast_name,
            $employee->mlast_name
         );

         // Verificar unicidad
         $suffix = '';
         $counter = 1;
         $finalUsername = $usernameBase;
         while (
            isset($usedUsernames[strtolower($finalUsername)])
         ) {
            $suffix = (string) $counter;
            $finalUsername = substr($usernameBase, 0, 15 - strlen($suffix)) . $suffix;
            $counter++;
         }
         $usedUsernames[strtolower($finalUsername)] = true;

         // Email basado en username (puedes cambiarlo)
         $email = strtolower($finalUsername) . '@gomezpalacio.gob.mx';

         $insertData[] = [
            'employee_id' => $employee->id,
            'role_id' => $defaultRoleId,
            'username' => $finalUsername,
            'email' => $email,
            'password' => $inactivePasswordHash,
            'active' => true,
            'created_at' => $now,
            'updated_at' => $now,
         ];
      }

      // Insertar en lotes
      foreach (array_chunk($insertData, 100) as $chunk) {
         DB::table('users')->insert($chunk);
      }

      Log::info('Usuarios creados: ' . count($insertData));
   }
}
