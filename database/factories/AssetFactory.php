<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Asset>
 */
class AssetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'information_classification_id' => InformationClassification::inRandomOrder()->first()?->id,
            'risk_classification_id' => RiskClassification::inRandomOrder()->first()?->id,
        ];
    }
}
