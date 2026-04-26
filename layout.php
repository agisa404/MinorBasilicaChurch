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
    $accountNavLabel = 'Account';
    if ($isLogged) {
        $accountNavLabel = trim((string) ($user['full_name'] ?? ''));
        if ($accountNavLabel === '') {
            $accountNavLabel = trim((string) ($user['email'] ?? ''));
        }
        if ($accountNavLabel === '') {
            $accountNavLabel = 'Account';
        }
    }
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
    $isMinister = $role === 'minister';
    $isPriest = $role === 'priest';
    $canAccessAttendance = AttendanceService::canAccessModule($user);
    $isHome = $active === 'home';
    $guestAuthPages = ['account_management.php', 'forgot_password.php', 'reset_password.php'];
    $isGuestAuthPage = !$isLogged && in_array($currentPage, $guestAuthPages, true);
    $useGlobalSidebar = false;
    $logoPath = '612401184_4348220988792023_5812589285034246497_n.jpg';
    $serviceChildren = [];
    if ($isAdminStaff) {
        $serviceChildren[] = ['key' => 'admin_service_requests', 'label' => 'Requests', 'href' => 'admin_service_requests.php', 'icon' => 'bi-clipboard-check'];
    }
    $serviceChildren[] = ['key' => 'services', 'label' => 'All Services', 'href' => 'services.php', 'icon' => 'bi-grid'];
    $serviceChildren[] = ['key' => 'services_mass_intentions', 'label' => 'Mass Intentions', 'href' => 'form_mass_intentions.php', 'icon' => 'bi-journal-check'];
    $serviceChildren[] = ['key' => 'services_baptism_registration', 'label' => 'Baptism Registration', 'href' => 'form_baptism_registration.php', 'icon' => 'bi-droplet-half'];
    $serviceChildren[] = ['key' => 'services_baptismal_request', 'label' => 'Certification Request', 'href' => 'form_baptismal_request.php', 'icon' => 'bi-file-earmark-text'];
    $serviceChildren[] = ['key' => 'services_confirmation_registration', 'label' => 'Confirmation Registration', 'href' => 'form_confirmation_registration.php', 'icon' => 'bi-bookmark-check'];
    $serviceChildren[] = ['key' => 'services_holy_communion', 'label' => 'Holy Communion', 'href' => 'form_holy_communion.php', 'icon' => 'bi-stars'];
    $serviceChildren[] = ['key' => 'services_wedding_registration', 'label' => 'Wedding Registration', 'href' => 'form_wedding_registration.php', 'icon' => 'bi-heart'];
    $serviceChildren[] = ['key' => 'services_facilities_reservation', 'label' => 'Facilities Reservation', 'href' => 'form_facilities_reservation.php', 'icon' => 'bi-building'];
    $serviceChildren[] = ['key' => 'services_outside_basilica_request', 'label' => 'Outside Basilica Request', 'href' => 'form_outside_basilica_request.php', 'icon' => 'bi-signpost-split'];
    $sideLinks = [
        ['key' => 'home', 'label' => 'Dashboard', 'href' => 'index.php', 'icon' => 'bi-house-door'],
        ['key' => 'announcements', 'label' => 'Announcements', 'href' => 'announcements.php', 'icon' => 'bi-megaphone'],
        ['key' => 'services', 'label' => 'Services', 'href' => $isAdminStaff ? 'admin_service_requests.php' : 'services.php', 'icon' => 'bi-journal-text', 'children' => $serviceChildren],
        ['key' => 'documents', 'label' => 'Documents', 'href' => 'document_requests.php', 'icon' => 'bi-file-earmark-text'],
        ['key' => 'events', 'label' => 'Schedules', 'href' => 'events.php', 'icon' => 'bi-calendar-event', 'children' => []],
        ['key' => 'settings', 'label' => 'Settings', 'href' => 'settings.php', 'icon' => 'bi-gear'],
    ];
    if ($canAccessAttendance) {
        array_splice($sideLinks, 5, 0, [[
            'key' => 'attendance',
            'label' => 'Attendance',
            'href' => 'attendance.php',
            'icon' => 'bi-qr-code-scan',
        ]]);
    }
    if ($isMinister) {
        array_splice($sideLinks, $canAccessAttendance ? 6 : 5, 0, [[
            'key' => 'event_request_menu',
            'label' => 'Event Request',
            'href' => 'minister_event_request.php?kind=event',
            'icon' => 'bi-calendar-plus',
            'children' => [
                ['key' => 'event_request_event', 'label' => 'Event', 'href' => 'minister_event_request.php?kind=event', 'icon' => 'bi-calendar-event'],
                ['key' => 'event_request_mass', 'label' => 'Mass', 'href' => 'minister_event_request.php?kind=mass', 'icon' => 'bi-cross'],
            ],
        ]]);
    }

    if ($isAdmin) {

        $sideLinks[] = ['key' => 'admin_tools', 'label' => 'Admin Tools', 'href' => 'admin_service_requests.php', 'icon' => 'bi-tools', 'children' => []];
        foreach ($sideLinks as &$link) {
            if ($link['key'] === 'events') {
                $link['children'] = [
                    ['key' => 'create_schedule_event', 'label' => 'Create Schedule - Event', 'href' => 'event_schedule_admin.php?kind=event', 'icon' => 'bi-calendar-plus'],
                    ['key' => 'create_schedule_mass', 'label' => 'Create Schedule - Mass', 'href' => 'event_schedule_admin.php?kind=mass', 'icon' => 'bi-cross'],
                    ['key' => 'events', 'label' => 'Calendar Module', 'href' => 'events.php', 'icon' => 'bi-calendar-event'],
                ];
            }
            if ($link['key'] === 'admin_tools') {
                $link['children'] = [
                    ['key' => 'admin_create_account', 'label' => 'Create Account', 'href' => 'admin_create_account.php', 'icon' => 'bi-person-plus'],
                    ['key' => 'admin_carousel', 'label' => 'Carousel Pics', 'href' => 'admin_carousel.php', 'icon' => 'bi-images'],
                    ['key' => 'admin_view_as', 'label' => 'View As', 'href' => 'admin_view_as.php', 'icon' => 'bi-person-bounding-box'],
                ];
            }
        }
        unset($link);
    }
    $seasonClass = 'liturgical-ordinary';
    $appNow = app_now();
    $dateTimeValidationConfig = [
        'nowIso' => $appNow->format(DateTimeInterface::ATOM),
        'timezone' => $appNow->getTimezone()->getName(),
        'message' => current_datetime_validation_message(),
    ];
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
        <script>
            window.APP_DATE_TIME_VALIDATION = <?php echo json_encode($dateTimeValidationConfig, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
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
                        <?php endif; ?>
                        <?php if ($isLogged): ?>
                            <a href="account_management.php" class="<?php echo $active === 'account' ? 'active' : ''; ?>">
                                <i class="bi bi-person"></i><span><?php echo e($accountNavLabel); ?></span>
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
                                    <?php endif; ?>
                                    <?php if ($isLogged): ?>
                                        <a href="account_management.php" class="<?php echo $active === 'account' ? 'active' : ''; ?>">
                                            <i class="bi bi-person"></i>
                                            <span><?php echo e($accountNavLabel); ?></span>
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
                <?php if (is_view_as_active()): ?>
                    <?php $previewRole = current_view_as_role(); ?>
                    <?php $adminActor = current_admin_actor(); ?>
                    <div class="alert alert-info d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                        <div>
                            Viewing as <strong><?php echo e(ucfirst((string)$previewRole)); ?></strong>
                            <?php if ($adminActor): ?>
                                while signed in as admin <strong><?php echo e($adminActor['full_name'] ?: $adminActor['email']); ?></strong>.
                            <?php endif; ?>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <a class="btn btn-sm btn-outline-light" href="admin_view_as.php">Manage View As</a>
                            <form method="POST" action="admin_view_as.php" class="m-0" data-suppress-alerts="true">
                                <input type="hidden" name="action" value="stop_preview">
                                <button class="btn btn-sm btn-warning" type="submit">Return To Admin</button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if ($flash): ?>
                    <div
                        class="alert alert-<?php echo e($flash['type']); ?> mb-4"
                        data-flash-notification="true"
                        data-flash-type="<?php echo e($flash['type']); ?>"
                        data-flash-message="<?php echo e($flash['message']); ?>"
                    ><?php echo e($flash['message']); ?></div>
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
        <script src="app_notifications.js?v=<?php echo filemtime(__DIR__ . '/app_notifications.js'); ?>"></script>
        <script src="global_action_confirmation.js?v=<?php echo filemtime(__DIR__ . '/global_action_confirmation.js'); ?>"></script>
        <script src="global_datetime_validation.js?v=<?php echo filemtime(__DIR__ . '/global_datetime_validation.js'); ?>"></script>
        <script src="service_forms.js?v=<?php echo filemtime(__DIR__ . '/service_forms.js'); ?>"></script>
    </body>
    </html>
    <?php
}
?>




