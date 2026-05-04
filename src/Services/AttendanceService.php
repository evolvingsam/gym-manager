<?php

namespace App\Services;

use App\Core\Database;
use PDO;
use Exception;

/**
 * Class AttendanceService
 * Handles front-desk check-ins and attendance reporting.
 */
class AttendanceService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Log a member check-in using their member code.
     * 
     * @param string $memberCode
     * @return array The checked-in member's data
     * @throws Exception If member not found or checked in too recently
     */
    public function logCheckIn(string $memberCode): array
    {
        // 1. Find the member
        $stmt = $this->db->prepare("SELECT id, full_name, photo_path FROM members WHERE member_code = ? AND deleted_at IS NULL");
        $stmt->execute([trim($memberCode)]);
        $member = $stmt->fetch();

        if (!$member) {
            throw new Exception("Member not found or account is deleted.");
        }

        // 2. Prevent rapid duplicate check-ins (15-minute cooldown)
        $checkStmt = $this->db->prepare("
            SELECT id FROM attendance 
            WHERE member_id = ? AND check_in_time >= NOW() - INTERVAL 15 MINUTE
        ");
        $checkStmt->execute([$member['id']]);
        if ($checkStmt->fetch()) {
            throw new Exception("{$member['full_name']} has already checked in within the last 15 minutes.");
        }

        // 3. Log the attendance
        $insertStmt = $this->db->prepare("INSERT INTO attendance (member_id) VALUES (?)");
        $insertStmt->execute([$member['id']]);

        return $member;
    }

    /**
     * Get attendance history for a specific member.
     */
    public function getMemberHistory(int $memberId, int $limit = 50): array
    {
        $stmt = $this->db->prepare("
            SELECT check_in_time 
            FROM attendance 
            WHERE member_id = ? 
            ORDER BY check_in_time DESC 
            LIMIT ?
        ");
        $stmt->bindValue(1, $memberId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * Get all check-ins for a specific date (defaults to today).
     */
    public function getDailyCheckIns(string $date = null): array
    {
        $date = $date ?? date('Y-m-d');
        
        $stmt = $this->db->prepare("
            SELECT a.check_in_time, m.full_name, m.member_code 
            FROM attendance a
            JOIN members m ON a.member_id = m.id
            WHERE DATE(a.check_in_time) = ?
            ORDER BY a.check_in_time DESC
        ");
        $stmt->execute([$date]);
        return $stmt->fetchAll();
    }

    /**
     * Get aggregated daily attendance counts for a given month and year.
     */
    public function getMonthlySummary(int $year, int $month): array
    {
        $stmt = $this->db->prepare("
            SELECT DATE(check_in_time) as attendance_date, COUNT(*) as total_visits
            FROM attendance
            WHERE YEAR(check_in_time) = ? AND MONTH(check_in_time) = ?
            GROUP BY DATE(check_in_time)
            ORDER BY attendance_date ASC
        ");
        $stmt->execute([$year, $month]);
        return $stmt->fetchAll();
    }
}