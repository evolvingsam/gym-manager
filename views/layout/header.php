<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'LASUFit - Gym Management') ?></title>
    
    <!-- Third-Party CSS (Bootstrap & Icons) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <!-- Centralized Custom CSS -->
    <link rel="stylesheet" href="/assets/css/style.css">
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
</head>
<body>

<!-- GLOBAL NAVIGATION BAR -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4 shadow-sm">
    <div class="container">
        
        <!-- THE NEW LASUFIT SVG LOGO -->
        <a class="navbar-brand" href="dashboard.php" style="padding: 0; margin-right: 20px;">
            <svg width="150" height="40" viewBox="0 0 150 40" xmlns="http://www.w3.org/2000/svg">
                <g fill="none" fill-rule="evenodd">
                    <rect fill="#0d6efd" x="0" y="10" width="6" height="20" rx="1"/>
                    <rect fill="#0d6efd" x="8" y="5" width="8" height="30" rx="2"/>
                    <rect fill="#adb5bd" x="16" y="17" width="16" height="6"/>
                    <rect fill="#0d6efd" x="32" y="5" width="8" height="30" rx="2"/>
                    <rect fill="#0d6efd" x="42" y="10" width="6" height="20" rx="1"/>
                    <text font-family="system-ui, -apple-system, sans-serif" font-size="22" font-weight="900" fill="#f8f9fa">
                        <tspan x="56" y="28" letter-spacing="1">LASU</tspan>
                        <tspan x="112" y="28" fill="#0d6efd">Fit</tspan>
                    </text>
                </g>
            </svg>
        </a>

        <!-- Dynamic User Status & Theme Toggle -->
        <div class="d-flex align-items-center gap-3">
            <!-- Theme Toggle Button -->
            <button id="theme-toggle" class="btn btn-outline-light btn-sm rounded-circle" aria-label="Toggle Theme">
                <i id="theme-icon" class="bi bi-moon-fill"></i>
            </button>

            <?php if (isset($_SESSION['user_id'])): ?>
                <span class="text-white d-none d-md-inline border-start border-secondary ps-3">
                    <?php if ($_SESSION['role'] === 'member'): ?>
                        Code: <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>
                    <?php else: ?>
                        <strong><?= htmlspecialchars($_SESSION['full_name']) ?></strong> (Staff)
                    <?php endif; ?>
                </span>
                <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
            <?php endif; ?>
        </div>
        
    </div>
</nav>