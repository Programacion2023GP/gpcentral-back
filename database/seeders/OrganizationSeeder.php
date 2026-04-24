<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrganizationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $organizations = [
            [
                "code" => "PR",
                "name" => "Presidencia",
            ],
            [
                "code" => "SIDEAPAAR",
                "name" => "SIDEAPAAR",
            ],
            [
                "code" => "SIDEAPA",
                "name" => "SIDEAPA",
            ],
            [
                "code" => "EXPOFERIA",
                "name" => "EXPOFERIA",
            ],
            [
                "code" => "DIF",
                "name" => "DIF",
            ],
        ];

        $data = array_map(function ($organization) {
            return [
                'code' => $organization['code'],
                'name' => $organization['name'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }, $organizations);

        DB::table('organizations')->insert($data);
    }
}
