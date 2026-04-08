<?php

namespace Database\Seeders;

use App\Models\CooperativeType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CooperativeTypeSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding CDA cooperative types...');

        $types = [
            'Credit',
            'Consumers',
            'Producers',
            'Marketing',
            'Service',
            'Multipurpose',
            'Advocacy',
            'Transport',
            'Water',
            'Electric',
            'Housing',
            'Health Services',
            'Laboratory',
            'Worker',
        ];

        $typeSlugs = array_map(fn ($name) => Str::slug($name), $types);

        CooperativeType::whereNotIn('slug', $typeSlugs)->delete();

        foreach ($types as $index => $typeName) {
            $record = CooperativeType::withTrashed()->updateOrCreate(
                ['slug' => Str::slug($typeName)],
                [
                    'name' => $typeName,
                    'description' => null,
                    'level' => 'region',
                    'parent_id' => null,
                    'sort_order' => $index + 1,
                ]
            );

            if ($record->trashed()) {
                $record->restore();
            }
        }

        $this->command->info('CDA cooperative types seeding complete.');
    }
}
