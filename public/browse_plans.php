<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Session;
use App\Services\AuthService;
use App\Services\PlanService;
use App\Services\SubscriptionService;

Session::start();
if (!AuthService::isLoggedIn() || Session::get('role') !== 'member') {
    header("Location: login.php");
    exit;
}

$memberId = Session::get('user_id');
$planService = new PlanService();
$subService = new SubscriptionService();

$error = '';
$success = Session::get('success_msg');
Session::set('success_msg', null);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) die("CSRF validation failed.");

    try {
        $planId = (int)$_POST['plan_id'];
        $subService->assignPlan($memberId, $planId);
        
        Session::set('success_msg', "Plan successfully added! Please visit the front desk to complete payment and activate your benefits.");
        header("Location: my_profile.php");
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$plans = $planService->getAllPlans();
$csrfToken = Session::generateCsrfToken();
$pageTitle = "Browse Plans - Gym Manager";
require_once __DIR__ . '/../views/layout/header.php';
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Available Memberships</h2>
        <a href="my_profile.php" class="btn btn-secondary">Back to Profile</a>
    </div>

    <?php if ($error): ?> <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div> <?php endif; ?>

    <div class="row">
        <?php foreach ($plans as $plan): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-body text-center p-4">
                        <h4 class="card-title fw-bold text-primary mb-3"><?= htmlspecialchars($plan['name']) ?></h4>
                        <h2 class="card-subtitle mb-2">₦<?= number_format($plan['price']) ?></h2>
                        <p class="text-muted mb-4"><?= htmlspecialchars($plan['duration_months']) ?> Month(s) Access</p>
                        
                        <div class="text-start mb-4 px-3">
                            <p class="card-text"><i class="bi bi-check-circle-fill text-success me-2"></i> <?= str_replace("\n", '<br><i class="bi bi-check-circle-fill text-success me-2"></i> ', htmlspecialchars($plan['features'])) ?></p>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-0 p-3">
                        <form method="POST" onsubmit="return confirm('Add this plan to your account? You will need to pay at the front desk.');">
                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                            <input type="hidden" name="plan_id" value="<?= $plan['id'] ?>">
                            <button type="submit" class="btn btn-primary w-100 fw-bold">Select Plan</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../views/layout/footer.php'; ?>