<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Session;
use App\Services\AuthService;
use App\Services\MemberService;

Session::start();
if (!AuthService::isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$memberService = new MemberService();
$error = '';
$memberId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$member = null;

// If editing, fetch existing data
if ($memberId) {
    $member = $memberService->getMemberById($memberId);
    if (!$member) {
        die("Member not found.");
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        die("CSRF validation failed.");
    }

    try {
        $data = [
            'full_name' => $_POST['full_name'] ?? '',
            'email' => $_POST['email'] ?? '',
            'phone' => $_POST['phone'] ?? '',
            'emergency_contact' => $_POST['emergency_contact'] ?? '',
            'join_date' => $_POST['join_date'] ?? date('Y-m-d')
        ];

        $photoFile = (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) ? $_FILES['photo'] : null;

        if ($memberId) {
            $memberService->updateMember($memberId, $data, $photoFile);
            Session::set('success_msg', "Member updated successfully.");
        } else {
            $memberService->createMember($data, $photoFile);
            Session::set('success_msg', "Member created successfully.");
        }

        header("Location: members.php");
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$csrfToken = Session::generateCsrfToken();
$pageTitle = ($memberId ? 'Edit' : 'Add') . " Member - Gym Manager";
require_once __DIR__ . '/../views/layout/header.php';
?>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h4 class="mb-0"><?= $memberId ? 'Edit Member' : 'Add New Member' ?></h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label>Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($member['full_name'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($member['email'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label>Phone</label>
                                <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($member['phone'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label>Join Date <span class="text-danger">*</span></label>
                                <input type="date" name="join_date" class="form-control" value="<?= htmlspecialchars($member['join_date'] ?? date('Y-m-d')) ?>" <?= $memberId ? 'readonly' : 'required' ?>>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label>Emergency Contact Name & Phone</label>
                            <input type="text" name="emergency_contact" class="form-control" value="<?= htmlspecialchars($member['emergency_contact'] ?? '') ?>">
                        </div>

                        <div class="mb-4">
                            <label>Profile Photo (Max 2MB, JPG/PNG)</label>
                            <input type="file" name="photo" class="form-control" accept="image/jpeg, image/png, image/webp">
                            <?php if (!empty($member['photo_path'])): ?>
                                <div class="mt-2 text-muted small">Current photo exists. Uploading a new one will replace it.</div>
                            <?php endif; ?>
                        </div>

                        <div class="d-flex justify-content-end">
                            <a href="members.php" class="btn btn-secondary me-2">Cancel</a>
                            <button type="submit" class="btn btn-success">Save Member</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../views/layout/footer.php'; ?>