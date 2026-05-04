<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Session;
use App\Services\AuthService;
use App\Services\ClassService;

Session::start();
// Ensure they are a logged-in member
if (!AuthService::isLoggedIn() || Session::get('role') !== 'member') {
    header("Location: login.php");
    exit;
}

$memberId = Session::get('user_id');
$classService = new ClassService();

$error = '';
$success = Session::get('success_msg');
Session::set('success_msg', null);

// Handle Booking / Canceling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) die("CSRF validation failed.");

    try {
        $classId = (int)$_POST['class_id'];
        
        if ($_POST['action'] === 'book') {
            $classService->bookClass($classId, $memberId);
            Session::set('success_msg', "Spot successfully reserved!");
        } elseif ($_POST['action'] === 'cancel') {
            $classService->cancelBooking($classId, $memberId);
            Session::set('success_msg', "Your reservation was cancelled.");
        }
        
        header("Location: book_class.php");
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$upcomingClasses = $classService->getUpcomingClasses();
$myBookings = $classService->getMemberBookedClassIds($memberId); // Array of class IDs they are already in
$csrfToken = Session::generateCsrfToken();

$pageTitle = "Class Schedule - Member Portal";
require_once __DIR__ . '/../views/layout/header.php';
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Group Fitness Schedule</h2>
        <a href="my_profile.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <?php if ($success): ?> <div class="alert alert-success"><i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?></div> <?php endif; ?>
    <?php if ($error): ?> <div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?></div> <?php endif; ?>

    <div class="row">
        <?php foreach ($upcomingClasses as $cls): ?>
            <?php 
                $isBooked = in_array($cls['id'], $myBookings);
                $isFull = ($cls['booked_count'] >= $cls['capacity']);
            ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm border-0 <?= $isBooked ? 'border border-2 border-success' : '' ?>">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <h4 class="card-title text-primary fw-bold"><?= htmlspecialchars($cls['name']) ?></h4>
                            <?php if ($isBooked): ?>
                                <span class="badge bg-success mb-2"><i class="bi bi-check-lg"></i> Booked</span>
                            <?php endif; ?>
                        </div>
                        <h6 class="card-subtitle mb-3 text-muted"><i class="bi bi-person"></i> <?= htmlspecialchars($cls['instructor']) ?></h6>
                        
                        <p class="mb-1"><i class="bi bi-calendar"></i> <?= date('l, M j, Y', strtotime($cls['schedule_date'])) ?></p>
                        <p class="mb-3"><i class="bi bi-clock"></i> <?= date('h:i A', strtotime($cls['schedule_time'])) ?></p>
                        
                        <!-- Progress bar for capacity visually representing space left -->
                        <?php $percentFull = ($cls['booked_count'] / $cls['capacity']) * 100; ?>
                        <div class="progress mb-2" style="height: 10px;">
                            <div class="progress-bar <?= $percentFull >= 100 ? 'bg-danger' : 'bg-info' ?>" style="width: <?= $percentFull ?>%;"></div>
                        </div>
                        <small class="text-muted"><?= $cls['capacity'] - $cls['booked_count'] ?> spots remaining</small>
                    </div>
                    
                    <div class="card-footer bg-white border-0 p-3">
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                            <input type="hidden" name="class_id" value="<?= $cls['id'] ?>">
                            
                            <?php if ($isBooked): ?>
                                <input type="hidden" name="action" value="cancel">
                                <button type="submit" class="btn btn-outline-danger w-100">Cancel Reservation</button>
                            <?php elseif ($isFull): ?>
                                <button type="button" class="btn btn-secondary w-100" disabled>Class Full</button>
                            <?php else: ?>
                                <input type="hidden" name="action" value="book">
                                <button type="submit" class="btn btn-success w-100 fw-bold">Book Spot</button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($upcomingClasses)): ?>
            <div class="col-12"><div class="alert alert-info">Check back soon for next week's schedule!</div></div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../views/layout/footer.php'; ?>