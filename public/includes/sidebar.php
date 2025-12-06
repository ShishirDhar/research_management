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

<aside class="sidebar">
    <div class="sidebar-header">
        RMS <?php echo ucfirst($user_type); ?>
    </div>
    <nav class="sidebar-nav">
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

            <?php if ($user_type == 'faculty'): ?>
                <li>
                    <a href="/research_management/modules/tasks/add.php"
                        class="<?php echo (in_array($current_page, ['add.php', 'edit.php']) && strpos($_SERVER['REQUEST_URI'], 'tasks') !== false) ? 'active' : ''; ?>">
                        Project Tasks
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
            <li>
                <a href="/research_management/modules/publications/list.php"
                    class="<?php echo (strpos($_SERVER['REQUEST_URI'], 'publications') !== false) ? 'active' : ''; ?>">
                    Publications
                </a>
            </li>
        </ul>
    </nav>
    <div class="sidebar-footer">
        <form action="/research_management/public/logout.php" method="POST">
            <button type="submit" class="btn-logout">Logout</button>
        </form>
    </div>
</aside>