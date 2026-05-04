<?php

namespace App\Services;

use App\Core\Database;
use PDO;
use Exception;
use PDOException;

/**
 * Class ClassService
 * Handles group fitness class scheduling and member bookings.
 */
class ClassService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function createClass(array $data): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO class_sessions (name, instructor, schedule_date, schedule_time, capacity) 
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            trim($data['name']),
            trim($data['instructor']),
            $data['schedule_date'],
            $data['schedule_time'],
            (int)$data['capacity']
        ]);
    }

    /**
     * Gets all upcoming classes and counts how many spots are currently booked.
     */
    public function getUpcomingClasses(): array
    {
        $stmt = $this->db->query("
            SELECT 
                cs.*, 
                (SELECT COUNT(*) FROM class_bookings cb WHERE cb.class_session_id = cs.id) as booked_count
            FROM class_sessions cs
            WHERE cs.schedule_date >= CURRENT_DATE
            ORDER BY cs.schedule_date ASC, cs.schedule_time ASC
        ");
        return $stmt->fetchAll();
    }

    /**
     * Book a spot for a member, enforcing capacity and duplicate checks.
     */
    public function bookClass(int $classId, int $memberId): bool
    {
        // 1. Check Capacity
        $stmt = $this->db->prepare("
            SELECT capacity, 
                   (SELECT COUNT(*) FROM class_bookings WHERE class_session_id = ?) as current_bookings
            FROM class_sessions WHERE id = ?
        ");
        $stmt->execute([$classId, $classId]);
        $classData = $stmt->fetch();

        if (!$classData) {
            throw new Exception("Class not found.");
        }
        if ($classData['current_bookings'] >= $classData['capacity']) {
            throw new Exception("Sorry, this class is fully booked.");
        }

        // 2. Attempt Booking
        try {
            $insertStmt = $this->db->prepare("INSERT INTO class_bookings (class_session_id, member_id) VALUES (?, ?)");
            return $insertStmt->execute([$classId, $memberId]);
        } catch (PDOException $e) {
            // Error 1062 is standard MySQL for Duplicate Entry (violating our UNIQUE key)
            if ($e->errorInfo[1] === 1062) {
                throw new Exception("You are already booked for this class.");
            }
            throw $e;
        }
    }

    public function cancelBooking(int $classId, int $memberId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM class_bookings WHERE class_session_id = ? AND member_id = ?");
        return $stmt->execute([$classId, $memberId]);
    }

    /**
     * Helper for the UI to know which classes a specific member is already in.
     */
    public function getMemberBookedClassIds(int $memberId): array
    {
        $stmt = $this->db->prepare("SELECT class_session_id FROM class_bookings WHERE member_id = ?");
        $stmt->execute([$memberId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN); // Returns a flat array of IDs
    }
}