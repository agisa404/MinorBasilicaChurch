<?php
require_once __DIR__ . '/layout.php';

$user = current_user();
$isAdmin = is_admin_or_staff($user);
purge_expired_announcements();

if ($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim($_POST['action'] ?? '');

    if ($action === 'create') {
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $isPublished = isset($_POST['is_published']) ? 1 : 0;
        $expiresInput = trim($_POST['expires_at'] ?? '');
        $expiresAt = null;
        if ($expiresInput !== '') {
            redirect_if_invalid_future_datetime_rules([
                ['datetime_local' => $expiresInput, 'allow_blank' => true],
            ], 'announcements.php');

            $parsed = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $expiresInput);
            if (!$parsed) {
                set_flash('danger', 'Invalid end date/time.');
                header('Location: announcements.php');
                exit();
            }
            $expiresAt = $parsed->format('Y-m-d H:i:s');
        }

        if ($title === '' || $content === '') {
            set_flash('danger', 'Announcement title and content are required.');
            header('Location: announcements.php');
            exit();
        }

        $stmt = $conn->prepare('INSERT INTO announcements (title, content, is_published, expires_at, created_by) VALUES (?, ?, ?, ?, ?)');
        $uid = (int)$user['id'];
        $stmt->bind_param('ssisi', $title, $content, $isPublished, $expiresAt, $uid);
        $stmt->execute();
        $stmt->close();

        set_flash('success', 'Announcement created.');
        header('Location: announcements.php');
        exit();
    }

    if ($action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        $status = (int)($_POST['status'] ?? 0);
        $stmt = $conn->prepare('UPDATE announcements SET is_published = ? WHERE id = ?');
        $stmt->bind_param('ii', $status, $id);
        $stmt->execute();
        $stmt->close();
        set_flash('success', 'Announcement status updated.');
        header('Location: announcements.php');
        exit();
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare('DELETE FROM announcements WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        set_flash('success', 'Announcement deleted.');
        header('Location: announcements.php');
        exit();
    }
}

if ($isAdmin) {
    $stmt = $conn->prepare('SELECT a.*, u.full_name FROM announcements a LEFT JOIN users u ON u.id = a.created_by ORDER BY a.created_at DESC');
} else {
    $now = app_now()->format('Y-m-d H:i:s');
    $stmt = $conn->prepare('SELECT a.*, u.full_name FROM announcements a LEFT JOIN users u ON u.id = a.created_by WHERE a.is_published = 1 AND (a.expires_at IS NULL OR a.expires_at > ?) ORDER BY a.created_at DESC');
    $stmt->bind_param('s', $now);
}
$stmt->execute();
$announcements = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

render_header('Announcements', 'announcements');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Church Announcements</h2>
    <?php if ($isAdmin): ?>
        <span class="badge text-bg-warning">Admin/Staff Mode</span>
    <?php endif; ?>
</div>

<?php if ($isAdmin): ?>
    <div class="card bg-dark border-warning-subtle mb-4">
        <div class="card-body">
            <h5 class="text-warning">Create Announcement</h5>
            <form method="POST" class="row g-3">
                <input type="hidden" name="action" value="create">
                <div class="col-12">
                    <label class="form-label">Title</label>
                    <input class="form-control" type="text" name="title" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Message</label>
                    <textarea class="form-control" name="content" rows="4" required></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">End Date/Time (optional)</label>
                    <input class="form-control" type="datetime-local" name="expires_at" data-datetime-future="true" data-datetime-role="datetime-local">
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_published" id="is_published" checked>
                        <label class="form-check-label" for="is_published">Publish immediately</label>
                    </div>
                </div>
                <div class="col-12">
                    <button class="btn btn-warning" type="submit">Post Announcement</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<div class="row g-3">
    <?php if (!$announcements): ?>
        <div class="col-12">
            <div class="alert alert-info">No announcements available yet.</div>
        </div>
    <?php else: ?>
        <?php foreach ($announcements as $item): ?>
            <div class="col-12">
                <div class="card bg-dark border-warning-subtle">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start gap-3">
                            <div>
                                <h5 class="text-warning mb-2"><?php echo e($item['title']); ?></h5>
                                <p class="mb-2"><?php echo nl2br(e($item['content'])); ?></p>
                                <small class="text-secondary">
                                    Posted by <?php echo e($item['full_name'] ?: 'System'); ?> on <?php echo e($item['created_at']); ?>
                                </small>
                                <?php if (!empty($item['expires_at'])): ?>
                                    <div><small class="text-warning">Ends: <?php echo e($item['expires_at']); ?></small></div>
                                <?php endif; ?>
                            </div>
                            <?php if ($isAdmin): ?>
                                <form method="POST" class="text-end">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?php echo (int)$item['id']; ?>">
                                    <input type="hidden" name="status" value="<?php echo $item['is_published'] ? 0 : 1; ?>">
                                    <span class="badge text-bg-<?php echo $item['is_published'] ? 'success' : 'secondary'; ?> mb-2">
                                        <?php echo $item['is_published'] ? 'Published' : 'Draft'; ?>
                                    </span><br>
                                    <button class="btn btn-sm btn-outline-light" type="submit">
                                        <?php echo $item['is_published'] ? 'Unpublish' : 'Publish'; ?>
                                    </button>
                                </form>
                                <form method="POST" class="text-end ms-2">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo (int)$item['id']; ?>">
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php render_footer(); ?>
