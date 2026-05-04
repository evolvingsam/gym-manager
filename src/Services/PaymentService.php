<?php

namespace App\Services;

use App\Core\Database;
use PDO;
use Exception;

/**
 * Class PaymentService
 * Manages financial transactions and revenue reporting.
 */
class PaymentService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Record a new payment against a subscription.
     */
    public function recordPayment(int $subscriptionId, int $memberId, float $amount, string $method): bool
    {
        if ($amount <= 0) {
            throw new Exception("Payment amount must be greater than zero.");
        }

        // Verify the subscription belongs to the member
        $stmt = $this->db->prepare("SELECT id FROM subscriptions WHERE id = ? AND member_id = ?");
        $stmt->execute([$subscriptionId, $memberId]);
        if (!$stmt->fetch()) {
            throw new Exception("Invalid subscription or member mismatch.");
        }

        $insertStmt = $this->db->prepare("
            INSERT INTO payments (subscription_id, member_id, amount, payment_method) 
            VALUES (?, ?, ?, ?)
        ");
        
        return $insertStmt->execute([$subscriptionId, $memberId, $amount, $method]);
    }

    /**
     * Get all unpaid or partially paid subscriptions.
     * If $memberId is provided, filters for that specific member.
     */
    public function getUnpaidSubscriptions(?int $memberId = null): array
    {
        // Using COALESCE to handle subscriptions with zero payments yet
        $query = "
            SELECT 
                s.id AS subscription_id, 
                s.start_date, 
                s.status,
                m.id AS member_id,
                m.full_name, 
                m.member_code,
                p.name AS plan_name, 
                p.price AS total_due,
                COALESCE(SUM(pay.amount), 0) AS total_paid,
                (p.price - COALESCE(SUM(pay.amount), 0)) AS balance_due
            FROM subscriptions s
            JOIN members m ON s.member_id = m.id
            JOIN plans p ON s.plan_id = p.id
            LEFT JOIN payments pay ON pay.subscription_id = s.id
            WHERE s.status != 'cancelled'
        ";

        $params = [];
        if ($memberId) {
            $query .= " AND s.member_id = ?";
            $params[] = $memberId;
        }

        $query .= " GROUP BY s.id HAVING balance_due > 0 ORDER BY s.start_date ASC";

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get payment history for a specific member.
     */
    public function getMemberPayments(int $memberId): array
    {
        $stmt = $this->db->prepare("
            SELECT pay.*, p.name AS plan_name 
            FROM payments pay
            JOIN subscriptions s ON pay.subscription_id = s.id
            JOIN plans p ON s.plan_id = p.id
            WHERE pay.member_id = ?
            ORDER BY pay.payment_date DESC
        ");
        $stmt->execute([$memberId]);
        return $stmt->fetchAll();
    }

    /**
     * Get global recent payments.
     */
    public function getRecentPayments(int $limit = 50): array
    {
        $stmt = $this->db->prepare("
            SELECT pay.*, m.full_name, p.name AS plan_name
            FROM payments pay
            JOIN members m ON pay.member_id = m.id
            JOIN subscriptions s ON pay.subscription_id = s.id
            JOIN plans p ON s.plan_id = p.id
            ORDER BY pay.payment_date DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Calculate total revenue grouped by month for a specific year.
     */
    public function getMonthlyRevenue(int $year): array
    {
        $stmt = $this->db->prepare("
            SELECT MONTH(payment_date) AS month, SUM(amount) AS total_revenue
            FROM payments
            WHERE YEAR(payment_date) = ?
            GROUP BY MONTH(payment_date)
            ORDER BY month ASC
        ");
        $stmt->execute([$year]);
        
        // Format to ensure all 12 months are represented even if 0
        $results = $stmt->fetchAll();
        $revenue = array_fill(1, 12, 0);
        foreach ($results as $row) {
            $revenue[(int)$row['month']] = (float)$row['total_revenue'];
        }
        return $revenue;
    }
}