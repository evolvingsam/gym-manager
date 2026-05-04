<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Session;
use App\Services\AuthService;
use App\Services\FitnessService;

Session::start();
if (!AuthService::isLoggedIn() || Session::get('role') !== 'member') {
    header("Location: login.php");
    exit;
}

$memberId = Session::get('user_id');
$fitnessService = new FitnessService();

$error = '';
$success = Session::get('success_msg');
Session::set('success_msg', null);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) die("CSRF validation failed.");

    try {
        if ($_POST['action'] === 'log_workout') {
            $fitnessService->logWorkout($memberId, $_POST);
            Session::set('success_msg', "Workout logged successfully! Keep it up.");
        } elseif ($_POST['action'] === 'log_metric') {
            $fitnessService->logBodyMetric($memberId, $_POST);
            Session::set('success_msg', "Body metrics updated.");
        } elseif ($_POST['action'] === 'delete_workout') {
            $fitnessService->deleteWorkoutLog((int)$_POST['log_id'], $memberId);
            Session::set('success_msg', "Workout entry removed.");
        }
        header("Location: fitness_tracker.php");
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$workouts = $fitnessService->getWorkoutHistory($memberId);
$metrics = $fitnessService->getMetricHistory($memberId);
$csrfToken = Session::generateCsrfToken();

$pageTitle = "Fitness Tracker - Gym Manager";
require_once __DIR__ . '/../views/layout/header.php';
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-activity"></i> Fitness Tracker</h2>
        <a href="my_profile.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <?php if ($success): ?> <div class="alert alert-success"><i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?></div> <?php endif; ?>
    <?php if ($error): ?> <div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?></div> <?php endif; ?>

    <div class="row">
        <!-- WORKOUT LOGGING COLUMN -->
        <div class="col-md-8 mb-4">
            
            <!-- Log Workout Form -->
            <div class="card shadow-sm border-primary mb-4">
                <div class="card-header bg-primary text-white fw-bold">Log an Exercise</div>
                <div class="card-body">
                    <form method="POST" class="row g-3 align-items-end">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                        <input type="hidden" name="action" value="log_workout">
                        
                        <div class="col-md-3">
                            <label class="form-label">Date</label>
                            <input type="date" name="log_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Exercise (e.g., Squat)</label>
                            <input type="text" name="exercise_name" class="form-control" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Sets</label>
                            <input type="number" name="sets" class="form-control" min="1" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Reps</label>
                            <input type="number" name="reps" class="form-control" min="1" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Weight (kg)</label>
                            <input type="number" step="0.5" name="weight_kg" class="form-control" min="0" required>
                        </div>
                        <div class="col-12 mt-3 text-end">
                            <button type="submit" class="btn btn-success"><i class="bi bi-plus-lg"></i> Add to Log</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Workout History -->
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-bold">Recent Workouts</div>
                <div class="card-body p-0">
                    <table class="table table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Exercise</th>
                                <th>Volume</th>
                                <th>Weight</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($workouts)): ?>
                                <tr><td colspan="5" class="text-center py-4 text-muted">No workouts logged yet. Time to hit the gym!</td></tr>
                            <?php endif; ?>
                            <?php foreach ($workouts as $log): ?>
                                <tr>
                                    <td><?= date('M j', strtotime($log['log_date'])) ?></td>
                                    <td class="fw-bold"><?= htmlspecialchars($log['exercise_name']) ?></td>
                                    <td><?= $log['sets'] ?> sets x <?= $log['reps'] ?> reps</td>
                                    <td><span class="badge bg-secondary"><?= $log['weight_kg'] ?> kg</span></td>
                                    <td class="text-end">
                                        <form method="POST" onsubmit="return confirm('Delete this entry?');" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                            <input type="hidden" name="action" value="delete_workout">
                                            <input type="hidden" name="log_id" value="<?= $log['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- BODY METRICS COLUMN -->
        <div class="col-md-4 mb-4">
            
            <!-- Log Metric Form -->
            <div class="card shadow-sm border-info mb-4">
                <div class="card-header bg-info text-white fw-bold">Record Body Metrics</div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                        <input type="hidden" name="action" value="log_metric">
                        
                        <div class="mb-3">
                            <label>Date</label>
                            <input type="date" name="recorded_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label>Body Weight (kg)</label>
                            <input type="number" step="0.1" name="weight_kg" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Body Fat % (Optional)</label>
                            <input type="number" step="0.1" name="body_fat_percent" class="form-control">
                        </div>
                        <button type="submit" class="btn btn-info w-100 text-white">Save Progress</button>
                    </form>
                </div>
            </div>

            <!-- Metric History -->
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-bold">Weight History</div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php if (empty($metrics)): ?>
                            <li class="list-group-item text-center py-3 text-muted">No metrics recorded.</li>
                        <?php endif; ?>
                        <?php foreach ($metrics as $metric): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><?= date('M j, Y', strtotime($metric['recorded_date'])) ?></span>
                                <strong><?= $metric['weight_kg'] ?> kg</strong>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../views/layout/footer.php'; ?>