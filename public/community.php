<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Session;
use App\Services\AuthService;
use App\Services\GamificationService;

Session::start();
if (!AuthService::isLoggedIn() || Session::get('role') !== 'member') {
    header("Location: login.php");
    exit;
}

$gamificationService = new GamificationService();

// Fetch Top 5 for each category
$topAttendees = $gamificationService->getTopAttendees(5);
$heavyLifters = $gamificationService->getHeavyLifters(5);

$currentMonthName = date('F');

$pageTitle = "Community Hub - Gym Manager";
require_once __DIR__ . '/../views/layout/header.php';
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-trophy text-warning"></i> Community Hub</h2>
        <a href="my_profile.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <div class="alert alert-info shadow-sm text-center mb-5">
        <strong>Leaderboards reset every month!</strong> Current standings for <strong><?= $currentMonthName ?></strong>.
    </div>

    <div class="row">
        <!-- ATTENDANCE LEADERBOARD -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm border-0 border-top border-warning border-4">
                <div class="card-header bg-white fw-bold py-3">
                    <i class="bi bi-fire text-danger"></i> The Iron Addicts (Most Visits)
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php if (empty($topAttendees)): ?>
                            <li class="list-group-item text-center py-4 text-muted">No visits recorded this month yet.</li>
                        <?php endif; ?>
                        
                        <?php foreach ($topAttendees as $index => $user): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                                <div class="d-flex align-items-center">
                                    <span class="fs-4 fw-bold text-muted me-3" style="width: 25px;">#<?= $index + 1 ?></span>
                                    <?php if ($user['photo_path']): ?>
                                        <img src="<?= htmlspecialchars($user['photo_path']) ?>" class="rounded-circle object-fit-cover me-3 shadow-sm" width="40" height="40" alt="Avatar">
                                    <?php else: ?>
                                        <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center text-white me-3 shadow-sm" style="width: 40px; height: 40px;"><i class="bi bi-person"></i></div>
                                    <?php endif; ?>
                                    <strong class="fs-5"><?= htmlspecialchars($user['full_name']) ?></strong>
                                </div>
                                <span class="badge bg-warning text-dark rounded-pill fs-6"><?= $user['total_visits'] ?> visits</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>

        <!-- VOLUME LEADERBOARD -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm border-0 border-top border-success border-4">
                <div class="card-header bg-white fw-bold py-3">
                    <i class="bi bi-bar-chart-line-fill text-success"></i> Heavy Lifters (Total Volume)
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php if (empty($heavyLifters)): ?>
                            <li class="list-group-item text-center py-4 text-muted">No workouts logged this month yet.</li>
                        <?php endif; ?>
                        
                        <?php foreach ($heavyLifters as $index => $user): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                                <div class="d-flex align-items-center">
                                    <span class="fs-4 fw-bold text-muted me-3" style="width: 25px;">#<?= $index + 1 ?></span>
                                    <?php if ($user['photo_path']): ?>
                                        <img src="<?= htmlspecialchars($user['photo_path']) ?>" class="rounded-circle object-fit-cover me-3 shadow-sm" width="40" height="40" alt="Avatar">
                                    <?php else: ?>
                                        <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center text-white me-3 shadow-sm" style="width: 40px; height: 40px;"><i class="bi bi-person"></i></div>
                                    <?php endif; ?>
                                    <strong class="fs-5"><?= htmlspecialchars($user['full_name']) ?></strong>
                                </div>
                                <span class="badge bg-success rounded-pill fs-6"><?= number_format($user['total_volume_kg']) ?> kg</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../views/layout/footer.php'; ?>