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
      $employees = DB::table('employees')->get();

      $usedUsernames = [];
      $insertData = [];
      $now = now();

      foreach ($employees as $employee) {
         // Obtener datos personales más recientes (end_date null)
         $detail = DB::table('employee_details')
            ->where('employee_id', $employee->id)
            ->whereNull('end_date')
            ->first();

         if (!$detail) {
            Log::warning("Empleado {$employee->employee_code} sin datos personales, se omite.");
            continue;
         }

         $usernameBase = $this->generateUsername(
            $detail->name,
            $detail->plast_name,
            $detail->mlast_name
         );

         // Verificar unicidad
         $suffix = '';
         $counter = 1;
         $finalUsername = $usernameBase;
         while (
            in_array($finalUsername, $usedUsernames) ||
            DB::table('users')->where('username', $finalUsername)->exists()
         ) {
            $suffix = (string) $counter;
            $finalUsername = substr($usernameBase, 0, 15 - strlen($suffix)) . $suffix;
            $counter++;
         }
         $usedUsernames[] = $finalUsername;

         // Email basado en username (puedes cambiarlo)
         $email = strtolower($finalUsername) . '@gomezpalacio.gob.mx';

         $insertData[] = [
            'employee_id' => $employee->id,
            'username' => $finalUsername,
            'email' => $email,
            'password' => Hash::make($finalUsername . "*"), // Cambiar después
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
