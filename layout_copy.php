<?php
require_once __DIR__ . '/core.php';

function module_nav_has_active_child(array $link, string $active): bool
{
    if (!empty($link['key']) && $link['key'] === $active) {
        return true;
    }
    foreach ($link['children'] ?? [] as $child) {
        if (!empty($child['key']) && $child['key'] === $active) {
            return true;
        }
    }
    return false;
}

function render_module_nav_link(array $link, string $active): void
{
    $hasChildren = !empty($link['children']);
    $isActive = module_nav_has_active_child($link, $active);
    if ($hasChildren):
        ?>
        <details class='module-dropdown<?php echo $isActive ? ' active' : ''; ?>' <?php echo $isActive ? 'open' : ''; ?>>
            <summary>
                <span class='module-link-main'>
                    <i class='bi <?php echo e($link['icon'] ?? ''); ?>'></i>
                    <span><?php echo e($link['label'] ?? ''); ?></span>
                </span>
                <i class='bi bi-chevron-down module-dropdown-caret' aria-hidden='true'></i>
            </summary>
            <div class='module-dropdown-menu'>
                <?php foreach ($link['children'] as $child): ?>
                    <a href='<?php echo e($child['href'] ?? '#'); ?>' class='module-dropdown-item<?php echo ($active === ($child['key'] ?? '')) ? ' active' : ''; ?>'>
                        <?php if (!empty($child['icon'])): ?>
                            <i class='bi <?php echo e($child['icon']); ?>'></i>
                        <?php endif; ?>
                        <span><?php echo e($child['label'] ?? ''); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </details>
        <?php
    else:
        ?>
        <a href='<?php echo e($link['href'] ?? '#'); ?>' class='<?php echo $isActive ? 'active' : ''; ?>'>
            <i class='bi <?php echo e($link['icon'] ?? ''); ?>'></i>
            <span><?php echo e($link['label'] ?? ''); ?></span>
        </a>
        <?php
    endif;
}

function render_header(string $title, string $active = ''): void
{
    $user = current_user();
    $isLogged = $user !== null;
    $currentPage = basename($_SERVER['SCRIPT_NAME'] ?? '');
    $publicPages = [
        'index.php',
        'account_management.php',
        'login.php',
        'register.php',
        'logout.php',
        'register.html',
        'about_us.php',
        'mission_vision.php',
        'ministries.php',
        'help.php',
        'forgot_password.php',
        'reset_password.php',
    ];
    if (!$isLogged && !in_array($currentPage, $publicPages, true)) {
        set_flash('danger', 'Please login first.');
        header('Location: index.php');
        exit();
    }
    $isAdminStaff = is_admin_or_staff($user);
    $role = $user['role'] ?? '';
    $isAdmin = $role === 'admin';
    $isPriest = $role === 'priest';
    $isHome = $active === 'home';
    $guestAuthPages = ['account_management.php', 'forgot_password.php', 'reset_password.php'];
    $isGuestAuthPage = !$isLogged && in_array($currentPage, $guestAuthPages, true);
    $useGlobalSidebar = false;
    $logoPath = '612401184_4348220988792023_5812589285034246497_n.jpg';
    $sideLinks = [
        ['key' => 'home', 'label' => 'Dashboard', 'href' => 'index.php', 'icon' => 'bi-house-door'],
        ['key' => 'announcements', 'label' => 'Announcements', 'href' => 'announcements.php', 'icon' => 'bi-megaphone'],
        ['key' => 'services', 'label' => 'Services', 'href' => 'services.php', 'icon' => 'bi-journal-text'],
        ['key' => 'documents', 'label' => 'Documents', 'href' => 'document_requests.php', 'icon' => 'bi-file-earmark-text'],
        ['key' => 'events', 'label' => 'Schedules', 'href' => 'events.php', 'icon' => 'bi-calendar-event', 'children' => []],
        ['key' => 'attendance', 'label' => 'Attendance', 'href' => 'attendance.php', 'icon' => 'bi-qr-code-scan'],
        ['key' => 'settings', 'label' => 'Settings', 'href' => 'settings.php', 'icon' => 'bi-gear'],
    ];
    if ($isAdmin) {
        $sideLinks[] = ['key' => 'admin_tools', 'label' => 'Admin Tools', 'href' => 'admin_service_requests.php', 'icon' => 'bi-tools', 'children' => []];
        foreach ($sideLinks as &$link) {
            if ($link['key'] === 'events') {
                $link['children'] = [
                    ['key' => 'create_schedule', 'label' => 'Create Schedule', 'href' => 'event_schedule_admin.php', 'icon' => 'bi-calendar-plus'],
                    ['key' => 'events', 'label' => 'Calendar Module', 'href' => 'events.php', 'icon' => 'bi-calendar-event'],
                ];
            }
            if ($link['key'] === 'admin_tools') {
                $link['children'] = [
                    ['key' => 'admin_service_requests', 'label' => 'Service Requests', 'href' => 'admin_service_requests.php', 'icon' => 'bi-clipboard-check'],
                    ['key' => 'admin_create_account', 'label' => 'Create Account', 'href' => 'admin_create_account.php', 'icon' => 'bi-person-plus'],
                    ['key' => 'admin_carousel', 'label' => 'Carousel Pics', 'href' => 'admin_carousel.php', 'icon' => 'bi-images'],
                ];
            }
        }
        unset($link);
    }
    $seasonClass = 'liturgical-ordinary';
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo e($title); ?></title>
        <script>
            (function () {
                localStorage.setItem('themeMode', 'default');
                document.documentElement.setAttribute('data-theme', 'default');
                document.documentElement.setAttribute('data-bs-theme', 'dark');
            })();
        </script>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
        <link rel="stylesheet" href="app.css?v=<?php echo filemtime(__DIR__ . '/app.css'); ?>">
    </head>
    <body class="<?php echo trim(($isHome ? 'page-home ' : '') . ($isGuestAuthPage ? 'page-auth ' : '') . $seasonClass); ?>">
        <?php if ($useGlobalSidebar): ?>
        <button class="btn btn-outline-light app-mobile-menu d-lg-none m-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#appSidebar" aria-controls="appSidebar">
            <i class="bi bi-list"></i> Menu
        </button>
        <div class="offcanvas offcanvas-start offcanvas-lg app-sidebar-wrap" tabindex="-1" id="appSidebar" aria-labelledby="appSidebarLabel">
            <div class="offcanvas-header d-lg-none">
                <h5 class="offcanvas-title" id="appSidebarLabel">Minor Basilica</h5>
                <button type="button" class="btn-close btn-close-white text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body p-0">
                <aside class="app-sidebar">
                    <div class="app-sidebar-header">
                        <img class="app-sidebar-logo" src="<?php echo e($logoPath); ?>" alt="Basilica Logo">
                        <div class="app-sidebar-title">Minor Basilica</div>
                        <div class="app-sidebar-sub">Portal Navigation</div>
                    </div>
                    <nav class="app-side-nav app-side-main">
                        <?php foreach ($sideLinks as $link): ?>
                            <?php render_module_nav_link($link, $active); ?>
                        <?php endforeach; ?>
                        <?php if ($isPriest): ?>
                            <a href="priest_dashboard.php" class="<?php echo $active === 'priest' ? 'active' : ''; ?>">
                                <i class="bi bi-person-badge"></i><span>Priest Dashboard</span>
                            </a>
                        <?php endif; ?>                        <?php if (!empty($user) && ($user['role'] ?? '') === 'minister'): ?>
                            <a href="minister_event_request.php" class="<?php echo $active === 'event_request' ? 'active' : ''; ?>">
                                <i class="bi bi-calendar-plus"></i><span>Event Request</span>
                            </a>
                        <?php endif; ?>
                        <?php if ($isLogged): ?>
                            <a href="account_management.php" class="<?php echo $active === 'account' ? 'active' : ''; ?>">
                                <i class="bi bi-person"></i><span>Account</span>
                            </a>
                        <?php endif; ?><?php if ($isLogged): ?>
                            <a href="logout.php"><i class="bi bi-box-arrow-right"></i><span>Logout</span></a>
                        <?php endif; ?>
                    </nav>
                    <?php if ($isLogged): ?>
                        <div class="app-side-meta">
                            <small><?php echo e($user['full_name'] ?: $user['email']); ?></small>
                        </div>
                    <?php endif; ?>
                </aside>
            </div>
        </div>
        <?php endif; ?>
        <main class="app-main py-4">
            <div class="container-fluid app-main-container">
                <?php $hasModulePanel = $isLogged; ?>
                <?php $GLOBALS['__layout_has_module_panel'] = $hasModulePanel; ?>
                <?php if ($hasModulePanel): ?>
                    <button class="btn btn-outline-light module-mobile-menu d-lg-none mb-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#moduleSidebar" aria-controls="moduleSidebar"><i class="bi bi-list"></i> Menu</button>
                    <div class="offcanvas offcanvas-start module-sidebar-wrap" tabindex="-1" id="moduleSidebar" aria-labelledby="moduleSidebarLabel"><div class="offcanvas-header"><h5 class="offcanvas-title" id="moduleSidebarLabel">Modules</h5><button type="button" class="btn-close btn-close-white text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button></div><?php require __DIR__ . '/partials/module_menu.php'; ?></div>
                    <div class="module-layout">
                        <aside class="module-left">
                            <div class="module-side rounded-4 p-3 h-100 d-flex flex-column">
                                <div class="module-side-title mb-3">Modules</div>
                                <nav class="d-grid gap-2 module-links">
                                    <?php foreach ($sideLinks as $link): ?>
                                        <?php render_module_nav_link($link, $active); ?>
                                    <?php endforeach; ?>
                                    <?php if ($isPriest): ?>
                                        <a href="priest_dashboard.php" class="<?php echo $active === 'priest' ? 'active' : ''; ?>">
                                            <i class="bi bi-person-badge"></i>
                                            <span>Priest Dashboard</span>
                                        </a>
                                    <?php endif; ?>                                    <?php if (!empty($user) && ($user['role'] ?? '') === 'minister'): ?>
                                        <a href="minister_event_request.php" class="<?php echo $active === 'event_request' ? 'active' : ''; ?>">
                                            <i class="bi bi-calendar-plus"></i>
                                            <span>Event Request</span>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($isLogged): ?>
                                        <a href="account_management.php" class="<?php echo $active === 'account' ? 'active' : ''; ?>">
                                            <i class="bi bi-person"></i>
                                            <span>Account</span>
                                        </a>
                                    <?php endif; ?><?php if ($isLogged): ?>
                                        <a href="logout.php">
                                            <i class="bi bi-box-arrow-right"></i>
                                            <span>Logout</span>
                                        </a>
                                    <?php endif; ?>
                                </nav>
                            </div>
                        </aside>
                        <section class="module-main">
                <?php endif; ?>
                <?php $flash = get_flash(); ?>
                <?php if ($flash): ?>
                    <div class="alert alert-<?php echo e($flash['type']); ?> mb-4"><?php echo e($flash['message']); ?></div>
                <?php endif; ?>
    <?php
}

function render_footer(): void
{
    $hasModulePanel = !empty($GLOBALS['__layout_has_module_panel']);
    ?>
            <?php if ($hasModulePanel): ?>
                        </section>
                    </div>
            <?php endif; ?>
            </div>
        </main>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            (function () {
                var current = window.location.pathname.split('/').pop() || 'index.php';
                var links = document.querySelectorAll('.app-side-nav a');
                links.forEach(function (link) {
                    var href = link.getAttribute('href') || '';
                    if (href === current) {
                        link.classList.add('active');
                        link.setAttribute('aria-current', 'page');
                    }
                });
            })();

        </script>
    </body>
    </html>
    <?php
}
?>




