<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $positions = [
            ['name' => 'N ACTUARIO'],
            ['name' => 'NA ADMINISTRADOR A'],
            ['name' => 'NB ADMINISTRADOR B'],
            ['name' => 'NA ADMINISTRATIVO A'],
            ['name' => 'NB ADMINISTRATIVO B'],
            ['name' => 'N AGENTE'],
            ['name' => 'N ALBAÑIL'],
            ['name' => 'N ALCAIDE'],
            ['name' => 'N ALMACENISTA'],
            ['name' => 'N ANALISTA DE PRECIOS'],
            ['name' => 'NA ASESOR JURIDICO A'],
            ['name' => 'NB ASESOR JURIDICO B'],
            ['name' => 'NA ASISTENTE A'],
            ['name' => 'NB ASISTENTE B'],
            ['name' => 'N ASPIRANTE A POLICIA PREVENTIVO'],
            ['name' => 'N AUDITOR INTERNO'],
            ['name' => 'NA AUXILIAR ADMINISTRATIVO A'],
            ['name' => 'NB AUXILIAR ADMINISTRATIVO B'],
            ['name' => 'NC AUXILIAR ADMINISTRATIVO C'],
            ['name' => 'N AUXILIAR DE MANTENIMIENTO'],
            ['name' => 'N AUXILIAR DE VIALIDAD'],
            ['name' => 'N AUXILIAR OPERATIVO'],
            ['name' => 'N AYUDANTE DE BACHEO'],
            ['name' => 'N AYUDANTE GENERAL'],
            ['name' => 'N BASCULISTA'],
            ['name' => 'N BIBLIOTECARIA'],
            ['name' => 'N BRIGADISTA'],
            ['name' => 'N CAJERO'],
            ['name' => 'N CARGADOR'],
            ['name' => 'N CARRERO'],
            ['name' => 'N CELADOR'],
            ['name' => 'N CHOFER'],
            ['name' => 'N COMISIONADO'],
            ['name' => 'N CONTRALOR MUNICIPAL'],
            ['name' => 'N COORDINADOR'],
            ['name' => 'NA COORDINADOR A'],
            ['name' => 'NB COORDINADOR B'],
            ['name' => 'NC COORDINADOR C'],
            ['name' => 'N CRONISTA'],
            ['name' => 'NA DIRECTOR A'],
            ['name' => 'NB DIRECTOR B'],
            ['name' => 'N DISEÑADOR'],
            ['name' => 'N ELECTRICISTA'],
            ['name' => 'N ENCARGADO DE BIBLIOTECA'],
            ['name' => 'N ENFERMERO'],
            ['name' => 'N ENTRENADOR'],
            ['name' => 'N EVENTUAL'],
            ['name' => 'N FAJINERO'],
            ['name' => 'N FOTOGRAFO'],
            ['name' => 'N ING SOPORTE'],
            ['name' => 'N INSPECTOR'],
            ['name' => 'N INTENDENTE'],
            ['name' => 'N JARDINERO'],
            ['name' => 'NA JEFE DE DEPARTAMENTO A'],
            ['name' => 'NB JEFE DE DEPARTAMENTO B'],
            ['name' => 'NC JEFE DE DEPARTAMENTO C'],
            ['name' => 'N MANTENIMIENTO'],
            ['name' => 'N MARIACHI'],
            ['name' => 'N MECANICO AUTOMOTRIZ'],
            ['name' => 'NA MEDICO A'],
            ['name' => 'NB MEDICO B'],
            ['name' => 'N MEDICO VETERINARIO'],
            ['name' => 'N MENSAJERO'],
            ['name' => 'N NOTIFICADOR'],
            ['name' => 'N OFICIAL DE PARTES'],
            ['name' => 'N OFICIAL DE TRANSITO'],
            ['name' => 'N OFICIAL MAYOR'],
            ['name' => 'N PANTEONERO'],
            ['name' => 'N PENSIONADOS'],
            ['name' => 'N PLOMERO'],
            ['name' => 'N POLICIA'],
            ['name' => 'N POLICIA PRIMERO'],
            ['name' => 'N POLICIA SEGUNDO'],
            ['name' => 'N POLICIA TERCERO'],
            ['name' => 'N PRESIDENTE DE LA JUNTA DE LA VILLA DE'],
            ['name' => 'N PRESIDENTE MUNICIPAL'],
            ['name' => 'N PROFESOR'],
            ['name' => 'N PROGRAMACION'],
            ['name' => 'N PROMOTOR'],
            ['name' => 'N PROYECTISTA DIGITAL'],
            ['name' => 'N PSICOLOGO'],
            ['name' => 'N QUIMICO'],
            ['name' => 'N RECEPCIONISTA'],
            ['name' => 'N REGIDOR'],
            ['name' => 'N REGIDORA'],
            ['name' => 'N REPORTERO'],
            ['name' => 'NA SECRETARIA A'],
            ['name' => 'NB SECRETARIA B'],
            ['name' => 'N SECRETARIO DE ACUERDOS'],
            ['name' => 'N SECRETARIO DEL AYUNTAMIENTO'],
            ['name' => 'N SECRETARIO TECNICO'],
            ['name' => 'N SINDICO'],
            ['name' => 'N SOLDADOR'],
            ['name' => 'NA SUB DIRECTOR A'],
            ['name' => 'NB SUB DIRECTOR B'],
            ['name' => 'N SUBSECRETARIO DEL AYUNTAMIENTO'],
            ['name' => 'NA SUPERVISOR A'],
            ['name' => 'NB SUPERVISOR B'],
            ['name' => 'N SUPLENTE'],
            ['name' => 'N TESORERO'],
            ['name' => 'N TOPOGRAFO'],
            ['name' => 'N TRABAJADORA SOCIAL'],
            ['name' => 'N VELADOR'],
            ['name' => 'N VIGILANTE'],

            // Extras que venían al final
            ['name' => 'INSTITUTO MUNICIPAL DE CULTURA'],
            // ['name' => 'XNB ASISTENTE B'],
            // ['name' => 'NB SUPERVISO'],
            // ['name' => 'N PRESIDENTE DE LA JUNTA DE LA VILLA DE'],
        ];

        $data = array_map(function ($position) {
            return [
                'uuid' => Str::uuid(),
                'department_uuid' => null,
                'name' => $position['name'],
                'parent_position_uuid' => null,
                'start_date' => now(),
                'end_date' => null,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }, $positions);

        DB::table('positions')->insert($data);
    }
}