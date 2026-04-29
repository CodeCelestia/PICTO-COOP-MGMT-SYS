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
            ],
            [
                'name' => 'Angeles City Credit Cooperative',
                'registration_number' => 'CDA-REG-3-2018-045',
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
            ],
            [
                'name' => 'Tarlac Farmers Producers Cooperative',
                'registration_number' => 'CDA-REG-3-2019-112',
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
            ],
            [
                'name' => 'Nueva Ecija Marketing Cooperative',
                'registration_number' => 'CDA-REG-3-2021-089',
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
            ],
            [
                'name' => 'Bulacan Transport Service Cooperative',
                'registration_number' => 'CDA-REG-3-2017-234',
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
            ],
            // Additional cooperatives requested
            [
                'name' => 'Batangas Multi-Purpose Cooperative',
                'registration_number' => 'CDA-REG-4-2001-002',
                'type_names' => ['Multipurpose'],
                'classification' => 'small',
                'date_established' => '2001-06-20',
                'address' => 'Batangas City, Batangas',
                'region' => 'Region IV-A (CALABARZON)',
                'province' => 'Batangas',
                'city_municipality' => 'Batangas City',
                'barangay' => null,
                'email' => null,
                'phone' => null,
                'status' => 'Active',
            ],
            [
                'name' => 'Cebu Producers Cooperative',
                'registration_number' => 'CDA-REG-7-1998-003',
                'type_names' => ['Producers'],
                'classification' => 'small',
                'date_established' => '1998-09-10',
                'address' => 'Cebu City, Cebu',
                'region' => 'Region VII (Central Visayas)',
                'province' => 'Cebu',
                'city_municipality' => 'Cebu City',
                'barangay' => null,
                'email' => null,
                'phone' => null,
                'status' => 'Active',
            ],
            [
                'name' => 'Davao Consumers Cooperative',
                'registration_number' => 'CDA-REG-11-2005-004',
                'type_names' => ['Consumers'],
                'classification' => 'medium',
                'date_established' => '2005-01-08',
                'address' => 'Davao City, Davao del Sur',
                'region' => 'Region XI (Davao Region)',
                'province' => 'Davao del Sur',
                'city_municipality' => 'Davao City',
                'barangay' => null,
                'email' => null,
                'phone' => null,
                'status' => 'Active',
            ],
            [
                'name' => 'Iloilo Service Cooperative',
                'registration_number' => 'CDA-REG-6-2003-005',
                'type_names' => ['Service'],
                'classification' => 'small',
                'date_established' => '2003-04-22',
                'address' => 'Iloilo City, Iloilo',
                'region' => 'Region VI (Western Visayas)',
                'province' => 'Iloilo',
                'city_municipality' => 'Iloilo City',
                'barangay' => null,
                'email' => null,
                'phone' => null,
                'status' => 'Active',
            ],
            [
                'name' => 'Laguna Transport Cooperative',
                'registration_number' => 'CDA-REG-4-2010-006',
                'type_names' => ['Transport'],
                'classification' => 'small',
                'date_established' => '2010-07-14',
                'address' => 'Sta. Cruz, Laguna',
                'region' => 'Region IV-A (CALABARZON)',
                'province' => 'Laguna',
                'city_municipality' => 'Sta. Cruz',
                'barangay' => null,
                'email' => null,
                'phone' => null,
                'status' => 'Active',
            ],
            [
                'name' => 'Mindanao Agrarian Reform Cooperative',
                'registration_number' => 'CDA-REG-12-1997-007',
                'type_names' => ['Producers'],
                'classification' => 'small',
                'date_established' => '1997-11-05',
                'address' => 'General Santos City, South Cotabato',
                'region' => 'Region XII (SOCCSKSARGEN)',
                'province' => 'South Cotabato',
                'city_municipality' => 'General Santos City',
                'barangay' => null,
                'email' => null,
                'phone' => null,
                'status' => 'Active',
            ],
            [
                'name' => 'Northern Luzon Marketing Cooperative',
                'registration_number' => 'CDA-REG-1-2008-008',
                'type_names' => ['Marketing'],
                'classification' => 'billion',
                'date_established' => '2008-02-17',
                'address' => 'Laoag City, Ilocos Norte',
                'region' => 'Region I (Ilocos Region)',
                'province' => 'Ilocos Norte',
                'city_municipality' => 'Laoag City',
                'barangay' => null,
                'email' => null,
                'phone' => null,
                'status' => 'Active',
            ],
            [
                'name' => 'Quezon Housing Cooperative',
                'registration_number' => 'CDA-REG-4-2000-009',
                'type_names' => ['Housing'],
                'classification' => 'small',
                'date_established' => '2000-08-30',
                'address' => 'Lucena City, Quezon',
                'region' => 'Region IV-A (CALABARZON)',
                'province' => 'Quezon',
                'city_municipality' => 'Lucena City',
                'barangay' => null,
                'email' => null,
                'phone' => null,
                'status' => 'Inactive',
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

