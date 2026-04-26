from pathlib import Path
path = Path('layout.php')
text = path.read_text()
old = '''    if () {
         = [
            ['key' => 'home', 'label' => 'Dashboard', 'href' => 'index.php', 'icon' => 'bi-house-door'],
            ['key' => 'announcements', 'label' => 'Announcements', 'href' => 'announcements.php', 'icon' => 'bi-megaphone'],
            ['key' => 'events', 'label' => 'Schedules', 'href' => 'events.php', 'icon' => 'bi-calendar-event'],
            ['key' => 'attendance', 'label' => 'Attendance', 'href' => 'attendance.php', 'icon' => 'bi-qr-code-scan'],
            ['key' => 'settings', 'label' => 'Settings', 'href' => 'settings.php', 'icon' => 'bi-gear'],
        ];
    }
\n\n\n
new = '''    if () {
         = [
            ['key' => 'home', 'label' => 'Dashboard', 'href' => 'index.php', 'icon' => 'bi-house-door'],
            ['key' => 'announcements', 'label' => 'Announcements', 'href' => 'announcements.php', 'icon' => 'bi-megaphone'],
            ['key' => 'events', 'label' => 'Schedules', 'href' => 'events.php', 'icon' => 'bi-calendar-event'],
            ['key' => 'attendance', 'label' => 'Attendance', 'href' => 'attendance.php', 'icon' => 'bi-qr-code-scan'],
            ['key' => 'settings', 'label' => 'Settings', 'href' => 'settings.php', 'icon' => 'bi-gear'],
        ];
        if ( === 'admin_dashboard.php') {
             = array_values(array_filter(, static function (array ): bool {
                return (['key'] ?? '') !== 'home';
            }));
        }
    }
\ntext = text.replace(old, new, 1)
path.write_text(text)
