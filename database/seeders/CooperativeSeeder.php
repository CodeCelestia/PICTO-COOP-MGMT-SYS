<?php

namespace Database\Seeders;

use App\Models\Cooperative;
use App\Models\CooperativeType;
use Illuminate\Database\Seeder;

class CooperativeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cooperatives = [
            [
                'name' => 'Picto Multi-Purpose Cooperative',
                'registration_number' => 'CDA-REG-5-2020-001',
                'classification' => 'Primary',
                'type_names' => ['Multipurpose'],
                'date_established' => '2020-01-15',
                'address' => 'McArthur Highway, Barangay San Jose',
                'region' => 'Region III (Central Luzon)',
                'province' => 'Pampanga',
                'city_municipality' => 'San Fernando City',
                'barangay' => 'San Jose',
                'email' => 'info@pictocoop.ph',
                'phone' => '+63 45 123 4567',
                'status' => 'Active',
                'accreditation_status' => 'Level 2',
                'accreditation_date' => '2022-06-01',
            ],
            [
                'name' => 'Angeles City Credit Cooperative',
                'registration_number' => 'CDA-REG-3-2018-045',
                'classification' => 'Primary',
                'type_names' => ['Credit'],
                'date_established' => '2018-03-20',
                'address' => 'MacArthur Highway, Nepo Mart Complex',
                'region' => 'Region III (Central Luzon)',
                'province' => 'Pampanga',
                'city_municipality' => 'Angeles City',
                'barangay' => 'Balibago',
                'email' => 'accc@example.com',
                'phone' => '+63 45 987 6543',
                'status' => 'Active',
                'accreditation_status' => 'Level 3',
                'accreditation_date' => '2021-08-15',
            ],
            [
                'name' => 'Tarlac Farmers Producers Cooperative',
                'registration_number' => 'CDA-REG-3-2019-112',
                'classification' => 'Primary',
                'type_names' => ['Producers'],
                'date_established' => '2019-05-10',
                'address' => 'Romulo Highway',
                'region' => 'Region III (Central Luzon)',
                'province' => 'Tarlac',
                'city_municipality' => 'Tarlac City',
                'barangay' => 'Santa Maria',
                'email' => 'farmers@tarlaccoop.ph',
                'phone' => '+63 45 456 7890',
                'status' => 'Active',
                'accreditation_status' => 'Level 1',
                'accreditation_date' => '2020-11-20',
            ],
            [
                'name' => 'Nueva Ecija Marketing Cooperative',
                'registration_number' => 'CDA-REG-3-2021-089',
                'classification' => 'Primary',
                'type_names' => ['Marketing'],
                'date_established' => '2021-07-25',
                'address' => 'Maharlika Highway, City Market Area',
                'region' => 'Region III (Central Luzon)',
                'province' => 'Nueva Ecija',
                'city_municipality' => 'Cabanatuan City',
                'barangay' => 'Poblacion',
                'email' => null,
                'phone' => null,
                'status' => 'Active',
                'accreditation_status' => null,
                'accreditation_date' => null,
            ],
            [
                'name' => 'Bulacan Transport Service Cooperative',
                'registration_number' => 'CDA-REG-3-2017-234',
                'classification' => 'Primary',
                'type_names' => ['Transport'],
                'date_established' => '2017-11-05',
                'address' => 'MacArthur Highway',
                'region' => 'Region III (Central Luzon)',
                'province' => 'Bulacan',
                'city_municipality' => 'Plaridel',
                'barangay' => 'Poblacion',
                'email' => 'transport@bulacancoop.ph',
                'phone' => '+63 44 123 4567',
                'status' => 'Inactive',
                'accreditation_status' => 'Level 1',
                'accreditation_date' => '2019-04-10',
            ],
        ];

        foreach ($cooperatives as $cooperative) {
            $typeNames = $cooperative['type_names'] ?? [];
            unset($cooperative['type_names']);

            $record = Cooperative::updateOrCreate(
                ['registration_number' => $cooperative['registration_number']],
                $cooperative
            );

            $typeIds = CooperativeType::whereIn('name', $typeNames)->pluck('id')->all();
            $record->types()->sync($typeIds);
        }
    }
}

