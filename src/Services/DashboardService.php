<?php

namespace App\Services;

use App\Core\Database;
use PDO;

/**
 * Class DashboardService
 * Read-only aggregator for dashboard KPIs, feeds, and alerts.
 */
class DashboardService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get the four core KPIs for the admin view.
     */
    public function getKPIs(): array
    {
        $kpis = [];

        // 1. Total Active Members
        $stmt = $this->db->query("SELECT COUNT(id) FROM members WHERE deleted_at IS NULL");
        $kpis['total_members'] = (int)$stmt->fetchColumn();

        // 2. Active Subscriptions
        $stmt = $this->db->query("SELECT COUNT(id) FROM subscriptions WHERE status = 'active'");
        $kpis['active_subscriptions'] = (int)$stmt->fetchColumn();

        // 3. Today's Check-ins
        $stmt = $this->db->query("SELECT COUNT(id) FROM attendance WHERE DATE(check_in_time) = CURRENT_DATE");
        $kpis['today_checkins'] = (int)$stmt->fetchColumn();

        // 4. Monthly Revenue
        $stmt = $this->db->query("
            SELECT COALESCE(SUM(amount), 0) 
            FROM payments 
            WHERE YEAR(payment_date) = YEAR(CURRENT_DATE) AND MONTH(payment_date) = MONTH(CURRENT_DATE)
        ");
        $kpis['monthly_revenue'] = (float)$stmt->fetchColumn();

        return $kpis;
    }

    /**
     * Get subscriptions expiring in the next X days.
     */
    public function getExpiringSubscriptions(int $days = 7): array
    {
        // We ensure end_date is between tomorrow and the target day to filter out already expired ones
        $stmt = $this->db->prepare("
            SELECT s.id AS subscription_id, s.end_date, m.id AS member_id, m.full_name, m.member_code, p.name AS plan_name
            FROM subscriptions s
            JOIN members m ON s.member_id = m.id
            JOIN plans p ON s.plan_id = p.id
            WHERE s.status = 'active' 
              AND s.end_date BETWEEN CURRENT_DATE AND DATE_ADD(CURRENT_DATE, INTERVAL ? DAY)
            ORDER BY s.end_date ASC
        ");
        $stmt->execute([$days]);
        return $stmt->fetchAll();
    }

    /**
     * Re-uses the logic from PaymentService but optimized for a lightweight notification view.
     */
    public function getOverduePaymentsSummary(): array
    {
        $query = "
            SELECT 
                m.id AS member_id,
                m.full_name, 
                (p.price - COALESCE(SUM(pay.amount), 0)) AS balance_due
            FROM subscriptions s
            JOIN members m ON s.member_id = m.id
            JOIN plans p ON s.plan_id = p.id
            LEFT JOIN payments pay ON pay.subscription_id = s.id
            WHERE s.status != 'cancelled'
            GROUP BY s.id 
            HAVING balance_due > 0 
            ORDER BY balance_due DESC
        ";

        $stmt = $this->db->query($query);
        return $stmt->fetchAll();
    }

    /**
     * Unified timeline of recent events using SQL UNION.
     */
    public function getRecentActivity(int $limit = 10): array
    {
        $query = "
            (SELECT 'attendance' AS type, a.check_in_time AS activity_date, m.full_name, 'Checked in at front desk' AS description
             FROM attendance a JOIN members m ON a.member_id = m.id
             ORDER BY a.check_in_time DESC LIMIT 10)
            UNION ALL
            (SELECT 'payment' AS type, p.payment_date AS activity_date, m.full_name, CONCAT('Paid ₦', p.amount) AS description
             FROM payments p JOIN members m ON p.member_id = m.id
             ORDER BY p.payment_date DESC LIMIT 10)
            UNION ALL
            (SELECT 'member' AS type, created_at AS activity_date, full_name, 'Joined the gym' AS description
             FROM members
             ORDER BY created_at DESC LIMIT 10)
            ORDER BY activity_date DESC LIMIT ?
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}