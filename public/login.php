<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Session;
use App\Services\AuthService;

Session::start();

// If already logged in, redirect them based on role
if (AuthService::isLoggedIn()) {
    $target = Session::get('role') === 'member' ? 'my_profile.php' : 'dashboard.php';
    header("Location: " . $target);
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) die("CSRF token mismatch.");

    $auth = new AuthService();
    $usernameOrEmail = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Try Staff Login first
    if ($auth->login($usernameOrEmail, $password)) {
        header("Location: dashboard.php");
        exit;
    } 
    // If Staff fails, try Member Login
    elseif ($auth->loginMember($usernameOrEmail, $password)) {
        header("Location: my_profile.php");
        exit;
    } 
    else {
        $error = "Invalid credentials. Please check your username/email and password.";
    }
}

$csrfToken = Session::generateCsrfToken();

$pageTitle = "Login - Gym Manager";
require_once __DIR__ . '/../views/layout/header.php'; 
?>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="card shadow">
                    <div class="card-body">
                        <h3 class="card-title text-center mb-4">Sign In</h3>
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>
                        
                        <form method="POST", action="login.php">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(App\Core\Session::get('csrf_token')) ?>">
                            <div class="mb-3">
                                <label>Username or Email</label>
                                <input type="text" name="username" class="form-control" required placeholder="Staff ID or Member Email">
                                
                            </div>
                            <div class="mb-3">
                                <label>Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 mb-3">Login</button>
                        </form>
                        
                        <div class="text-center border-top pt-3">
                            <span class="text-muted d-block mb-2">New gym member?</span>
                            <a href="register.php" class="btn btn-outline-secondary w-100">Register Online</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php require_once __DIR__ . '/../views/layout/footer.php'; ?>