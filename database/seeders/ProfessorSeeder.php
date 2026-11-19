<?php

namespace Database\Seeders;

use App\Models\Professor;
use Illuminate\Database\Seeder;

class ProfessorSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'Prof. Juan Dela Cruz',
            'Prof. Maria Clara Santos',
            'Prof. Roberto Reyes',
            'Prof. Elena Bautista',
            'Prof. Carlos Mendoza',
        ] as $name) {
            Professor::firstOrCreate(
                ['name' => $name],
                ['is_active' => true],
            );
        }
    }
}
