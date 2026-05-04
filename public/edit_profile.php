<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Session;
use App\Services\AuthService;
use App\Services\MemberService;

Session::start();
if (!AuthService::isLoggedIn() || Session::get('role') !== 'member') {
    header("Location: login.php");
    exit;
}

$memberId = Session::get('user_id');
$memberService = new MemberService();
$member = $memberService->getMemberById($memberId);

$error = '';
$success = Session::get('success_msg');
Session::set('success_msg', null);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) die("CSRF validation failed.");

    try {
        $data = [
            'phone' => $_POST['phone'] ?? '',
            'emergency_contact' => $_POST['emergency_contact'] ?? ''
        ];
        
        $photoFile = (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) ? $_FILES['photo'] : null;

        $memberService->updateOwnProfile($memberId, $data, $photoFile);
        Session::set('success_msg', "Profile updated successfully.");
        header("Location: edit_profile.php");
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$csrfToken = Session::generateCsrfToken();
$pageTitle = "Edit Profile - Gym Manager";
require_once __DIR__ . '/../views/layout/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-bold"><i class="bi bi-gear"></i> Account Settings</div>
                <div class="card-body">
                    <?php if ($success): ?> <div class="alert alert-success"><?= htmlspecialchars($success) ?></div> <?php endif; ?>
                    <?php if ($error): ?> <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div> <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                        
                        <!-- Locked Fields -->
                        <div class="mb-3">
                            <label class="text-muted small">Full Name (Contact admin to change)</label>
                            <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($member['full_name']) ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small">Email Address</label>
                            <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($member['email']) ?>" readonly>
                        </div>

                        <!-- Editable Fields -->
                        <div class="mb-3">
                            <label class="fw-bold">Phone Number</label>
                            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($member['phone'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="fw-bold">Emergency Contact Name & Phone</label>
                            <input type="text" name="emergency_contact" class="form-control" value="<?= htmlspecialchars($member['emergency_contact'] ?? '') ?>">
                        </div>
                        <div class="mb-4">
                            <label class="fw-bold">Update Profile Photo (Max 2MB, JPG/PNG)</label>
                            <input type="file" name="photo" class="form-control" accept="image/jpeg, image/png, image/webp">
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="my_profile.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../views/layout/footer.php'; ?>