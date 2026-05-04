<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Session;
use App\Services\AuthService;
use App\Services\SubscriptionService;
use App\Services\PaymentService;
use App\Services\AttendanceService;

Session::start();
if (!AuthService::isLoggedIn() || Session::get('role') !== 'member') {
    header("Location: login.php");
    exit;
}

$memberId = Session::get('user_id');

$subService = new SubscriptionService();
$paymentService = new PaymentService();
$attendanceService = new AttendanceService();

$subscriptions = $subService->getMemberSubscriptions($memberId);
$unpaidSubs = $paymentService->getUnpaidSubscriptions($memberId);
$recentAttendance = $attendanceService->getMemberHistory($memberId, 5);

$totalDebt = array_sum(array_column($unpaidSubs, 'balance_due'));
$pageTitle = "My Profile - Gym Manager";
require_once __DIR__ . '/../views/layout/header.php';
?>


<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Welcome back, <?= htmlspecialchars(Session::get('full_name')) ?>!</h2>
        <div class="d-flex flex-wrap gap-2">
            <a href="edit_profile.php" class="btn btn-outline-secondary"><i class="bi bi-gear"></i> Settings</a>
            <a href="browse_plans.php" class="btn btn-success"><i class="bi bi-plus-circle"></i> New Plan</a>
            <a href="book_class.php" class="btn btn-primary"><i class="bi bi-calendar-plus"></i> Book Class</a>
            <a href="fitness_tracker.php" class="btn btn-dark"><i class="bi bi-activity"></i> Log Workout</a>
            <a href="community.php" class="btn btn-warning"><i class="bi bi-trophy"></i> Community</a>
        </div>
    </div>

    <?php if ($totalDebt > 0): ?>
        <div class="alert alert-danger shadow-sm">
            <strong><i class="bi bi-exclamation-circle"></i> Outstanding Balance:</strong> You currently owe ₦<?= number_format($totalDebt, 2) ?>. Please visit the front desk to settle your account.
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Subscriptions -->
        <div class="col-md-8 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white fw-bold"><i class="bi bi-card-checklist"></i> My Memberships</div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light"><tr><th>Plan</th><th>Start</th><th>Expires</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php if(empty($subscriptions)): ?><tr><td colspan="4" class="text-center py-3">No active plans. See front desk to subscribe.</td></tr><?php endif; ?>
                            <?php foreach($subscriptions as $sub): ?>
                                <tr>
                                    <td><?= htmlspecialchars($sub['plan_name']) ?></td>
                                    <td><?= date('M j, Y', strtotime($sub['start_date'])) ?></td>
                                    <td><?= date('M j, Y', strtotime($sub['end_date'])) ?></td>
                                    <td><span class="badge bg-<?= $sub['status'] === 'active' ? 'success' : 'secondary' ?>"><?= ucfirst($sub['status']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Recent Check-ins -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white fw-bold"><i class="bi bi-clock-history"></i> Recent Visits</div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php if(empty($recentAttendance)): ?><li class="list-group-item text-center py-3">No visits yet.</li><?php endif; ?>
                        <?php foreach($recentAttendance as $log): ?>
                            <li class="list-group-item d-flex justify-content-between">
                                <span><?= date('D, M j', strtotime($log['check_in_time'])) ?></span>
                                <span class="text-muted"><?= date('h:i A', strtotime($log['check_in_time'])) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../views/layout/footer.php'; ?>