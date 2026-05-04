<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;

try {
    $db = Database::getInstance();

    // Clear existing data
    $db->exec("SET FOREIGN_KEY_CHECKS = 0; TRUNCATE users; TRUNCATE plans; SET FOREIGN_KEY_CHECKS = 1;");

    // Seed Admin (Password: admin123)
    $stmt = $db->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
    $stmt->execute(['admin', password_hash('admin123', PASSWORD_DEFAULT), 'Samuel Admin', 'admin']);

    // Seed Plans
    $plans = [
        ['Basic Monthly', 1, 5000.00, 'Gym access only'],
        ['Premium Quarterly', 3, 13500.00, 'Gym + Trainer'],
        ['Annual VIP', 12, 50000.00, 'Full access + Spa']
    ];
    $stmt = $db->prepare("INSERT INTO plans (name, duration_months, price, features) VALUES (?, ?, ?, ?)");
    foreach ($plans as $plan) {
        $stmt->execute($plan);
    }

    echo "Database seeded successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}