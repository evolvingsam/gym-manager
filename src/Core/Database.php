<?php

namespace App\Core;

use PDO;
use PDOException;
use Exception;

/**
 * Class Database
 * Handles the PDO connection using the Singleton pattern.
 */
class Database
{
    private static ?PDO $instance = null;

    /**
     * Get the PDO database instance.
     * 
     * @return PDO
     * @throws Exception If connection fails.
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            
            // 1. Point to the local, secure config file first
            $configFile = __DIR__ . '/../../config.local.php';
            
            // 2. If it doesn't exist (like on GitHub or a new server), use the dummy config
            if (!file_exists($configFile)) {
                $configFile = __DIR__ . '/../../config.php';
            }
            
            // 3. Load the credentials into an array
            $config = require $configFile;

            $charset = 'utf8mb4';
            $dsn = "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=$charset";
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                // 4. Inject the loaded credentials into the PDO connection
                self::$instance = new PDO($dsn, $config['db_user'], $config['db_pass'], $options);
            } catch (PDOException $e) {
                // Log error to file (Simplified for local setup)
                error_log($e->getMessage());
                throw new Exception("Database connection failed. Check your local logs.");
            }
        }

        return self::$instance;
    }

    // Prevent cloning and serialization
    private function __construct() {}
    private function __clone() {}
}