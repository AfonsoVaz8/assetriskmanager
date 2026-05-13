<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\InformationClassification;
use App\Models\RiskClassification;

class ClassificationSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Classificação de Informação
        $infoClassifications = [
            ['name' => 'Público', 'level' => 1],
            ['name' => 'Uso Interno', 'level' => 2],
            ['name' => 'Confidencial', 'level' => 3],
            ['name' => 'Secreto', 'level' => 4],
            ['name' => 'Altamente Secreto', 'level' => 5],
        ];

        foreach ($infoClassifications as $info) {
            InformationClassification::firstOrCreate(['name' => $info['name']], $info);
        }

        // 2. Classificação de Risco
        $riskClassifications = [
            ['name' => 'Muito Baixo', 'score' => 1],
            ['name' => 'Baixo', 'score' => 2],
            ['name' => 'Médio', 'score' => 3],
            ['name' => 'Alto', 'score' => 4],
            ['name' => 'Crítico', 'score' => 5],
        ];

        foreach ($riskClassifications as $risk) {
            RiskClassification::firstOrCreate(['name' => $risk['name']], $risk);
        }
    }
}
