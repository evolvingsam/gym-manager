<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Session;
use App\Services\AuthService;
use App\Services\MemberService;

Session::start();
if (!isset($_SESSION['role']) || $_SESSION['role'] === 'member') {
    
    // Set an error message so they know why they were redirected
    Session::set('error', 'Unauthorized Access: You do not have permission to view the admin area.');
    
    // Redirect them back to their own dashboard or login
    header("Location: my_profile.php"); 
    exit(); // CRITICAL: Stop the rest of the page from loading
}

$memberService = new MemberService();
$error = '';
$success = Session::get('success_msg');
Session::set('success_msg', null); // clear flash message

// Handle Soft Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        die("CSRF validation failed.");
    }
    
    try {
        $memberService->deleteMember((int)$_POST['member_id']);
        Session::set('success_msg', "Member deleted successfully.");
        header("Location: members.php");
        exit;
    } catch (Exception $e) {
        $error = "Failed to delete member.";
        error_log($e->getMessage());
    }
}

// Handle Pagination & Search
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$search = $_GET['search'] ?? '';

$result = $memberService->getMembers($page, 10, $search);
$members = $result['data'];
$totalPages = $result['pages'];

$csrfToken = Session::generateCsrfToken();
$pageTitle = "Manage Members - Gym Manager";
require_once __DIR__ . '/../views/layout/header.php';
?>
<div class="container mt-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-people"></i> Members</h2>
        <div>
            <a href="dashboard.php" class="btn btn-secondary">Dashboard</a>
            <a href="member_form.php" class="btn btn-primary"><i class="bi bi-person-plus"></i> Add New Member</a>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Search Form -->
    <form method="GET" class="mb-3 d-flex shadow-sm rounded">
        <input type="text" name="search" class="form-control me-2" placeholder="Search name, email, or code..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn btn-primary px-4">Search</button>
        <?php if ($search): ?>
            <a href="members.php" class="btn btn-outline-secondary ms-2">Clear</a>
        <?php endif; ?>
    </form>

    <!-- Data Table -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-0 table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Code</th>
                        <th>Photo</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Join Date</th>
                        <th class="text-center">Manage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($members)): ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">No members found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($members as $m): ?>
                        <tr>
                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($m['member_code']) ?></span></td>
                            <td>
                                <?php if ($m['photo_path']): ?>
                                    <img src="<?= htmlspecialchars($m['photo_path']) ?>" alt="Photo" width="45" height="45" class="rounded-circle object-fit-cover shadow-sm">
                                <?php else: ?>
                                    <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center text-white shadow-sm" style="width: 45px; height: 45px; font-size: 0.8rem;">N/A</div>
                                <?php endif; ?>
                            </td>
                            <td class="fw-bold"><?= htmlspecialchars($m['full_name']) ?></td>
                            <td><?= htmlspecialchars($m['phone'] ?? 'N/A') ?></td>
                            <td><?= date('M j, Y', strtotime($m['join_date'])) ?></td>
                            <td>
                                <div class="d-flex justify-content-center gap-1">
                                    <!-- Integrations from Batches 4, 5, and 6 -->
                                    <a href="member_subscriptions.php?id=<?= $m['id'] ?>" class="btn btn-sm btn-info text-white" title="Plans"><i class="bi bi-card-list"></i> Plans</a>
                                    <a href="member_payments.php?id=<?= $m['id'] ?>" class="btn btn-sm btn-success" title="Billing"><i class="bi bi-wallet2"></i> Billing</a>
                                    <a href="member_attendance.php?id=<?= $m['id'] ?>" class="btn btn-sm btn-secondary" title="Attendance History"><i class="bi bi-clock-history"></i></a>
                                    
                                    <!-- Original CRUD Actions -->
                                    <a href="member_form.php?id=<?= $m['id'] ?>" class="btn btn-sm btn-warning" title="Edit Profile"><i class="bi bi-pencil"></i></a>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete <?= htmlspecialchars(addslashes($m['full_name'])) ?>?');">
                                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="member_id" value="<?= $m['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete Member"><i class="bi bi-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <nav class="mt-4">
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php require_once __DIR__ . '/../views/layout/footer.php'; ?>