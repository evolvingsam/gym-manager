<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Session;
use App\Services\AuthService;
use App\Services\MemberService;
use App\Services\PlanService;
use App\Services\SubscriptionService;

Session::start();
if (!AuthService::isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$memberId = (int)($_GET['id'] ?? 0);
if (!$memberId) die("Member ID required.");

$memberService = new MemberService();
$planService = new PlanService();
$subService = new SubscriptionService();

$member = $memberService->getMemberById($memberId);
if (!$member) die("Member not found.");

$error = '';
$success = Session::get('success_msg');
Session::set('success_msg', null);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) die("CSRF validation failed.");

    try {
        if ($_POST['action'] === 'assign') {
            $subService->assignPlan($memberId, (int)$_POST['plan_id']);
            Session::set('success_msg', "Plan successfully assigned.");
        } elseif ($_POST['action'] === 'cancel') {
            $subService->cancelSubscription((int)$_POST['subscription_id']);
            Session::set('success_msg', "Subscription cancelled.");
        }
        header("Location: member_subscriptions.php?id=" . $memberId);
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$subscriptions = $subService->getMemberSubscriptions($memberId);
$availablePlans = $planService->getAllPlans();
$csrfToken = Session::generateCsrfToken();
$pageTitle = "Subscriptions: " . htmlspecialchars($member['full_name']) . " - Gym Manager";
require_once __DIR__ . '/../views/layout/header.php';
?>
<div class="container mt-4">
    <div class="mb-4 d-flex justify-content-between align-items-center">
        <h2><?= htmlspecialchars($member['full_name']) ?>'s Subscriptions</h2>
        <a href="members.php" class="btn btn-secondary">Back to Members</a>
    </div>

    <?php if ($success): ?> <div class="alert alert-success"><?= htmlspecialchars($success) ?></div> <?php endif; ?>
    <?php if ($error): ?> <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div> <?php endif; ?>

    <div class="row">
        <!-- Assign New Plan Form -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">Assign New Plan</div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                        <input type="hidden" name="action" value="assign">
                        
                        <div class="mb-3">
                            <label class="form-label">Select Plan</label>
                            <select name="plan_id" class="form-select" required>
                                <option value="">-- Choose Plan --</option>
                                <?php foreach ($availablePlans as $plan): ?>
                                    <option value="<?= $plan['id'] ?>">
                                        <?= htmlspecialchars($plan['name']) ?> (₦<?= number_format($plan['price']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-success w-100">Assign Plan</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Subscription History Table -->
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Plan</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($subscriptions)): ?>
                                <tr><td colspan="5" class="text-center py-3">No subscriptions found.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($subscriptions as $sub): ?>
                                <tr>
                                    <td><?= htmlspecialchars($sub['plan_name']) ?></td>
                                    <td><?= htmlspecialchars($sub['start_date']) ?></td>
                                    <td><?= htmlspecialchars($sub['end_date']) ?></td>
                                    <td>
                                        <?php
                                            $badgeClass = match($sub['status']) {
                                                'active' => 'bg-success',
                                                'expired' => 'bg-danger',
                                                'cancelled' => 'bg-warning text-dark',
                                                default => 'bg-secondary'
                                            };
                                        ?>
                                        <span class="badge <?= $badgeClass ?>"><?= ucfirst($sub['status']) ?></span>
                                    </td>
                                    <td>
                                        <?php if ($sub['status'] === 'active'): ?>
                                            <form method="POST" onsubmit="return confirm('Are you sure you want to cancel this active subscription?');">
                                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                                <input type="hidden" name="action" value="cancel">
                                                <input type="hidden" name="subscription_id" value="<?= $sub['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-warning">Cancel</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
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