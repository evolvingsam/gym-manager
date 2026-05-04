<?php

namespace App\Services;

use App\Core\Database;
use PDO;

/**
 * Class FitnessService
 * Handles member workout logging and body metric tracking.
 */
class FitnessService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Log a specific exercise.
     */
    public function logWorkout(int $memberId, array $data): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO workout_logs (member_id, exercise_name, sets, reps, weight_kg, log_date) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $memberId,
            trim($data['exercise_name']),
            (int)$data['sets'],
            (int)$data['reps'],
            (float)$data['weight_kg'],
            $data['log_date']
        ]);
    }

    /**
     * Get recent workout history.
     */
    public function getWorkoutHistory(int $memberId, int $limit = 50): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM workout_logs 
            WHERE member_id = ? 
            ORDER BY log_date DESC, created_at DESC 
            LIMIT ?
        ");
        $stmt->bindValue(1, $memberId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Log a body measurement.
     */
    public function logBodyMetric(int $memberId, array $data): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO body_metrics (member_id, weight_kg, body_fat_percent, recorded_date) 
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([
            $memberId,
            (float)$data['weight_kg'],
            !empty($data['body_fat_percent']) ? (float)$data['body_fat_percent'] : null,
            $data['recorded_date']
        ]);
    }

    /**
     * Get body metric history.
     */
    public function getMetricHistory(int $memberId, int $limit = 30): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM body_metrics 
            WHERE member_id = ? 
            ORDER BY recorded_date DESC 
            LIMIT ?
        ");
        $stmt->bindValue(1, $memberId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Delete a workout log (if user made a mistake).
     */
    public function deleteWorkoutLog(int $logId, int $memberId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM workout_logs WHERE id = ? AND member_id = ?");
        return $stmt->execute([$logId, $memberId]);
    }
}