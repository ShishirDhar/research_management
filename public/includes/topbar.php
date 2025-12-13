<?php
// Determine current page for active state
$current_page = basename($_SERVER['PHP_SELF']);
$user_type = $_SESSION['user_type'] ?? 'student';

// Dashboard URL logic
$dashboard_url = '/research_management/public/dashboard.php';
if ($user_type == 'faculty') {
    $dashboard_url = '/research_management/public/dashboard_faculty.php';
} elseif ($user_type == 'student') {
    $dashboard_url = '/research_management/public/dashboard_student.php';
}
?>

<header class="topbar">
    <div class="topbar-logo">
        RMS <span style="font-weight: 400; font-size: 0.9em; opacity: 0.8;"><?php echo ucfirst($user_type); ?></span>
    </div>

    <nav class="topbar-nav">
        <ul>
            <li>
                <a href="<?php echo $dashboard_url; ?>"
                    class="<?php echo (strpos($current_page, 'dashboard') !== false) ? 'active' : ''; ?>">
                    Overview
                </a>
            </li>

            <?php if ($user_type != 'admin'): ?>
                <li>
                    <a href="/research_management/public/profile.php"
                        class="<?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>">
                        My Profile
                    </a>
                </li>
            <?php endif; ?>



            <!-- Common Links -->
            <li>
                <a href="/research_management/modules/researchers/list.php"
                    class="<?php echo (strpos($_SERVER['REQUEST_URI'], 'researchers') !== false) ? 'active' : ''; ?>">
                    <?php echo ($user_type == 'admin') ? 'Manage Researchers' : 'Researchers'; ?>
                </a>
            </li>
            <li>
                <a href="/research_management/modules/projects/list.php"
                    class="<?php echo (strpos($_SERVER['REQUEST_URI'], 'projects') !== false) ? 'active' : ''; ?>">
                    Projects
                </a>
            </li>
            <?php if ($user_type != 'student'): ?>
                <li>
                    <a href="/research_management/modules/funding/list.php"
                        class="<?php echo (strpos($_SERVER['REQUEST_URI'], 'funding') !== false) ? 'active' : ''; ?>">
                        Funding
                    </a>
                </li>
            <?php endif; ?>

            <li>
                <a href="/research_management/modules/publications/list.php"
                    class="<?php echo (strpos($_SERVER['REQUEST_URI'], 'publications') !== false) ? 'active' : ''; ?>">
                    Publications
                </a>
            </li>
        </ul>
    </nav>

    <div class="topbar-actions">
        <form action="/research_management/public/logout.php" method="POST" style="margin: 0;">
            <button type="submit" class="btn-logout-top">Logout</button>
        </form>
    </div>
</header>