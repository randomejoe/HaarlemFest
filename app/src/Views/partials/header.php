<?php
$isLoggedIn = !empty($_SESSION['user_id']);
$displayName = trim((string) ($_SESSION['username'] ?? ''));
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if ($displayName === '') {
    $displayName = trim((string) ($_SESSION['email'] ?? ''));
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Haarlem Festival</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@400;500;600;700;800&family=Manrope:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/festival.css">
</head>

<body>
    <header class="topbar sticky-top border-bottom">
        <nav class="navbar navbar-expand-lg py-3">
            <div class="container">
                <a class="navbar-brand brand d-inline-flex align-items-center gap-2" href="/">
                    <span class="brand-mark">H</span>
                    <span class="brand-text">Haarlem Festival</span>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#festivalNav" aria-controls="festivalNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="festivalNav">
                    <ul class="navbar-nav ms-lg-auto mb-2 mb-lg-0 fw-semibold">
                        <li class="nav-item"><a class="nav-link<?php echo $currentPath === '/' ? ' active' : ''; ?>" href="/">Home</a></li>
                        <li class="nav-item"><a class="nav-link<?php echo $currentPath === '/jazz' ? ' active' : ''; ?>" href="/jazz">Jazz</a></li>
                        <li class="nav-item"><a class="nav-link" href="#events">Yummy</a></li>
                        <li class="nav-item"><a class="nav-link" href="#events">Dance</a></li>
                        <li class="nav-item"><a class="nav-link" href="#events">History</a></li>
                    </ul>
                    <ul class="navbar-nav navbar-tools flex-row align-items-center gap-2 mt-2 mt-lg-0 ms-lg-3">
                        <li class="nav-item">
                            <a href="/planner" class="icon-btn" aria-label="Planner">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M7 2v3M17 2v3M3 9h18M5 5h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z" />
                                </svg>
                            </a>
                        </li>
                        <?php if ($isLoggedIn): ?>
                            <li class="nav-item user-menu">
                                <button type="button" class="user-menu-toggle">
                                    <?php echo htmlspecialchars($displayName !== '' ? $displayName : 'Account', ENT_QUOTES, 'UTF-8'); ?>
                                </button>
                                <div class="user-menu-dropdown">
                                    <a href="/account" class="user-menu-item">Account</a>
                                    <a href="/orders" class="user-menu-item">Orders</a>
                                    <form method="post" action="/logout" class="m-0">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) ($csrf_token ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                        <button type="submit" class="user-menu-item user-menu-logout">Logout</button>
                                    </form>
                                </div>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a href="/login" class="icon-btn" aria-label="Account">
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M20 21a8 8 0 1 0-16 0M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" />
                                    </svg>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
