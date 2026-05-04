<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Session;
use App\Services\MemberService;

Session::start();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        die("CSRF validation failed.");
    }

    try {
        if ($_POST['password'] !== $_POST['confirm_password']) {
            throw new Exception("Passwords do not match.");
        }

        $memberService = new MemberService();
        $memberService->selfRegister($_POST, $_POST['password']);
        
        $success = "Registration successful! You can now log in.";
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$csrfToken = Session::generateCsrfToken();
$pageTitle = "Register - Gym Manager";
require_once __DIR__ . '/../views/layout/header.php';
?>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow">
                <div class="card-body p-4">
                    <h3 class="card-title text-center mb-4">Member Registration</h3>
                    
                    <?php if ($error): ?> <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div> <?php endif; ?>
                    <?php if ($success): ?> 
                        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div> 
                        <a href="login.php" class="btn btn-primary w-100">Go to Login</a>
                    <?php else: ?>

                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                        <div class="mb-3">
                            <label>Full Name</label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Email Address</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Phone Number</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label>Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="mb-4">
                            <label>Confirm Password</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-success w-100">Create Account</button>
                    </form>
                    
                    <?php endif; ?>
                    <div class="text-center mt-3">
                        <a href="login.php" class="text-decoration-none">Already have an account? Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../views/layout/footer.php'; ?>