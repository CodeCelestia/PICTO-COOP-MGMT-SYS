<?php

namespace Database\Seeders;

use App\Models\CooperativeType;
use Illuminate\Database\Seeder;

class CooperativeTypeSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding cooperative hierarchy types...');

        $structure = [
            'region i' => [
                'name' => 'Region I (Ilocos Region)',
                'provinces' => [
                    'ilocos norte' => [
                        'name' => 'Ilocos Norte',
                        'municipalities' => [
                            'luna' => 'Luna',
                            'batac' => 'Batac',
                        ],
                    ],
                    'ilocos sur' => [
                        'name' => 'Ilocos Sur',
                        'municipalities' => [
                            'vigan' => 'Vigan',
                            'cabugao' => 'Cabugao',
                        ],
                    ],
                ],
            ],
            'region iii' => [
                'name' => 'Region III (Central Luzon)',
                'provinces' => [
                    'pampanga' => [
                        'name' => 'Pampanga',
                        'municipalities' => [
                            'san fernando' => 'San Fernando',
                            'angeles city' => 'Angeles City',
                        ],
                    ],
                    'tarlac' => [
                        'name' => 'Tarlac',
                        'municipalities' => [
                            'tarlac city' => 'Tarlac City',
                            'paniqui' => 'Paniqui',
                        ],
                    ],
                ],
            ],
        ];

        foreach ($structure as $regionSlug => $regionData) {
            $region = CooperativeType::updateOrCreate(
                ['slug' => $regionSlug],
                ['name' => $regionData['name'], 'level' => 'region', 'sort_order' => 0]
            );

            foreach ($regionData['provinces'] as $provinceSlug => $provinceData) {
                $province = CooperativeType::updateOrCreate(
                    ['slug' => $provinceSlug],
                    [
                        'name' => $provinceData['name'],
                        'level' => 'province',
                        'parent_id' => $region->id,
                        'sort_order' => 0,
                    ]
                );

                foreach ($provinceData['municipalities'] as $municipalitySlug => $municipalityName) {
                    CooperativeType::updateOrCreate(
                        ['slug' => $municipalitySlug],
                        [
                            'name' => $municipalityName,
                            'level' => 'municipality',
                            'parent_id' => $province->id,
                            'sort_order' => 0,
                        ]
                    );
                }
            }
        }

        $this->command->info('Cooperative type hierarchy seeding complete.');
    }
}
