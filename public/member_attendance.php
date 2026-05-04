<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Session;
use App\Services\AuthService;
use App\Services\MemberService;
use App\Services\AttendanceService;

Session::start();
if (!AuthService::isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$memberId = (int)($_GET['id'] ?? 0);
if (!$memberId) die("Member ID required.");

$memberService = new MemberService();
$attendanceService = new AttendanceService();

$member = $memberService->getMemberById($memberId);
if (!$member) die("Member not found.");

$history = $attendanceService->getMemberHistory($memberId);
$pageTitle = "Attendance: " . htmlspecialchars($member['full_name']) . " - Gym Manager";
require_once __DIR__ . '/../views/layout/header.php';
?>
<div class="container mt-4">
    <div class="mb-4 d-flex justify-content-between align-items-center">
        <h2><?= htmlspecialchars($member['full_name']) ?>'s Attendance History</h2>
        <a href="members.php" class="btn btn-secondary">Back to Members</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($history)): ?>
                        <tr><td colspan="2" class="text-center py-3">No check-ins recorded yet.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($history as $record): ?>
                        <tr>
                            <td><?= date('M j, Y', strtotime($record['check_in_time'])) ?></td>
                            <td><?= date('h:i:s A', strtotime($record['check_in_time'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../views/layout/footer.php'; ?>