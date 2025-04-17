<?php

namespace Ultra\UltraConfigManager\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Ultra\UltraConfigManager\Models\UltraConfigModel; // Necessario per creare il parent
use Ultra\UltraConfigManager\Models\UltraConfigVersion;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Ultra\UltraConfigManager\Models\UltraConfigVersion>
 */
class UltraConfigVersionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = UltraConfigVersion::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Trova un UltraConfigModel esistente o creane uno nuovo
        // per assicurare che uconfig_id sia valido.
        $config = UltraConfigModel::first() ?? UltraConfigModel::factory()->create();

        return [
            'uconfig_id' => $config->id,
            'version' => $this->faker->unique()->numberBetween(1, 1000), // Assicura versioni uniche per test semplici
            'key' => $config->key, // Usa la chiave del modello padre
            'category' => $this->faker->randomElement(['system', 'application', 'security', 'performance', null, '']),
            'note' => $this->faker->optional()->sentence,
            'value' => $this->faker->realText(100), // Il cast si occuperà della criptazione
            'user_id' => null, // O un ID utente valido se necessario e se la colonna esiste
            // created_at e updated_at sono gestiti automaticamente
        ];
    }

    /**
     * Indica che la versione appartiene a un config specifico.
     *
     * @param UltraConfigModel $config
     * @return static
     */
    public function forConfig(UltraConfigModel $config): static
    {
         return $this->state(fn (array $attributes) => [
             'uconfig_id' => $config->id,
             'key' => $config->key, // Aggiorna anche la chiave per coerenza
         ]);
    }
}