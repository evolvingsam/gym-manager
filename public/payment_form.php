<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Session;
use App\Core\Database;
use App\Services\AuthService;
use App\Services\PaymentService;
use App\Services\MemberService;

Session::start();
if (!AuthService::isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$subId = (int)($_GET['sub_id'] ?? 0);
$memberId = (int)($_GET['member_id'] ?? 0);

if (!$subId || !$memberId) die("Invalid parameters.");

$paymentService = new PaymentService();
$memberService = new MemberService();
$member = $memberService->getMemberById($memberId);

// Fetch exactly how much is owed for THIS subscription using a direct query for simplicity in the controller
$db = Database::getInstance();
$stmt = $db->prepare("
    SELECT p.name AS plan_name, p.price, COALESCE(SUM(pay.amount), 0) AS paid 
    FROM subscriptions s
    JOIN plans p ON s.plan_id = p.id
    LEFT JOIN payments pay ON pay.subscription_id = s.id
    WHERE s.id = ?
    GROUP BY s.id
");
$stmt->execute([$subId]);
$subData = $stmt->fetch();

if (!$subData) die("Subscription not found.");

$balanceDue = $subData['price'] - $subData['paid'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) die("CSRF validation failed.");

    try {
        $amount = (float)$_POST['amount'];
        $method = $_POST['payment_method'];

        if ($amount > $balanceDue) {
            throw new Exception("Payment amount cannot exceed the balance due (₦" . number_format($balanceDue, 2) . ").");
        }

        $paymentService->recordPayment($subId, $memberId, $amount, $method);
        Session::set('success_msg', "Payment of ₦" . number_format($amount, 2) . " recorded successfully.");
        
        // Redirect back to the member's payment history
        header("Location: member_payments.php?id=" . $memberId);
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$csrfToken = Session::generateCsrfToken();
$pageTitle = "Record Payment - Gym Manager";
require_once __DIR__ . '/../views/layout/header.php';
?>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Record Payment for <?= htmlspecialchars($member['full_name']) ?></h5>
                </div>
                <div class="card-body">
                    <?php if ($error): ?> <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div> <?php endif; ?>

                    <div class="mb-4 p-3 bg-light rounded border">
                        <strong>Plan:</strong> <?= htmlspecialchars($subData['plan_name']) ?><br>
                        <strong>Total Price:</strong> ₦<?= number_format($subData['price'], 2) ?><br>
                        <strong>Amount Paid:</strong> ₦<?= number_format($subData['paid'], 2) ?><br>
                        <h4 class="mt-2 text-danger">Balance Due: ₦<?= number_format($balanceDue, 2) ?></h4>
                    </div>

                    <?php if ($balanceDue <= 0): ?>
                        <div class="alert alert-success">This subscription is fully paid.</div>
                        <a href="payments.php" class="btn btn-secondary w-100">Return to Payments</a>
                    <?php else: ?>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Payment Amount (₦)</label>
                                <input type="number" step="0.01" name="amount" class="form-control form-control-lg" max="<?= $balanceDue ?>" value="<?= $balanceDue ?>" required autofocus>
                                <div class="form-text">Defaults to full balance due. You can process partial payments.</div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label">Payment Method</label>
                                <select name="payment_method" class="form-select" required>
                                    <option value="Cash">Cash</option>
                                    <option value="POS / Card">POS / Card</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                </select>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="payments.php" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-success px-4">Record Payment</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../views/layout/footer.php'; ?>