<?php
require_once __DIR__ . '/layout.php';
$user = current_user();
$actualUser = actual_user() ?: $user;

if ($user && isset($_POST['update_profile'])) {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = trim($_POST['role'] ?? ($actualUser['role'] ?? $user['role']));
    $bio = trim($_POST['bio'] ?? '');
    $about = trim($_POST['about'] ?? '');
    $avatarPath = $user['avatar_path'] ?? null;

    if ($fullName === '' || $email === '') {
        set_flash('danger', 'Full name and email are required.');
        header('Location: account_management.php?edit=1');
        exit();
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_flash('danger', 'Please enter a valid email address.');
        header('Location: account_management.php?edit=1');
        exit();
    }

    $allowedRoles = ['parishioner', 'minister', 'priest', 'staff', 'admin', 'user'];
    if (!in_array($role, $allowedRoles, true)) {
        $role = 'parishioner';
    }
    if ((($actualUser['role'] ?? $user['role']) ?? '') !== 'admin') {
        $role = $actualUser['role'] ?? $user['role'] ?? 'parishioner';
    }

    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            set_flash('danger', 'Avatar upload failed. Please try again.');
            header('Location: account_management.php?edit=1');
            exit();
        }

        $tmpPath = $_FILES['avatar']['tmp_name'] ?? '';
        $originalName = $_FILES['avatar']['name'] ?? '';
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($tmpPath) ?: '';
        $allowedMime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        if (!in_array($extension, $allowedExt, true) || !in_array($mimeType, $allowedMime, true)) {
            set_flash('danger', 'Invalid avatar file type. Please upload an image.');
            header('Location: account_management.php?edit=1');
            exit();
        }

        if (!is_dir(__DIR__ . '/uploads/avatars')) {
            mkdir(__DIR__ . '/uploads/avatars', 0777, true);
        }

        $fileName = 'avatar_' . $user['id'] . '_' . time() . '.' . $extension;
        $targetPath = __DIR__ . '/uploads/avatars/' . $fileName;

        if (!move_uploaded_file($tmpPath, $targetPath)) {
            set_flash('danger', 'Unable to save avatar image.');
            header('Location: account_management.php?edit=1');
            exit();
        }

        $avatarPath = 'uploads/avatars/' . $fileName;
    }

    $emailCheck = $conn->prepare('SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');
    $emailCheck->bind_param('si', $email, $user['id']);
    $emailCheck->execute();
    $emailTaken = $emailCheck->get_result()->num_rows > 0;
    $emailCheck->close();
    if ($emailTaken) {
        set_flash('danger', 'That email is already used by another account.');
        header('Location: account_management.php?edit=1');
        exit();
    }

    $stmt = $conn->prepare('UPDATE users SET full_name = ?, email = ?, role = ?, avatar_path = ?, bio = ?, about = ? WHERE id = ?');
    $stmt->bind_param('ssssssi', $fullName, $email, $role, $avatarPath, $bio, $about, $user['id']);
    $ok = $stmt->execute();
    $stmt->close();
    if (!$ok) {
        set_flash('danger', 'Unable to save profile right now. Please try again.');
        header('Location: account_management.php?edit=1');
        exit();
    }

    $_SESSION['user_role'] = $role;
    set_flash('success', 'Account profile updated.');
    header('Location: account_management.php');
    exit();
}

if ($user && isset($_POST['mark_read'])) {
    $stmt = $conn->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?');
    $stmt->bind_param('i', $user['id']);
    $stmt->execute();
    $stmt->close();

    set_flash('success', 'Notifications marked as read.');
    $redirect = 'account_management.php';
    if (($_GET['notifications'] ?? '') === 'all') {
        $redirect .= '?notifications=all';
    }
    header('Location: ' . $redirect);
    exit();
}

$notifications = [];
$notificationTotal = 0;
$showAllNotifications = (($_GET['notifications'] ?? '') === 'all');
$newRegisteredUsers = [];
if ($user) {
    $countStmt = $conn->prepare('SELECT COUNT(*) AS total FROM notifications WHERE user_id = ?');
    $countStmt->bind_param('i', $user['id']);
    $countStmt->execute();
    $countRow = $countStmt->get_result()->fetch_assoc();
    $countStmt->close();
    $notificationTotal = (int)($countRow['total'] ?? 0);

    $limitSql = $showAllNotifications ? '' : ' LIMIT 5';
    $stmt = $conn->prepare('SELECT id, message, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC' . $limitSql);
    $stmt->bind_param('i', $user['id']);
    $stmt->execute();
    $notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (is_admin_or_staff($user)) {
        $recentStmt = $conn->prepare('SELECT full_name, email, role, created_at FROM users WHERE id <> ? ORDER BY created_at DESC LIMIT 5');
        $recentStmt->bind_param('i', $user['id']);
        $recentStmt->execute();
        $newRegisteredUsers = $recentStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $recentStmt->close();
    }
}

$displayName = $user ? ($user['full_name'] ?? '') : '';
$displayName = trim($displayName) !== '' ? $displayName : ($user ? ($user['email'] ?? 'Member') : 'Member');
$aboutText = ($user && trim((string)($user['about'] ?? '')) !== '') ? $user['about'] : 'There is currently no information about this member.';
$bioText = ($user && trim((string)($user['bio'] ?? '')) !== '') ? $user['bio'] : 'Minor Basilica Information Management System';
$joinedDate = ($user && !empty($user['created_at'])) ? date('M d, Y', strtotime($user['created_at'])) : 'N/A';
$lastActivity = !empty($notifications)
    ? date('M d, Y h:i A', strtotime($notifications[0]['created_at']))
    : 'No recent activity';
$avatarInitial = strtoupper(substr($displayName, 0, 1));
$accountRoleLabel = $user ? (($user['role'] ?? '') === 'user' ? 'parishioner' : ($user['role'] ?? 'parishioner')) : 'parishioner';
$authMode = ($_GET['auth'] ?? '') === 'register' ? 'register' : 'login';
$registerForm = $_SESSION['register_form'] ?? [];
$registerFirstName = (string)($registerForm['first_name'] ?? '');
$registerMiddleName = (string)($registerForm['middle_name'] ?? '');
$registerLastName = (string)($registerForm['last_name'] ?? '');
$registerSuffix = (string)($registerForm['suffix'] ?? '');
$registerEmail = (string)($registerForm['email'] ?? '');
$openEditModal = isset($_GET['edit']);
$authLogoPath = '612401184_4348220988792023_5812589285034246497_n.jpg';

render_header('Account Management', 'account');
?>


<?php if (!$user): ?>
    <?php if ($authMode === 'register'): ?>
        <div class="auth-shell auth-shell-login-left">
            <div class="auth-card auth-card-login card border-0">
                <div class="card-body p-4 p-md-5">
                    <div class="auth-login-brand text-center mb-4">
                        <img class="auth-login-logo mb-3" src="<?php echo e($authLogoPath); ?>" alt="Minor Basilica Logo">
                        <h5 class="auth-login-title mb-2">Create Account</h5>
                        <p class="auth-login-subtitle mb-0">Sign up to access the full parish system modules.</p>
                    </div>
                    <form method="POST" action="register.php" id="signupForm" data-suppress-alerts="true" novalidate>
                            <label class="form-label">First Name</label>
                            <input class="form-control mb-2" type="text" name="first_name" value="<?php echo e($registerFirstName); ?>" required>
                            <label class="form-label">Middle Name</label>
                            <input class="form-control mb-2" type="text" name="middle_name" value="<?php echo e($registerMiddleName); ?>" required>
                            <label class="form-label">Last Name</label>
                            <input class="form-control mb-2" type="text" name="last_name" value="<?php echo e($registerLastName); ?>" required>
                            <label class="form-label">Suffix</label>
                            <input class="form-control mb-2" type="text" name="suffix" value="<?php echo e($registerSuffix); ?>" placeholder="Jr., Sr., III, etc. (optional)">
                            <label class="form-label">Email</label>
                            <input class="form-control mb-2" type="email" name="email" value="<?php echo e($registerEmail); ?>" placeholder="example@email.com" required>
                            <label class="form-label">Password</label>
                            <input id="signupPassword" class="form-control mb-2" type="password" name="password" autocomplete="new-password" required>
                            <div class="form-text mb-2">Use at least 8 characters with 1 uppercase letter, 1 lowercase letter, 1 number, and 1 special character.</div>
                            <div id="signupPasswordFeedback" class="text-danger small mb-2 d-none account-inline-feedback"></div>
                            <label class="form-label">Confirm Password</label>
                            <input id="signupConfirmPassword" class="form-control mb-2" type="password" name="confirm_password" autocomplete="new-password" required>
                            <div id="signupConfirmFeedback" class="text-danger small mb-2 d-none account-inline-feedback"></div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="showSignupPasswords">
                                <label class="form-check-label" for="showSignupPasswords">Show password</label>
                            </div>
                            <label class="form-label">Verification OTP</label>
                            <input class="form-control mb-2" type="text" name="otp" inputmode="numeric" autocomplete="one-time-code" placeholder="Enter the 6-digit OTP" maxlength="6">
                            <button class="btn btn-outline-light w-100 mb-2" type="submit" name="signup_action" value="request_otp">Send OTP</button>
                            <button class="btn auth-login-btn w-100 mb-3" type="submit" name="signup_action" value="verify_otp">Verify & Sign Up</button>
                            <div class="d-flex gap-2 auth-actions auth-login-links">
                                <a class="btn btn-outline-light" href="account_management.php?auth=login">Back to Login</a>
                                <a class="btn btn-outline-light" href="index.php">Back to Homepage</a>
                            </div>
                        </form>
                        <script>
                            (function () {
                                var form = document.getElementById('signupForm');
                                if (!form) return;
                                var password = document.getElementById('signupPassword');
                                var confirm = document.getElementById('signupConfirmPassword');
                                var passwordFeedback = document.getElementById('signupPasswordFeedback');
                                var confirmFeedback = document.getElementById('signupConfirmFeedback');

                                function getPasswordError(value) {
                                    var passwordValue = String(value || '');
                                    if (passwordValue.length < 8) return 'Password must be at least 8 characters long.';
                                    if (!/[A-Z]/.test(passwordValue)) return 'Password must include at least 1 uppercase letter.';
                                    if (!/[a-z]/.test(passwordValue)) return 'Password must include at least 1 lowercase letter.';
                                    if (!/\d/.test(passwordValue)) return 'Password must include at least 1 number.';
                                    if (!/[^A-Za-z\d]/.test(passwordValue)) return 'Password must include at least 1 special character.';
                                    return '';
                                }

                                function setFeedback(element, message) {
                                    if (!element) return;
                                    element.textContent = message;
                                    element.classList.toggle('d-none', message === '');
                                }

                                function syncPasswordValidation() {
                                    if (!password || !confirm) return true;

                                    var passwordMessage = getPasswordError(password.value);
                                    password.setCustomValidity(passwordMessage);
                                    password.classList.toggle('is-invalid', passwordMessage !== '');
                                    setFeedback(passwordFeedback, passwordMessage);

                                    var confirmMessage = '';
                                    if (confirm.value !== '' && password.value !== confirm.value) {
                                        confirmMessage = 'Passwords do not match.';
                                    }
                                    confirm.setCustomValidity(confirmMessage);
                                    confirm.classList.toggle('is-invalid', confirmMessage !== '');
                                    setFeedback(confirmFeedback, confirmMessage);

                                    return passwordMessage === '' && confirmMessage === '';
                                }

                                form.addEventListener('submit', function (event) {
                                    var valid = syncPasswordValidation();
                                    if (!form.checkValidity() || !valid) {
                                        event.preventDefault();
                                        event.stopPropagation();
                                        form.classList.add('was-validated');
                                        if (typeof form.reportValidity === 'function') {
                                            form.reportValidity();
                                        }
                                    }
                                });

                                if (password) {
                                    password.addEventListener('input', syncPasswordValidation);
                                }
                                if (confirm) {
                                    confirm.addEventListener('input', syncPasswordValidation);
                                }

                                var showSignup = document.getElementById('showSignupPasswords');
                                if (showSignup) {
                                    showSignup.addEventListener('change', function () {
                                        var inputType = showSignup.checked ? 'text' : 'password';
                                        if (password) password.type = inputType;
                                        if (confirm) confirm.type = inputType;
                                    });
                                }
                            })();
                        </script>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="auth-shell auth-shell-login-left">
            <div class="auth-card auth-card-login card border-0">
                <div class="card-body p-4 p-md-5">
                    <div class="auth-login-brand text-center mb-4">
                        <img class="auth-login-logo mb-3" src="<?php echo e($authLogoPath); ?>" alt="Minor Basilica Logo">
                        <h5 class="auth-login-title mb-2">Minor Basilica Information System</h5>
                        <p class="auth-login-subtitle mb-0">Login to your account to continue</p>
                    </div>
                    <form method="POST" action="login.php" class="auth-login-form" data-suppress-alerts="true">
                        <label class="form-label">Email</label>
                        <input class="form-control mb-3" type="email" name="email" placeholder="Enter your email" required>
                        <label class="form-label">Password</label>
                        <div class="auth-password-wrap mb-4">
                            <input id="loginPassword" class="form-control" type="password" name="password" placeholder="Enter your password" required>
                            <button class="auth-pass-toggle" type="button" aria-label="Show password" data-auth-toggle="loginPassword">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="showLoginPassword">
                            <label class="form-check-label" for="showLoginPassword">Show password</label>
                        </div>
                        <div class="d-flex justify-content-end mb-3">
                            <a class="link-light small fw-semibold text-decoration-none" href="forgot_password.php">Forgot password?</a>
                        </div>
                        <button class="btn auth-login-btn w-100 mb-3" type="submit">Login</button>
                        <div class="d-flex gap-2 auth-actions auth-login-links">
                            <a class="btn btn-outline-light" href="account_management.php?auth=register">Sign Up</a>
                            <a class="btn btn-outline-light" href="index.php">Back to Homepage</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <script>
            (function () {
                var toggles = document.querySelectorAll('[data-auth-toggle]');
                toggles.forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var target = document.getElementById(btn.getAttribute('data-auth-toggle'));
                        if (!target) return;
                        var isPassword = target.type === 'password';
                        target.type = isPassword ? 'text' : 'password';
                        btn.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
                        btn.innerHTML = isPassword ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
                    });
                });

                var checkbox = document.getElementById('showLoginPassword');
                var loginPassword = document.getElementById('loginPassword');
                if (checkbox && loginPassword) {
                    checkbox.addEventListener('change', function () {
                        loginPassword.type = checkbox.checked ? 'text' : 'password';
                    });
                }
            })();
        </script>
    <?php endif; ?>
<?php else: ?>
    <div class="account-modern">
        <section class="profile-hero mb-4">
            <div class="profile-cover"></div>
            <div class="profile-head p-3 p-md-4">
                <div class="profile-avatar-wrap">
                    <?php if (!empty($user['avatar_path'])): ?>
                        <img class="profile-avatar" src="<?php echo e($user['avatar_path']); ?>" alt="Avatar">
                    <?php else: ?>
                        <div class="profile-avatar profile-avatar-fallback"><?php echo e($avatarInitial); ?></div>
                    <?php endif; ?>
                </div>
                <div class="profile-main">
                    <h3 class="mb-1"><?php echo e($displayName); ?></h3>
                    <p class="profile-sub mb-0"><?php echo e($bioText); ?></p>
                </div>
                <button class="btn btn-info profile-edit-btn" type="button" data-bs-toggle="modal" data-bs-target="#editAccountModal">
                    Edit
                </button>
            </div>
        </section>

        <div class="row g-4">
            <div class="col-lg-8">
                <section class="account-panel p-3 p-md-4 mb-4">
                    <h4 class="mb-3">About</h4>
                    <p class="mb-0"><?php echo nl2br(e($aboutText)); ?></p>
                </section>

                <section class="account-panel p-3 p-md-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Notifications</h5>
                        <form method="POST" class="d-flex gap-2">
                            <input type="hidden" name="mark_read" value="1">
                            <button class="btn btn-sm btn-outline-light" type="submit">Mark all read</button>
                            <?php if (!$showAllNotifications && $notificationTotal > 5): ?>
                                <a class="btn btn-sm btn-outline-info" href="account_management.php?notifications=all">View More</a>
                            <?php elseif ($showAllNotifications && $notificationTotal > 5): ?>
                                <a class="btn btn-sm btn-outline-info" href="account_management.php">Show Less</a>
                            <?php endif; ?>
                        </form>
                    </div>
                    <?php if (!$notifications): ?>
                        <p class="mb-0">No notifications yet.</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($notifications as $n): ?>
                                <div class="list-group-item bg-transparent border-secondary-subtle">
                                    <div class="small <?php echo $n['is_read'] ? 'text-secondary' : 'text-info'; ?>"><?php echo e($n['created_at']); ?></div>
                                    <div><?php echo e($n['message']); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

            </div>

            <div class="col-lg-4">
                <section class="account-panel p-3 p-md-4">
                    <h4 class="mb-3">Account</h4>
                    <div class="account-line"><strong>Joined</strong><span><?php echo e($joinedDate); ?></span></div>
                    <div class="account-line"><strong>Last Activity</strong><span><?php echo e($lastActivity); ?></span></div>
                    <div class="account-line"><strong>Role</strong><span class="text-uppercase"><?php echo e($accountRoleLabel); ?></span></div>
                    <?php if (is_admin_or_staff($user)): ?>
                        <hr>
                        <h6 class="mb-2">New Registered Users</h6>
                        <?php if (!$newRegisteredUsers): ?>
                            <small class="text-secondary">No newly registered users.</small>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($newRegisteredUsers as $ru): ?>
                                    <div class="list-group-item bg-transparent border-secondary-subtle px-0">
                                        <div class="small text-secondary"><?php echo e(date('M d, Y h:i A', strtotime($ru['created_at']))); ?></div>
                                        <div><?php echo e($ru['full_name'] ?: $ru['email']); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </div>
    <div class="modal fade profile-edit-modal" id="editAccountModal" tabindex="-1" aria-labelledby="editAccountModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data" id="editAccountForm" novalidate>
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title" id="editAccountModalLabel">Edit Profile</h5>
                            <div class="form-text">Update your profile details and save when everything looks right.</div>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="update_profile" value="1">
                        <label class="form-label">Avatar</label>
                        <input class="form-control mb-3" type="file" name="avatar" accept="image/*">
                        <label class="form-label">Full Name</label>
                        <input class="form-control mb-3" type="text" name="full_name" id="editFullName" value="<?php echo e($user['full_name'] ?? ''); ?>" required>
                        <label class="form-label">Email</label>
                        <input class="form-control mb-3" type="email" name="email" id="editEmail" value="<?php echo e($user['email']); ?>" required>
                        <label class="form-label">Role</label>
                        <select class="form-select mb-3" name="role">
                            <?php
                            $editableRoles = ['parishioner', 'minister', 'priest', 'staff', 'user'];
                            if (($user['role'] ?? '') === 'admin') {
                                $editableRoles[] = 'admin';
                            }
                            foreach ($editableRoles as $role):
                                $roleLabel = $role === 'user' ? 'PARISHIONER' : strtoupper($role);
                            ?>
                                <option value="<?php echo $role; ?>" <?php echo $user['role'] === $role ? 'selected' : ''; ?>><?php echo $roleLabel; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label class="form-label">Bio</label>
                        <textarea class="form-control mb-3" name="bio" rows="3" placeholder="Short description"><?php echo e($user['bio'] ?? ''); ?></textarea>
                        <label class="form-label">About</label>
                        <textarea class="form-control mb-0" name="about" rows="4" placeholder="Tell more about yourself"><?php echo e($user['about'] ?? ''); ?></textarea>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <div class="d-flex gap-2 flex-wrap">
                            <button class="btn btn-outline-light" type="button" data-bs-dismiss="modal">Cancel</button>
                            <?php if (is_admin_or_staff($user)): ?>
                                <a class="btn btn-outline-info" href="admin_dashboard.php">Go to Admin Dashboard</a>
                            <?php endif; ?>
                        </div>
                        <button class="btn btn-warning" type="submit">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var modalElement = document.getElementById('editAccountModal');
            var form = document.getElementById('editAccountForm');
            if (!modalElement || !form || typeof bootstrap === 'undefined' || !bootstrap.Modal) return;

            var modal = bootstrap.Modal.getOrCreateInstance(modalElement);
            var fullNameInput = document.getElementById('editFullName');
            var emailInput = document.getElementById('editEmail');

            function syncProfileValidation() {
                if (fullNameInput) {
                    var fullName = String(fullNameInput.value || '').trim();
                    fullNameInput.setCustomValidity(fullName === '' ? 'Please enter your full name.' : '');
                }

                if (emailInput) {
                    var email = String(emailInput.value || '').trim();
                    var validEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
                    emailInput.setCustomValidity(validEmail ? '' : 'Please enter a valid email address.');
                }
            }

            if (fullNameInput) {
                fullNameInput.addEventListener('input', syncProfileValidation);
            }
            if (emailInput) {
                emailInput.addEventListener('input', syncProfileValidation);
            }

            form.addEventListener('submit', function (event) {
                syncProfileValidation();
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                    form.classList.add('was-validated');
                    if (typeof form.reportValidity === 'function') {
                        form.reportValidity();
                    }
                }
            });

            <?php if ($openEditModal): ?>
            modal.show();
            <?php endif; ?>
        });
    </script>
<?php endif; ?>

<?php render_footer(); ?>


