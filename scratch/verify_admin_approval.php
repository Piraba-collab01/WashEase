<?php
require_once __DIR__ . '/../api/config/database.php';
require_once __DIR__ . '/../api/controllers/AdminController.php';

$admin = new AdminController();
$pending = $admin->getPendingUsers();
echo "Pending Users:\n";
print_r($pending);

if (!empty($pending['data'])) {
    foreach ($pending['data'] as $p) {
        if ($p['email'] === 'contact@apexwash.com') {
            echo "Approving Apex Wash & Dry (ID: {$p['user_id']})...\n";
            $res = $admin->approveUser($p['user_id']);
            print_r($res);
        }
    }
}
