<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Session;
use App\Services\AuthService;
use App\Services\AttendanceService;

Session::start();
if (!AuthService::isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$attendanceService = new AttendanceService();
$error = '';
$success = Session::get('success_msg');
Session::set('success_msg', null);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        die("CSRF validation failed.");
    }

    try {
        $member = $attendanceService->logCheckIn($_POST['member_code']);
        Session::set('success_msg', "Successfully checked in: " . $member['full_name']);
        header("Location: attendance.php");
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$todayCheckIns = $attendanceService->getDailyCheckIns();
$csrfToken = Session::generateCsrfToken();
$pageTitle = "Front Desk - Attendance - Gym Manager";
require_once __DIR__ . '/../views/layout/header.php';
?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Front Desk: Check-In</h2>
        <div>
            <a href="dashboard.php" class="btn btn-secondary">Dashboard</a>
            <a href="attendance_report.php" class="btn btn-info">View Reports</a>
        </div>
    </div>

    <?php if ($success): ?> <div class="alert alert-success fs-5 fw-bold"><?= htmlspecialchars($success) ?></div> <?php endif; ?>
    <?php if ($error): ?> <div class="alert alert-danger fs-5"><?= htmlspecialchars($error) ?></div> <?php endif; ?>

    <div class="row">
        <!-- Check-in Form -->
        <div class="col-md-5 mb-4">
            <div class="card shadow-sm border-primary">
                <div class="card-header bg-primary text-white text-center fs-5">
                    Log Visit
                </div>
                <div class="card-body p-4">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                        <div class="mb-3">
                            <label class="form-label text-muted">Scan or Type Member Code</label>
                            <input type="text" name="member_code" class="form-control form-control-lg text-uppercase" placeholder="e.g. GYM-2410-A1B2" required autofocus>
                        </div>
                        <button type="submit" class="btn btn-success btn-lg w-100">Check In</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Today's Feed -->
        <div class="col-md-7">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-bold">
                    Today's Activity (<?= count($todayCheckIns) ?> visits)
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Member Code</th>
                                <th>Name</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($todayCheckIns)): ?>
                                <tr><td colspan="3" class="text-center py-3 text-muted">No check-ins yet today.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($todayCheckIns as $log): ?>
                                <tr>
                                    <td><?= date('h:i A', strtotime($log['check_in_time'])) ?></td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($log['member_code']) ?></span></td>
                                    <td><?= htmlspecialchars($log['full_name']) ?></td>
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