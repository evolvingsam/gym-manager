<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Session;
use App\Services\AuthService;
use App\Services\ClassService;

Session::start();
AuthService::requireRole('admin');

$classService = new ClassService();
$error = '';
$success = Session::get('success_msg');
Session::set('success_msg', null);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) die("CSRF validation failed.");

    try {
        $classService->createClass($_POST);
        Session::set('success_msg', "Class scheduled successfully.");
        header("Location: admin_classes.php");
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$upcomingClasses = $classService->getUpcomingClasses();
$csrfToken = Session::generateCsrfToken();

// UI Layout
$pageTitle = "Manage Classes - Gym Manager";
require_once __DIR__ . '/../views/layout/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-calendar-event"></i> Manage Classes</h2>
        <a href="dashboard.php" class="btn btn-secondary">Dashboard</a>
    </div>

    <?php if ($success): ?> <div class="alert alert-success"><?= htmlspecialchars($success) ?></div> <?php endif; ?>
    <?php if ($error): ?> <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div> <?php endif; ?>

    <div class="row">
        <!-- Create Class Form -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm border-primary">
                <div class="card-header bg-primary text-white">Schedule New Class</div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                        <div class="mb-3">
                            <label>Class Name (e.g. Vinyasa Yoga)</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Instructor Name</label>
                            <input type="text" name="instructor" class="form-control" required>
                        </div>
                        <div class="row mb-3">
                            <div class="col-6">
                                <label>Date</label>
                                <input type="date" name="schedule_date" class="form-control" min="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-6">
                                <label>Time</label>
                                <input type="time" name="schedule_time" class="form-control" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label>Capacity (Max Participants)</label>
                            <input type="number" name="capacity" class="form-control" min="1" value="20" required>
                        </div>
                        <button type="submit" class="btn btn-success w-100">Schedule Class</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Class List -->
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-bold">Upcoming Schedule</div>
                <div class="card-body p-0">
                    <table class="table table-striped mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Class Info</th>
                                <th>When</th>
                                <th>Capacity</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($upcomingClasses)): ?>
                                <tr><td colspan="3" class="text-center py-4">No upcoming classes scheduled.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($upcomingClasses as $cls): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($cls['name']) ?></strong><br>
                                        <small class="text-muted">with <?= htmlspecialchars($cls['instructor']) ?></small>
                                    </td>
                                    <td>
                                        <?= date('M j, Y', strtotime($cls['schedule_date'])) ?><br>
                                        <span class="badge bg-secondary"><?= date('h:i A', strtotime($cls['schedule_time'])) ?></span>
                                    </td>
                                    <td>
                                        <?php 
                                            $badgeClass = ($cls['booked_count'] >= $cls['capacity']) ? 'bg-danger' : 'bg-info';
                                        ?>
                                        <span class="badge <?= $badgeClass ?> fs-6">
                                            <?= $cls['booked_count'] ?> / <?= $cls['capacity'] ?> Booked
                                        </span>
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