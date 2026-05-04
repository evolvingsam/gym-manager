<?php

namespace App\Services;

use App\Core\Database;
use PDO;

/**
 * Class GamificationService
 * Handles community leaderboards and member rankings.
 */
class GamificationService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get the top attendees for the current month.
     */
    public function getTopAttendees(int $limit = 5): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                m.full_name, 
                m.photo_path,
                COUNT(a.id) as total_visits
            FROM attendance a
            JOIN members m ON a.member_id = m.id
            WHERE YEAR(a.check_in_time) = YEAR(CURRENT_DATE) 
              AND MONTH(a.check_in_time) = MONTH(CURRENT_DATE)
              AND m.deleted_at IS NULL
            GROUP BY m.id
            ORDER BY total_visits DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get the strongest members based on total volume lifted this month.
     * Volume = Sets * Reps * Weight
     */
    public function getHeavyLifters(int $limit = 5): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                m.full_name,
                m.photo_path,
                SUM(w.sets * w.reps * w.weight_kg) as total_volume_kg
            FROM workout_logs w
            JOIN members m ON w.member_id = m.id
            WHERE YEAR(w.log_date) = YEAR(CURRENT_DATE) 
              AND MONTH(w.log_date) = MONTH(CURRENT_DATE)
              AND m.deleted_at IS NULL
            GROUP BY m.id
            HAVING total_volume_kg > 0
            ORDER BY total_volume_kg DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}