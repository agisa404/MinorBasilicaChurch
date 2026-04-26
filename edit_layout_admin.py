from pathlib import Path
path = Path('layout.php')
text = path.read_text()
marker = 'if () {'
start = text.index(marker)
insert_pos = text.index('\n', start) + 1
insert = "        $sideLinks[] = ['key' => 'admin_tools', 'label' => 'Admin Tools', 'href' => 'admin_dashboard.php', 'icon' => 'bi-tools', 'children' => []];\r\n"
text = text[:insert_pos] + insert + text[insert_pos:]
path.write_text(text)
