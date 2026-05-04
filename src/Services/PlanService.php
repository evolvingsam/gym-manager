<?php

namespace App\Services;

use App\Core\Database;
use PDO;
use PDOException;
use Exception;

/**
 * Class PlanService
 * Handles CRUD operations for Membership Plans.
 */
class PlanService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAllPlans(): array
    {
        $stmt = $this->db->query("SELECT * FROM plans ORDER BY price ASC");
        return $stmt->fetchAll();
    }

    public function getPlanById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM plans WHERE id = ?");
        $stmt->execute([$id]);
        $plan = $stmt->fetch();
        return $plan ?: null;
    }

    public function createPlan(array $data): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO plans (name, duration_months, price, features) 
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([
            trim($data['name']),
            (int)$data['duration_months'],
            (float)$data['price'],
            trim($data['features'] ?? '')
        ]);
    }

    public function updatePlan(int $id, array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE plans 
            SET name = ?, duration_months = ?, price = ?, features = ? 
            WHERE id = ?
        ");
        return $stmt->execute([
            trim($data['name']),
            (int)$data['duration_months'],
            (float)$data['price'],
            trim($data['features'] ?? ''),
            $id
        ]);
    }

    public function deletePlan(int $id): bool
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM plans WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            // Error 1451 is MySQL's standard code for a Foreign Key Constraint violation
            if ($e->errorInfo[1] === 1451) {
                throw new Exception("Cannot delete this plan because it is assigned to existing members.");
            }
            throw $e;
        }
    }
}