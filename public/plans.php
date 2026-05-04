<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Session;
use App\Services\AuthService;
use App\Services\PlanService;

Session::start();
AuthService::requireRole('admin'); // Only admins should manage plans

$planService = new PlanService();
$error = '';
$success = Session::get('success_msg');
Session::set('success_msg', null);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        die("CSRF validation failed.");
    }

    try {
        if ($_POST['action'] === 'save') {
            $data = [
                'name' => $_POST['name'],
                'duration_months' => $_POST['duration_months'],
                'price' => $_POST['price'],
                'features' => $_POST['features']
            ];
            
            if (!empty($_POST['plan_id'])) {
                $planService->updatePlan((int)$_POST['plan_id'], $data);
                Session::set('success_msg', "Plan updated successfully.");
            } else {
                $planService->createPlan($data);
                Session::set('success_msg', "Plan created successfully.");
            }
        } elseif ($_POST['action'] === 'delete') {
            $planService->deletePlan((int)$_POST['plan_id']);
            Session::set('success_msg', "Plan deleted successfully.");
        }
        header("Location: plans.php");
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$plans = $planService->getAllPlans();
$csrfToken = Session::generateCsrfToken();
$pageTitle = "Manage Plans - Gym Manager";
require_once __DIR__ . '/../views/layout/header.php';
?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Membership Plans</h2>
        <div>
            <a href="dashboard.php" class="btn btn-secondary">Dashboard</a>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#planModal" onclick="resetForm()">Add Plan</button>
        </div>
    </div>

    <?php if ($success): ?> <div class="alert alert-success"><?= htmlspecialchars($success) ?></div> <?php endif; ?>
    <?php if ($error): ?> <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div> <?php endif; ?>

    <div class="row">
        <?php foreach ($plans as $plan): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-body">
                        <h4 class="card-title text-primary"><?= htmlspecialchars($plan['name']) ?></h4>
                        <h2 class="card-subtitle mb-3">₦<?= number_format($plan['price'], 2) ?></h2>
                        <p class="text-muted"><?= htmlspecialchars($plan['duration_months']) ?> Month(s)</p>
                        <p class="card-text"><?= nl2br(htmlspecialchars($plan['features'])) ?></p>
                    </div>
                    <div class="card-footer bg-white border-0 d-flex justify-content-between">
                        <button class="btn btn-sm btn-outline-primary" 
                                onclick='editPlan(<?= json_encode($plan) ?>)' 
                                data-bs-toggle="modal" data-bs-target="#planModal">Edit</button>
                        
                        <form method="POST" onsubmit="return confirm('Delete this plan?');">
                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="plan_id" value="<?= $plan['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Plan Modal -->
<div class="modal fade" id="planModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="plan_id" id="form_plan_id" value="">
            
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add Plan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label>Plan Name</label>
                    <input type="text" name="name" id="form_name" class="form-control" required>
                </div>
                <div class="row mb-3">
                    <div class="col-6">
                        <label>Duration (Months)</label>
                        <input type="number" name="duration_months" id="form_duration" class="form-control" min="1" required>
                    </div>
                    <div class="col-6">
                        <label>Price (₦)</label>
                        <input type="number" step="0.01" name="price" id="form_price" class="form-control" min="0" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label>Features (comma separated)</label>
                    <textarea name="features" id="form_features" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-success">Save Plan</button>
            </div>
        </form>
    </div>
</div>

<script>
// Minor Vanilla JS to handle Modal population (No business logic here, just UX)
function editPlan(plan) {
    document.getElementById('modalTitle').innerText = 'Edit Plan';
    document.getElementById('form_plan_id').value = plan.id;
    document.getElementById('form_name').value = plan.name;
    document.getElementById('form_duration').value = plan.duration_months;
    document.getElementById('form_price').value = plan.price;
    document.getElementById('form_features').value = plan.features;
}
function resetForm() {
    document.getElementById('modalTitle').innerText = 'Add Plan';
    document.getElementById('form_plan_id').value = '';
    document.getElementById('form_name').value = '';
    document.getElementById('form_duration').value = '';
    document.getElementById('form_price').value = '';
    document.getElementById('form_features').value = '';
}
</script>
<?php require_once __DIR__ . '/../views/layout/footer.php'; ?>