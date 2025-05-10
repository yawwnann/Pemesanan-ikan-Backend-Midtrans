<?php
// config/cors.php

return [
    'paths' => ['api/*', 'login', 'register', 'logout', 'sanctum/csrf-cookie'], // Cakup semua path yang relevan
    'allowed_methods' => ['*'],
    'allowed_origins' => ['http://localhost:5173'], // Ganti dengan URL frontend Anda jika berbeda
    // 'allowed_origins' => ['*'], // Gunakan ini jika masih error untuk tes ekstrem
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];