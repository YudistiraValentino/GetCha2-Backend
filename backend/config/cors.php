<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout', 'admin/*', '*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['*'], // Buka semua untuk sementara
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'], // ✅ Izinkan semua header (termasuk Authorization)
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false, // Set false jika pakai Bearer Token murni (tanpa cookie)
];