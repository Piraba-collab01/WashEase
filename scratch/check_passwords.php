<?php
$hashes = [
    'admin' => '$2y$10$tM9sEw10U34u8.7d9H36cuzm2uRpyqj3P8H9B6l4vYF2JpQ6hH/Hq', // admin123
];

foreach ($hashes as $user => $hash) {
    $verify = password_verify('admin123', $hash);
    echo "$user with 'admin123': " . ($verify ? 'MATCH' : 'NO MATCH') . "\n";
}
