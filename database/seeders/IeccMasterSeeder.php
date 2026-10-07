<?php

namespace Database\Seeders;

use App\Models\Auth\Role;
use App\Models\Master\Agency;
use App\Models\Master\Facility;
use App\Models\Master\Unit;
use App\Models\Master\UnitMember;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class IeccMasterSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Roles IECC sesuai PRD Bagian 4
        $rolesData = [
            ['name' => 'Operator IECC', 'slug' => 'operator'],
            ['name' => 'Supervisor IECC', 'slug' => 'supervisor'],
            ['name' => 'Petugas Lapangan', 'slug' => 'field-officer'],
            ['name' => 'Admin Instansi', 'slug' => 'agency-admin'],
            ['name' => 'Petugas RS', 'slug' => 'hospital-staff'],
            ['name' => 'Pimpinan', 'slug' => 'executive'],
            ['name' => 'Warga', 'slug' => 'citizen'],
        ];

        $roles = [];
        foreach ($rolesData as $r) {
            $roles[$r['slug']] = Role::firstOrCreate(['slug' => $r['slug']], ['name' => $r['name']]);
        }

        // 2. Instansi (Agencies)
        $agenciesData = [
            [
                'code'      => 'IECC',
                'name'      => 'Command Center Kota',
                'type'      => 'COMMAND',
                'phone'     => '112',
                'is_active' => true,
            ],
            [
                'code'      => 'DINKES',
                'name'      => 'Dinas Kesehatan / PSC 119',
                'type'      => 'HEALTH',
                'phone'     => '119',
                'is_active' => true,
            ],
            [
                'code'      => 'DAMKAR',
                'name'      => 'Dinas Pemadam Kebakaran & Penyelamatan',
                'type'      => 'FIRE',
                'phone'     => '113',
                'is_active' => true,
            ],
            [
                'code'      => 'POLISI',
                'name'      => 'Kepolisian Resor Kota (Polresta)',
                'type'      => 'POLICE',
                'phone'     => '110',
                'is_active' => true,
            ],
            [
                'code'      => 'BPBD',
                'name'      => 'Badan Penanggulangan Bencana Daerah',
                'type'      => 'DISASTER',
                'phone'     => '021-500115',
                'is_active' => true,
            ],
            [
                'code'      => 'SATPOL',
                'name'      => 'Satuan Polisi Pamong Praja',
                'type'      => 'CIVIL_ORDER',
                'phone'     => '021-500116',
                'is_active' => true,
            ],
            [
                'code'      => 'RS-UD',
                'name'      => 'RSUD Kota',
                'type'      => 'HOSPITAL',
                'phone'     => '021-500118',
                'is_active' => true,
            ],
        ];

        $agencies = [];
        foreach ($agenciesData as $a) {
            $agencies[$a['code']] = Agency::firstOrCreate(['code' => $a['code']], $a);
        }

        // 3. Fasilitas (Facilities) - Menggunakan contoh koordinat kota Jakarta Pusat / area simulasi
        $facilitiesData = [
            [
                'agency_id'         => $agencies['RS-UD']->id,
                'type'              => 'HOSPITAL',
                'name'              => 'RSUD Kota Pusat',
                'address'           => 'Jl. Kesehatan No. 10',
                'phone'             => '021-500118',
                'lat'               => -6.1753924,
                'lng'               => 106.8271528,
                'services'          => ['emergency_room', 'trauma', 'obstetric', 'icu', 'neonatal'],
                'er_beds_total'     => 30,
                'er_beds_available' => 12,
                'er_status'         => 'NORMAL',
            ],
            [
                'agency_id'         => $agencies['DINKES']->id,
                'type'              => 'PUSKESMAS',
                'name'              => 'Puskesmas Kecamatan Gambir',
                'address'           => 'Jl. Tanah Abang II',
                'phone'             => '021-3841234',
                'lat'               => -6.1731200,
                'lng'               => 106.8154300,
                'services'          => ['emergency_room', 'maternal'],
                'er_beds_total'     => 10,
                'er_beds_available' => 6,
                'er_status'         => 'NORMAL',
            ],
            [
                'agency_id'         => $agencies['DAMKAR']->id,
                'type'              => 'FIRE_STATION',
                'name'              => 'Pos Pemadam Kebakaran Gambir',
                'address'           => 'Jl. Medan Merdeka Barat',
                'phone'             => '113',
                'lat'               => -6.1764500,
                'lng'               => 106.8213400,
                'services'          => ['rescue', 'fire_control'],
                'er_status'         => 'NORMAL',
            ],
            [
                'agency_id'         => $agencies['POLISI']->id,
                'type'              => 'POLICE_STATION',
                'name'              => 'Polsek Metro Gambir',
                'address'           => 'Jl. Cideng Barat',
                'phone'             => '110',
                'lat'               => -6.1712000,
                'lng'               => 106.8115000,
                'services'          => ['patrol', 'traffic_control'],
                'er_status'         => 'NORMAL',
            ],
        ];

        $facilities = [];
        foreach ($facilitiesData as $f) {
            $lat = $f['lat'];
            $lng = $f['lng'];
            $facility = Facility::firstOrCreate(
                ['name' => $f['name']],
                array_merge($f, [
                    'location' => DB::raw("ST_GeomFromText('POINT({$lng} {$lat})', 0)"),
                ])
            );
            $facilities[$f['name']] = $facility;
        }

        // 4. Units (Ambulans, Damkar, Polisi, Tim Rescue)
        $unitsData = [
            [
                'agency_id'        => $agencies['DINKES']->id,
                'code'             => 'AMB-01',
                'type'             => 'AMBULANCE',
                'status'           => 'AVAILABLE',
                'crew_ready'       => true,
                'base_facility_id' => $facilities['Puskesmas Kecamatan Gambir']->id ?? null,
                'lat'              => -6.1731200,
                'lng'              => 106.8154300,
            ],
            [
                'agency_id'        => $agencies['DAMKAR']->id,
                'code'             => 'DAM-01',
                'type'             => 'FIRE_TRUCK',
                'status'           => 'AVAILABLE',
                'crew_ready'       => true,
                'base_facility_id' => $facilities['Pos Pemadam Kebakaran Gambir']->id ?? null,
                'lat'              => -6.1764500,
                'lng'              => 106.8213400,
            ],
            [
                'agency_id'        => $agencies['POLISI']->id,
                'code'             => 'POL-01',
                'type'             => 'POLICE_PATROL',
                'status'           => 'AVAILABLE',
                'crew_ready'       => true,
                'base_facility_id' => $facilities['Polsek Metro Gambir']->id ?? null,
                'lat'              => -6.1712000,
                'lng'              => 106.8115000,
            ],
            [
                'agency_id'        => $agencies['BPBD']->id,
                'code'             => 'RES-01',
                'type'             => 'RESCUE_TEAM',
                'status'           => 'AVAILABLE',
                'crew_ready'       => true,
                'base_facility_id' => null,
                'lat'              => -6.1745000,
                'lng'              => 106.8240000,
            ],
        ];

        $createdUnits = [];
        foreach ($unitsData as $u) {
            $lat = $u['lat'];
            $lng = $u['lng'];
            $unitModel = Unit::firstOrCreate(
                ['code' => $u['code']],
                array_merge($u, [
                    'location' => DB::raw("ST_GeomFromText('POINT({$lng} {$lat})', 0)"),
                ])
            );
            $createdUnits[$u['code']] = $unitModel;
        }

        // 5. Contoh Pengguna sesuai Role (Operator, Petugas Lapangan per Unit, Petugas RS, Warga)
        $usersData = [
            [
                'name'      => 'Operator Command Center',
                'email'     => 'operator@iecc.local',
                'password'  => Hash::make('12345678'),
                'role_slug' => 'operator',
                'agency_id' => $agencies['IECC']->id,
                'user_type' => 'STAFF',
            ],
            [
                'name'      => 'Supervisor IECC',
                'email'     => 'supervisor@iecc.local',
                'password'  => Hash::make('12345678'),
                'role_slug' => 'supervisor',
                'agency_id' => $agencies['IECC']->id,
                'user_type' => 'STAFF',
            ],
            [
                'name'      => 'Petugas Ambulans 01 (PSC 119)',
                'email'     => 'field.ambulance@iecc.local',
                'password'  => Hash::make('12345678'),
                'role_slug' => 'field-officer',
                'agency_id' => $agencies['DINKES']->id,
                'user_type' => 'FIELD',
                'unit_code' => 'AMB-01',
                'unit_role' => 'paramedic',
            ],
            [
                'name'      => 'Petugas Damkar 01 (Rescue)',
                'email'     => 'field.damkar@iecc.local',
                'password'  => Hash::make('12345678'),
                'role_slug' => 'field-officer',
                'agency_id' => $agencies['DAMKAR']->id,
                'user_type' => 'FIELD',
                'unit_code' => 'DAM-01',
                'unit_role' => 'officer',
            ],
            [
                'name'      => 'Petugas Patroli Polisi 01',
                'email'     => 'field.polisi@iecc.local',
                'password'  => Hash::make('12345678'),
                'role_slug' => 'field-officer',
                'agency_id' => $agencies['POLISI']->id,
                'user_type' => 'FIELD',
                'unit_code' => 'POL-01',
                'unit_role' => 'officer',
            ],
            [
                'name'      => 'Petugas Tim Rescue BPBD',
                'email'     => 'field.rescue@iecc.local',
                'password'  => Hash::make('12345678'),
                'role_slug' => 'field-officer',
                'agency_id' => $agencies['BPBD']->id,
                'user_type' => 'FIELD',
                'unit_code' => 'RES-01',
                'unit_role' => 'rescuer',
            ],
            [
                'name'      => 'Admin Damkar',
                'email'     => 'admin.damkar@iecc.local',
                'password'  => Hash::make('12345678'),
                'role_slug' => 'agency-admin',
                'agency_id' => $agencies['DAMKAR']->id,
                'user_type' => 'STAFF',
            ],
            [
                'name'      => 'Petugas IGD RSUD',
                'email'     => 'hospital.rsud@iecc.local',
                'password'  => Hash::make('12345678'),
                'role_slug' => 'hospital-staff',
                'agency_id' => $agencies['RS-UD']->id,
                'user_type' => 'STAFF',
            ],
            [
                'name'      => 'Walikota / Pimpinan',
                'email'     => 'pimpinan@iecc.local',
                'password'  => Hash::make('12345678'),
                'role_slug' => 'executive',
                'agency_id' => $agencies['IECC']->id,
                'user_type' => 'STAFF',
            ],
            [
                'name'      => 'Ahmad Warga Kota',
                'email'     => 'warga@iecc.local',
                'password'  => Hash::make('12345678'),
                'phone'     => '081234567890',
                'role_slug' => 'citizen',
                'agency_id' => null,
                'user_type' => 'CITIZEN',
            ],
        ];

        foreach ($usersData as $ud) {
            $roleSlug = $ud['role_slug'];
            $unitCode = $ud['unit_code'] ?? null;
            $unitRole = $ud['unit_role'] ?? 'officer';

            unset($ud['role_slug'], $ud['unit_code'], $ud['unit_role']);

            $role = $roles[$roleSlug] ?? null;
            $user = User::firstOrCreate(
                ['email' => $ud['email']],
                array_merge($ud, ['role_id' => $role?->id])
            );

            if ($role) {
                $user->roles()->syncWithoutDetaching([$role->id]);
            }

            // Assign user to unit member if specified
            if ($unitCode && isset($createdUnits[$unitCode])) {
                UnitMember::firstOrCreate(
                    [
                        'unit_id' => $createdUnits[$unitCode]->id,
                        'user_id' => $user->id,
                    ],
                    [
                        'role'    => $unitRole,
                        'on_duty' => true,
                    ]
                );
            }
        }
    }
}
