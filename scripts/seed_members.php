<?php
// scripts/seed_members.php

require_once __DIR__ . '/../vendor/autoload.php';
use App\Core\Database;
use Faker\Factory;

echo "Starting database seed for LASUFit...\n";

// Initialize Faker with Nigerian locale
$faker = Factory::create('en_NG');

try {
    $db = Database::getInstance();
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage() . "\n");
}

// SQL matches ONLY the columns from your DESCRIBE output
$sql = "INSERT INTO members (member_code, full_name, email, password, phone, emergency_contact, join_date, created_at) 
        VALUES (:member_code, :full_name, :email, :password, :phone, :emergency_contact, :join_date, :created_at)";

$stmt = $db->prepare($sql);

// Default password so you can test logging in
$defaultPassword = password_hash('password123', PASSWORD_DEFAULT);

$db->beginTransaction();

try {
    for ($i = 0; $i < 100; $i++) {
        // Generate a random date from the last 6 months
        $randomDate = $faker->dateTimeBetween('-6 months', 'now');
        
        $stmt->execute([
            ':member_code'       => 'GYM-' . strtoupper($faker->bothify('????')),
            ':full_name'         => $faker->name(),
            ':email'             => $faker->unique()->safeEmail(),
            ':password'          => $defaultPassword,
            ':phone'             => $faker->phoneNumber(),
            ':emergency_contact' => $faker->phoneNumber(),
            ':join_date'         => $randomDate->format('Y-m-d'),
            ':created_at'        => $randomDate->format('Y-m-d H:i:s')
        ]);
    }
    
    $db->commit();
    echo "Success! 50 dummy members have been inserted into the database.\n";
    
} catch (Exception $e) {
    $db->rollBack();
    die("Seeding failed: " . $e->getMessage() . "\n");
}