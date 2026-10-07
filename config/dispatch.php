<?php

return [
    'max_radius_meters' => env('DISPATCH_MAX_RADIUS', 10000), // 10 km
    'max_eta_seconds'   => env('DISPATCH_MAX_ETA', 900),      // 15 menit

    // Bobot scoring (total = 1.0)
    'weights' => [
        'eta'      => 0.35,
        'distance' => 0.10,
        'status'   => 0.15,
        'crew'     => 0.10,
        'match'    => 0.15,
        'hospital' => 0.15,
    ],

    // Default speed km/h bila routing OSRM offline (30 km/h di dalam kota)
    'average_speed_kmh' => 30,
];
