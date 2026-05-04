<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Session;
use App\Services\AuthService;
use App\Services\MemberService;
use App\Services\PaymentService;

Session::start();
if (!AuthService::isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$memberId = (int)($_GET['id'] ?? 0);
if (!$memberId) die("Member ID required.");

$memberService = new MemberService();
$paymentService = new PaymentService();

$member = $memberService->getMemberById($memberId);
if (!$member) die("Member not found.");

$success = Session::get('success_msg');
Session::set('success_msg', null);

$paymentHistory = $paymentService->getMemberPayments($memberId);
$unpaidSubs = $paymentService->getUnpaidSubscriptions($memberId);
$pageTitle = "Billing: " . htmlspecialchars($member['full_name']) . " - Gym Manager";
require_once __DIR__ . '/../views/layout/header.php';
?>
<div class="container mt-4">
    <div class="mb-4 d-flex justify-content-between align-items-center">
        <h2>Billing Profile: <?= htmlspecialchars($member['full_name']) ?></h2>
        <a href="members.php" class="btn btn-secondary">Back to Members</a>
    </div>

    <?php if ($success): ?> <div class="alert alert-success"><?= htmlspecialchars($success) ?></div> <?php endif; ?>

    <div class="row">
        <!-- Outstanding Balances -->
        <div class="col-md-12 mb-4">
            <div class="card shadow-sm border-warning">
                <div class="card-header bg-warning fw-bold">Outstanding Balances</div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Plan</th>
                                <th>Start Date</th>
                                <th>Total Price</th>
                                <th>Balance Due</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($unpaidSubs)): ?>
                                <tr><td colspan="5" class="text-center py-3 text-success fw-bold">No outstanding balances!</td></tr>
                            <?php endif; ?>
                            <?php foreach ($unpaidSubs as $sub): ?>
                                <tr>
                                    <td><?= htmlspecialchars($sub['plan_name']) ?></td>
                                    <td><?= htmlspecialchars($sub['start_date']) ?></td>
                                    <td>₦<?= number_format($sub['total_due'], 2) ?></td>
                                    <td class="text-danger fw-bold">₦<?= number_format($sub['balance_due'], 2) ?></td>
                                    <td>
                                        <a href="payment_form.php?sub_id=<?= $sub['subscription_id'] ?>&member_id=<?= $memberId ?>" class="btn btn-sm btn-success">Pay Now</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Payment History -->
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-bold">Payment History</div>
                <div class="card-body p-0">
                    <table class="table table-striped mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Date & Time</th>
                                <th>Plan Reference</th>
                                <th>Method</th>
                                <th>Amount Paid</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($paymentHistory)): ?>
                                <tr><td colspan="4" class="text-center py-3">No payments recorded.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($paymentHistory as $pay): ?>
                                <tr>
                                    <td><?= date('M j, Y - h:i A', strtotime($pay['payment_date'])) ?></td>
                                    <td><?= htmlspecialchars($pay['plan_name']) ?></td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($pay['payment_method']) ?></span></td>
                                    <td class="text-success fw-bold">₦<?= number_format($pay['amount'], 2) ?></td>
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