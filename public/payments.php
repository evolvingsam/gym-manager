<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Session;
use App\Services\AuthService;
use App\Services\PaymentService;

Session::start();
if (!AuthService::isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$paymentService = new PaymentService();
$currentYear = (int)date('Y');

$unpaidSubs = $paymentService->getUnpaidSubscriptions();
$recentPayments = $paymentService->getRecentPayments(15);
$monthlyRevenue = $paymentService->getMonthlyRevenue($currentYear);

// Calculate totals for quick metrics
$totalDebt = array_sum(array_column($unpaidSubs, 'balance_due'));
$ytdRevenue = array_sum($monthlyRevenue);
$pageTitle = "Payments & Financials - Gym Manager";
require_once __DIR__ . '/../views/layout/header.php';
?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Financial Overview (<?= $currentYear ?>)</h2>
        <a href="dashboard.php" class="btn btn-secondary">Dashboard</a>
    </div>

    <!-- KPI Cards -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">YTD Revenue</h5>
                    <h2 class="mb-0">₦<?= number_format($ytdRevenue, 2) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm bg-danger text-white">
                <div class="card-body">
                    <h5 class="card-title">Outstanding Dues (Gym-wide)</h5>
                    <h2 class="mb-0">₦<?= number_format($totalDebt, 2) ?></h2>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Unpaid Subscriptions (Debtors) -->
        <div class="col-md-7 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white fw-bold text-danger">Action Required: Unpaid Memberships</div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Member</th>
                                <th>Plan</th>
                                <th>Owes</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($unpaidSubs)): ?>
                                <tr><td colspan="4" class="text-center py-4">All memberships are fully paid!</td></tr>
                            <?php endif; ?>
                            <?php foreach ($unpaidSubs as $debt): ?>
                                <tr>
                                    <td>
                                        <?= htmlspecialchars($debt['full_name']) ?><br>
                                        <small class="text-muted"><?= htmlspecialchars($debt['member_code']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($debt['plan_name']) ?></td>
                                    <td class="text-danger fw-bold">₦<?= number_format($debt['balance_due'], 2) ?></td>
                                    <td>
                                        <a href="payment_form.php?sub_id=<?= $debt['subscription_id'] ?>&member_id=<?= $debt['member_id'] ?>" class="btn btn-sm btn-success">Pay Now</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="col-md-5 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white fw-bold">Recent Transactions</div>
                <div class="card-body p-0">
                    <table class="table table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Member</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentPayments)): ?>
                                <tr><td colspan="3" class="text-center py-3">No payments recorded.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($recentPayments as $pay): ?>
                                <tr>
                                    <td><?= date('M j', strtotime($pay['payment_date'])) ?></td>
                                    <td><?= htmlspecialchars($pay['full_name']) ?></td>
                                    <td class="text-success">₦<?= number_format($pay['amount'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../views/layout/footer.php'; ?>