<?php

namespace App\Services;

use App\Core\Database;
use PDO;
use Exception;
use DateTime;

/**
 * Class SubscriptionService
 * Manages assigning plans to members and tracking subscription statuses.
 */
class SubscriptionService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Updates expired subscriptions lazily.
     */
    public function enforceStatuses(): void
    {
        // Transition active subscriptions to expired if the end date has passed.
        $this->db->exec("
            UPDATE subscriptions 
            SET status = 'expired' 
            WHERE status = 'active' AND end_date < CURRENT_DATE
        ");
    }

    /**
     * Fetch subscriptions for a specific member.
     */
    public function getMemberSubscriptions(int $memberId): array
    {
        $this->enforceStatuses();

        $stmt = $this->db->prepare("
            SELECT s.*, p.name AS plan_name, p.price 
            FROM subscriptions s
            JOIN plans p ON s.plan_id = p.id
            WHERE s.member_id = ?
            ORDER BY s.start_date DESC
        ");
        $stmt->execute([$memberId]);
        return $stmt->fetchAll();
    }

    /**
     * Assign a new plan to a member.
     */
    public function assignPlan(int $memberId, int $planId): bool
    {
        // 1. Fetch Plan to get duration
        $planStmt = $this->db->prepare("SELECT duration_months FROM plans WHERE id = ?");
        $planStmt->execute([$planId]);
        $plan = $planStmt->fetch();

        if (!$plan) {
            throw new Exception("Invalid plan selected.");
        }

        // 2. Prevent multiple active overlapping subscriptions (Business Rule)
        $activeStmt = $this->db->prepare("
            SELECT id FROM subscriptions 
            WHERE member_id = ? AND status = 'active' AND end_date >= CURRENT_DATE
        ");
        $activeStmt->execute([$memberId]);
        if ($activeStmt->fetch()) {
            throw new Exception("Member already has an active subscription. Cancel it before assigning a new one.");
        }

        // 3. Compute Dates
        $startDate = new DateTime(); // Today
        $endDate = clone $startDate;
        $endDate->modify("+{$plan['duration_months']} months");

        // 4. Insert Subscription
        $stmt = $this->db->prepare("
            INSERT INTO subscriptions (member_id, plan_id, start_date, end_date, status) 
            VALUES (?, ?, ?, ?, 'active')
        ");

        return $stmt->execute([
            $memberId,
            $planId,
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d')
        ]);
    }

    /**
     * Manually cancel a subscription.
     */
    public function cancelSubscription(int $subscriptionId): bool
    {
        $stmt = $this->db->prepare("UPDATE subscriptions SET status = 'cancelled' WHERE id = ?");
        return $stmt->execute([$subscriptionId]);
    }
}