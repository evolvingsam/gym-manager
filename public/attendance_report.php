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

// Default to current year and month
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');

$monthlySummary = $attendanceService->getMonthlySummary($year, $month);
$totalVisitsMonth = array_sum(array_column($monthlySummary, 'total_visits'));

$pageTitle = "Attendance Reports - Gym Manager";
require_once __DIR__ . '/../views/layout/header.php';
?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Attendance Reports</h2>
        <a href="attendance.php" class="btn btn-secondary">Back to Front Desk</a>
    </div>

    <!-- Filter Form -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-center">
                <div class="col-auto">
                    <label class="col-form-label">Month</label>
                </div>
                <div class="col-auto">
                    <select name="month" class="form-select">
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= $m ?>" <?= $m === $month ? 'selected' : '' ?>>
                                <?= date('F', mktime(0, 0, 0, $m, 10)) ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <label class="col-form-label">Year</label>
                </div>
                <div class="col-auto">
                    <input type="number" name="year" class="form-control" value="<?= $year ?>" min="2000" max="2100">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="alert alert-info shadow-sm">
        <strong>Total Visits for <?= date('F Y', mktime(0, 0, 0, $month, 10, $year)) ?>:</strong> <?= $totalVisitsMonth ?>
    </div>

    <!-- Monthly Summary Table -->
    <div class="card shadow-sm">
        <div class="card-header bg-white fw-bold">Daily Breakdown</div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Date</th>
                        <th>Total Check-ins</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($monthlySummary)): ?>
                        <tr><td colspan="2" class="text-center py-4">No attendance data for this month.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($monthlySummary as $day): ?>
                        <tr>
                            <td><?= date('D, M j, Y', strtotime($day['attendance_date'])) ?></td>
                            <td><span class="badge bg-success fs-6"><?= $day['total_visits'] ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../views/layout/footer.php'; ?>