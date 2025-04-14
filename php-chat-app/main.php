<?php
// File: views/layouts/main.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? $title . ' - ' . APP_NAME : APP_NAME ?></title>
    <!-- CSS -->
    <link rel="stylesheet" href="/public/css/main.css">
    <link rel="stylesheet" href="/public/css/responsive.css">
    <?php if (isset($styles)): foreach ($styles as $style): ?>
        <link rel="stylesheet" href="<?= $style ?>">
    <?php endforeach; endif; ?>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/public/assets/icons/favicon.png">
</head>
<body class="<?= isset($bodyClass) ? $bodyClass : '' ?>">
    <?php if (!isset($hideHeader) || !$hideHeader): ?>
    <header class="main-header">
        <div class="container">
            <div class="logo">
                <a href="/">
                    <img src="/public/assets/images/logo.png" alt="<?= APP_NAME ?>">
                </a>
            </div>
            <nav class="main-nav">
                <ul>
                    <?php if (isset($user) && $user): ?>
                        <li><a href="/chat">Chat</a></li>
                        <li><a href="/profile">Profile</a></li>
                        <li><a href="/logout">Logout</a></li>
                    <?php else: ?>
                        <li><a href="/login">Login</a></li>
                        <li><a href="/register">Register</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            <button class="mobile-menu-toggle">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </header>
    <?php endif; ?>

    <main class="main-content">
        <?php if (isset($pageTitle)): ?>
            <h1 class="page-title"><?= $pageTitle ?></h1>
        <?php endif; ?>
        
        <?php if (isset($flash) && !empty($flash)): ?>
            <div class="flash-message <?= $flash['type'] ?>">
                <?= $flash['message'] ?>
            </div>
        <?php endif; ?>
        
        <?= $content ?? '' ?>
    </main>

    <?php if (!isset($hideFooter) || !$hideFooter): ?>
    <footer class="main-footer">
        <div class="container">
            <p>&copy; <?= date('Y') ?> <?= APP_NAME ?>. All rights reserved.</p>
        </div>
    </footer>
    <?php endif; ?>

    <!-- JavaScript -->
    <script src="/public/js/main.js"></script>
    <?php if (isset($scripts)): foreach ($scripts as $script): ?>
        <script src="<?= $script ?>"></script>
    <?php endforeach; endif; ?>
</body>
</html>