<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Session;
use App\Services\AuthService;
use App\Services\DashboardService;
use App\Services\SubscriptionService;

Session::start();
if (!AuthService::isLoggedIn()) {
    header("Location: login.php");
    exit;
}

// Ensure lazy-expiration runs on dashboard load to keep data accurate
$subscriptionService = new SubscriptionService();
$subscriptionService->enforceStatuses();

$dashboardService = new DashboardService();
$kpis = $dashboardService->getKPIs();
$expiringSubs = $dashboardService->getExpiringSubscriptions(7);
$overduePayments = $dashboardService->getOverduePaymentsSummary();
$recentActivity = $dashboardService->getRecentActivity(12);

$userName = Session::get('full_name');
$userRole = Session::get('role');
$pageTitle = "Dashboard - Gym Manager";
require_once __DIR__ . '/../views/layout/header.php';
?>

<div class="container">
    
    <!-- Quick Action Menu -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body d-flex gap-2 flex-wrap">
                    <a href="attendance.php" class="btn btn-primary"><i class="bi bi-person-check"></i> Front Desk Check-in</a>
                    <a href="members.php" class="btn btn-outline-primary"><i class="bi bi-people"></i> Manage Members</a>
                    <a href="payments.php" class="btn btn-outline-success"><i class="bi bi-wallet2"></i> Financials</a>
                    <?php if ($userRole === 'admin'): ?>
                        <a href="plans.php" class="btn btn-outline-info"><i class="bi bi-card-list"></i> Manage Plans</a>
                        <a href="admin_classes.php" class="btn btn-outline-warning"><i class="bi bi-calendar-event"></i> Schedule Classes</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Notifications Area -->
    <div class="row mb-3">
        <div class="col-12">
            <?php if (!empty($expiringSubs)): ?>
                <div class="alert alert-warning alert-dismissible fade show shadow-sm" role="alert">
                    <strong><i class="bi bi-exclamation-triangle-fill"></i> Expiring Soon!</strong> 
                    You have <?= count($expiringSubs) ?> membership(s) expiring in the next 7 days.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    <hr>
                    <ul class="mb-0">
                        <?php foreach ($expiringSubs as $sub): ?>
                            <li>
                                <?= htmlspecialchars($sub['full_name']) ?> (<?= htmlspecialchars($sub['plan_name']) ?>) - 
                                Expires on <strong><?= date('M j, Y', strtotime($sub['end_date'])) ?></strong>
                                <a href="member_subscriptions.php?id=<?= $sub['member_id'] ?>" class="alert-link text-decoration-none ms-2">View</a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!empty($overduePayments)): ?>
                <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                    <strong><i class="bi bi-cash-coin"></i> Action Required!</strong> 
                    There are <?= count($overduePayments) ?> members with outstanding balances.
                    <a href="payments.php" class="alert-link ms-2">Resolve Debts</a>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- KPI Widgets -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white h-100 shadow-sm border-0">
                <div class="card-body">
                    <h6 class="card-title text-uppercase text-white-50 fw-bold">Total Members</h6>
                    <h1 class="display-5 fw-bold mb-0"><?= number_format($kpis['total_members']) ?></h1>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white h-100 shadow-sm border-0">
                <div class="card-body">
                    <h6 class="card-title text-uppercase text-white-50 fw-bold">Active Subscriptions</h6>
                    <h1 class="display-5 fw-bold mb-0"><?= number_format($kpis['active_subscriptions']) ?></h1>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white h-100 shadow-sm border-0">
                <div class="card-body">
                    <h6 class="card-title text-uppercase text-white-50 fw-bold">Today's Check-ins</h6>
                    <h1 class="display-5 fw-bold mb-0"><?= number_format($kpis['today_checkins']) ?></h1>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-dark text-white h-100 shadow-sm border-0">
                <div class="card-body">
                    <h6 class="card-title text-uppercase text-white-50 fw-bold">Revenue (This Month)</h6>
                    <h2 class="fw-bold mt-2">₦<?= number_format($kpis['monthly_revenue'], 2) ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity Feed -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-bottom border-light fw-bold py-3">
                    <i class="bi bi-clock-history"></i> Recent Activity Log
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php if (empty($recentActivity)): ?>
                            <li class="list-group-item text-center py-4 text-muted">No recent activity found.</li>
                        <?php endif; ?>
                        
                        <?php foreach ($recentActivity as $activity): ?>
                            <?php 
                                // Determine icon based on activity type
                                $icon = match($activity['type']) {
                                    'attendance' => '<i class="bi bi-check-circle-fill text-success"></i>',
                                    'payment' => '<i class="bi bi-cash-stack text-primary"></i>',
                                    'member' => '<i class="bi bi-person-plus-fill text-info"></i>',
                                    default => '<i class="bi bi-info-circle text-secondary"></i>'
                                };
                            ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                                <div>
                                    <span class="me-2 fs-5"><?= $icon ?></span>
                                    <strong><?= htmlspecialchars($activity['full_name']) ?></strong> 
                                    <span class="text-muted ms-1"><?= htmlspecialchars($activity['description']) ?></span>
                                </div>
                                <span class="badge bg-light text-dark border">
                                    <?= date('M j, h:i A', strtotime($activity['activity_date'])) ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../views/layout/footer.php'; ?>