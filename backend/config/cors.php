<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout', 'admin/*', '*'], // Tambah '*' biar aman
    'allowed_methods' => ['*'],
    'allowed_origins' => ['*'], // Buka untuk semua dulu sementara
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'], // Izinkan semua header termasuk Authorization
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true, // INI PENTING
];