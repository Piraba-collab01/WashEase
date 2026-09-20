<?php
$url = 'http://localhost:8000/index.php?action=login';
$data = [
    'username' => 'admin@washease.com',
    'password' => 'admin123'
];
$options = [
    'http' => [
        'header'  => "Content-Type: application/json\r\n",
        'method'  => 'POST',
        'content' => json_encode($data),
        'ignore_errors' => true
    ]
];
$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);
echo $result . "\n";
