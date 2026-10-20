<?php
// shared/settings.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/db_connect.php';
include ROOT_PATH . '/includes/header.php';

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: " . ROOT_PATH . "/index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$message = "";
$error = "";

// --- HANDLE PROFILE & IMAGE UPDATE ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    
    $current_data = $conn->query("SELECT profile_image FROM users WHERE user_id = $user_id")->fetch_assoc();
    $profile_image = $current_data['profile_image'];

    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $filename = $_FILES['profile_pic']['name'];
        $filesize = $_FILES['profile_pic']['size'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            $error = "Invalid file format. Only JPG, PNG, and WebP are allowed.";
        } elseif ($filesize > 2 * 1024 * 1024) { 
            $error = "File is too large. Max 2MB allowed.";
        } else {
            $new_filename = "user_" . $user_id . "_" . time() . "." . $ext;
            $upload_dir = ROOT_PATH . "/assets/uploads/";
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $upload_dir . $new_filename)) {
                if (!empty($profile_image) && file_exists($upload_dir . $profile_image)) {
                    unlink($upload_dir . $profile_image);
                }
                $profile_image = $new_filename; 
            } else {
                $error = "Failed to upload image.";
            }
        }
    }

    if (empty($error)) {
        if (empty($name) || empty($email)) {
            $error = "Name and Email are required.";
        } else {
            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, profile_image = ? WHERE user_id = ?");
            $stmt->bind_param("sssi", $name, $email, $profile_image, $user_id);
            if ($stmt->execute()) {
                $_SESSION['name'] = $name;
                $message = "Profile updated successfully!";
            } else {
                $error = "Database error: " . $conn->error;
            }
            $stmt->close();
        }
    }
}

// --- HANDLE PASSWORD CHANGE ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if (empty($current) || empty($new) || empty($confirm)) {
        $error = "Please fill in all password fields.";
    } elseif ($new !== $confirm) {
        $error = "New password and confirmation do not match.";
    } elseif (strlen($new) < 5) {
        $error = "New password must be at least 5 characters.";
    } else {
        $check = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
        $check->bind_param("i", $user_id);
        $check->execute();
        $row = $check->get_result()->fetch_assoc();
        
        if ($row['password'] !== $current) {
            $error = "Current password is incorrect.";
        } else {
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $stmt->bind_param("si", $new, $user_id);
            if ($stmt->execute()) {
                $message = "Password changed successfully!";
            } else {
                $error = "Failed to change password.";
            }
            $stmt->close();
        }
        $check->close();
    }
}

$user_data = $conn->query("SELECT * FROM users WHERE user_id = $user_id")->fetch_assoc();
$current_image = $user_data['profile_image'];
$image_path = !empty($current_image) ? "../assets/uploads/" . $current_image : null;
?>

<style>
    :root {
        --ep-primary: #0f172a;
        --ep-accent: #2563eb;
        --ep-accent-hover: #1d4ed8;
        --ep-bg-slate: #f8fafc;
        --ep-card-bg: #ffffff;
        --ep-border-color: #e2e8f0;
        --ep-text-title: #0f172a;
        --ep-text-sub: #475569;
        --ep-text-muted: #94a3b8;
    }

    /* DARK MODE OVERRIDES (SOFT SLATE) */
    body.dark-mode {
        --ep-primary: #ffffff;
        --ep-bg-slate: #334155; /* Lighter slate background */
        --ep-card-bg: #1e293b; /* Lighter card background */
        --ep-border-color: #475569; 
        --ep-text-title: #f8fafc;
        --ep-text-sub: #cbd5e1;
        --ep-text-muted: #94a3b8;
        background-color: var(--ep-bg-slate);
        color: #f8fafc;
    }
    
    body.dark-mode .ep-header-banner, 
    body.dark-mode .ep-main-panel, 
    body.dark-mode .ep-sidebar-card,
    body.dark-mode .ep-control,
    body.dark-mode .ep-action-tile { 
        background-color: var(--ep-card-bg) !important; 
        color: var(--ep-text-title) !important;
        border-color: var(--ep-border-color) !important;
    }
    
    body.dark-mode .ep-tabs-bar { background: #0f172a !important; }
    body.dark-mode .ep-tabs-bar a { color: #94a3b8; }
    body.dark-mode .ep-tabs-bar a.active { 
        background: var(--ep-card-bg); 
        color: var(--ep-accent) !important; 
    }
    body.dark-mode .ep-preferences-box { background: #334155 !important; }
    
    /* Make labels white in dark mode */
    body.dark-mode .ep-field-label,
    body.dark-mode .ep-section-heading,
    body.dark-mode .ep-sidebar-heading {
        color: #ffffff !important;
    }
    
    /* Form fields contrast */
    body.dark-mode .ep-control {
        background-color: #0f172a !important; 
        color: #ffffff !important;
    }
    body.dark-mode .ep-control:focus {
        background-color: #1e293b !important;
        border-color: var(--ep-accent) !important;
    }
    body.dark-mode .ep-control:disabled {
        background-color: #1e293b !important;
        color: #64748b !important;
    }

    /* Dark mode overrides for switches and text */
    body.dark-mode .text-muted { color: #94a3b8 !important; }
    body.dark-mode .text-dark { color: #f8fafc !important; }
    body.dark-mode .form-check-label { color: #cbd5e1 !important; }


    /* =========================================
       MAIN UI STYLES (LIGHT MODE DEFAULTS)
       ========================================= */
    .ep-settings-container { max-width: 1240px; margin: 0 auto; padding-bottom: 2.5rem; }
    
    .ep-header-banner { 
        background: var(--ep-card-bg); 
        border: 1px solid var(--ep-border-color); 
        border-radius: 12px; 
        padding: 1.5rem 2rem; 
        margin-bottom: 1.5rem; 
        box-shadow: 0 1px 3px rgba(0,0,0,0.02); 
    }
    .ep-header-title { font-size: 1.35rem; font-weight: 700; color: var(--ep-text-title); margin: 0; }
    .ep-header-sub { font-size: 0.875rem; color: var(--ep-text-sub); margin-top: 0.25rem; }
    
    .ep-role-pill { 
        background: #f1f5f9; 
        color: #334155; 
        border: 1px solid #cbd5e1; 
        font-weight: 600; 
        font-size: 0.8rem; 
        padding: 0.4rem 0.9rem; 
        border-radius: 6px; 
    }
    
    .ep-grid-layout { display: flex; gap: 1.5rem; }
    .ep-main-panel { flex: 1; background: var(--ep-card-bg); border: 1px solid var(--ep-border-color); border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.03); }
    
    /* Tabs */
    .ep-tabs-bar { display: flex; background: #f8fafc; border-bottom: 1px solid var(--ep-border-color); padding: 0 1.25rem; overflow-x: auto; }
    .ep-tabs-bar a { padding: 1rem 1.25rem; font-size: 0.875rem; font-weight: 600; color: var(--ep-text-sub); text-decoration: none; border-bottom: 2px solid transparent; display: flex; align-items: center; gap: 0.5rem; white-space: nowrap; cursor: pointer; transition: 0.2s; }
    .ep-tabs-bar a.active { color: var(--ep-accent); border-bottom-color: var(--ep-accent); background: #ffffff; }
    .ep-tabs-bar a:hover:not(.active) { color: var(--ep-text-title); }
    
    .ep-section-body { padding: 2rem; }
    .ep-section-heading { font-size: 1.05rem; font-weight: 700; color: var(--ep-text-title); margin-bottom: 0.25rem; display: flex; align-items: center; gap: 0.5rem; }
    .ep-section-sub { font-size: 0.825rem; color: var(--ep-text-sub); margin-bottom: 1.75rem; }
    
    /* Avatar */
    .ep-avatar-wrapper { display: flex; flex-direction: column; align-items: center; padding-right: 2rem; border-right: 1px solid var(--ep-border-color); }
    .ep-avatar-box { position: relative; width: 128px; height: 128px; margin-bottom: 1rem; }
    .avatar-img-large { width: 128px; height: 128px; border-radius: 50%; object-fit: cover; border: 3px solid #ffffff; box-shadow: 0 4px 12px rgba(0,0,0,0.08); background: #f1f5f9; display: flex; align-items: center; justify-content: center; font-size: 2.75rem; color: var(--ep-accent); font-weight: 700; }
    .ep-camera-badge { position: absolute; bottom: 4px; right: 4px; background: var(--ep-accent); color: #ffffff; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 2px solid #ffffff; cursor: pointer; }
    
    .btn-change-photo { background: #ffffff; border: 1px solid var(--ep-border-color); color: var(--ep-text-title); font-size: 0.825rem; font-weight: 600; padding: 0.45rem 1rem; border-radius: 6px; cursor: pointer; transition: 0.15s; }
    .btn-change-photo:hover { background: #f8fafc; }

    /* Form Controls */
    .ep-field-label { font-size: 0.8rem; font-weight: 600; color: var(--ep-text-title); margin-bottom: 0.35rem; text-transform: uppercase; letter-spacing: 0.4px; }
    .ep-control { font-size: 0.875rem; padding: 0.6rem 0.85rem; border-radius: 6px; border: 1px solid var(--ep-border-color); background-color: #ffffff; color: var(--ep-text-title); transition: 0.2s; }
    .ep-control:focus { border-color: var(--ep-accent); box-shadow: 0 0 0 3px rgba(37,99,235,0.12); outline: none; }
    .ep-control:disabled { background-color: #f8fafc; color: var(--ep-text-sub); cursor: not-allowed; }

    /* Buttons */
    .btn-ep-primary { background-color: var(--ep-accent); color: #ffffff; font-weight: 600; font-size: 0.875rem; padding: 0.6rem 1.4rem; border-radius: 6px; border: none; transition: 0.2s; }
    .btn-ep-primary:hover { background-color: var(--ep-accent-hover); color: white; }
    .btn-ep-secondary { background-color: #ffffff; color: var(--ep-text-sub); border: 1px solid var(--ep-border-color); font-weight: 600; font-size: 0.875rem; padding: 0.6rem 1.4rem; border-radius: 6px; transition: 0.2s; }
    .btn-ep-secondary:hover { background-color: #f8fafc; }

    .ep-preferences-box { background: #f8fafc; border-top: 1px solid var(--ep-border-color); padding: 1.75rem 2rem; }
    
    /* Sidebar */
    .ep-sidebar { width: 310px; display: flex; flex-direction: column; gap: 1.25rem; }
    .ep-sidebar-card { background: var(--ep-card-bg); border: 1px solid var(--ep-border-color); border-radius: 12px; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
    .ep-sidebar-heading { font-size: 0.95rem; font-weight: 700; color: var(--ep-text-title); margin-bottom: 0.2rem; }
    .ep-sidebar-desc { font-size: 0.8rem; color: var(--ep-text-sub); margin-bottom: 1.25rem; }
    
    .ep-action-tile { display: flex; align-items: center; gap: 0.85rem; padding: 0.85rem 1rem; border: 1px solid var(--ep-border-color); border-radius: 8px; margin-bottom: 0.65rem; text-decoration: none; color: var(--ep-text-title); background: #ffffff; width: 100%; text-align: left; transition: 0.15s; }
    .ep-action-tile:hover { border-color: var(--ep-accent); box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
    .ep-tile-icon { width: 36px; height: 36px; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 0.95rem; flex-shrink: 0; }
    
    .ep-sys-row { display: flex; justify-content: space-between; align-items: center; font-size: 0.825rem; padding: 0.6rem 0; border-bottom: 1px solid var(--ep-border-color); }
    .ep-sys-row:last-child { border-bottom: none; }

    /* Theme Switch Buttons */
    .theme-btn-group { display: flex; gap: 10px; margin-top: 5px; }
    .theme-btn { flex: 1; padding: 10px; border: 1px solid var(--ep-border-color); border-radius: 8px; background: white; text-align: center; cursor: pointer; transition: 0.2s; color: #333; font-weight: 600; font-size: 0.85rem; }
    .theme-btn.active { border-color: var(--ep-accent); background: #eff6ff; color: var(--ep-accent); }
    
    /* Dark mode for theme buttons */
    body.dark-mode .theme-btn { background: #0f172a; color: #cbd5e1; border-color: #334155; }
    body.dark-mode .theme-btn.active { background: #1e3a8a; color: #ffffff; border-color: #3b82f6; }

        /* --- FIX DARK MODE MODAL INPUTS --- */
    body.dark-mode .modal-content {
        background-color: #1e293b !important;
        color: #f8fafc !important;
    }
    body.dark-mode .modal-header,
    body.dark-mode .modal-footer {
        background-color: #0f172a !important;
        border-color: #334155 !important;
    }
    body.dark-mode .modal-title {
        color: #ffffff !important;
    }
    body.dark-mode .modal-body {
        background-color: #1e293b !important;
    }
    /* Force modal input fields to have a dark background and white text */
    body.dark-mode .modal-body .form-control,
    body.dark-mode .modal-body .form-select {
        background-color: #0f172a !important;
        color: #ffffff !important;
        border: 1px solid #475569 !important;
    }
    body.dark-mode .modal-body .form-control:focus {
        background-color: #0f172a !important;
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25) !important;
    }
    /* Fix the labels inside the modal */
    body.dark-mode .modal-body .form-label,
    body.dark-mode .modal-body .ep-field-label {
        color: #cbd5e1 !important;
    }
    /* Fix the close button (X) */
    body.dark-mode .btn-close {
        filter: invert(1) grayscale(100%) brightness(200%);
    }
</style>

<div class="main-content ep-settings-container">
    
    <!-- Header Banner -->
    <div class="ep-header-banner d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
        <div>
            <h4 class="ep-header-title"><i class="fas fa-sliders-h text-primary me-2"></i> Settings</h4>
            <div class="ep-header-sub">Manage your account, preferences and system settings</div>
        </div>
        <div>
            <span class="ep-role-pill">
                <i class="fas fa-shield-alt me-1 text-primary"></i> <?php echo ucfirst($role); ?>
            </span>
        </div>
    </div>

    <!-- Alert Notifications -->
    <?php if (!empty($message)): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="ep-grid-layout">
        
        <!-- LEFT SIDE: MAIN SETTINGS -->
        <div class="ep-main-panel">
            
            <!-- TABS -->
            <div class="ep-tabs-bar">
                <a onclick="switchSettingsTab('profile', this)" class="active"><i class="fas fa-user-circle me-1"></i> Profile</a>
                <a onclick="switchSettingsTab('account', this)"><i class="fas fa-id-card me-1"></i> Account</a>
                <a onclick="switchSettingsTab('appearance', this)"><i class="fas fa-swatchbook me-1"></i> Appearance</a>
                <a onclick="switchSettingsTab('notifications', this)"><i class="fas fa-bell me-1"></i> Notifications</a>
                <a onclick="switchSettingsTab('security', this)"><i class="fas fa-shield-alt me-1"></i> Security</a>
                <a onclick="switchSettingsTab('privacy', this)"><i class="fas fa-user-shield me-1"></i> Privacy</a>
                <a onclick="switchSettingsTab('system', this)"><i class="fas fa-cogs me-1"></i> System</a>
            </div>

            <!-- 1. PROFILE TAB -->
            <div id="tab-profile" class="settings-tab-content">
                <div class="ep-section-body">
                    <div class="ep-section-heading"><i class="fas fa-user-edit text-primary"></i> Profile Information</div>
                    <div class="ep-section-sub">Update your personal account picture and basic profile parameters.</div>

                    <form method="POST" action="" enctype="multipart/form-data">
                        <div class="row g-0">
                            <div class="col-md-4 col-lg-3 ep-avatar-wrapper">
                                <div class="ep-avatar-box">
                                    <?php if ($image_path): ?>
                                        <img src="<?php echo $image_path; ?>" class="avatar-img-large" id="avatarPreview" alt="Profile">
                                    <?php else: ?>
                                        <div class="avatar-img-large" id="avatarPreview"><?php echo strtoupper(substr($user_data['name'], 0, 1)); ?></div>
                                    <?php endif; ?>
                                    <label for="fileUpload" class="ep-camera-badge"><i class="fas fa-camera" style="font-size: 0.75rem;"></i></label>
                                </div>
                                <label for="fileUpload" class="btn-change-photo mt-2 mb-2"><i class="fas fa-cloud-upload-alt me-1"></i> Change Photo</label>
                                <input type="file" id="fileUpload" name="profile_pic" class="d-none" accept="image/*" onchange="previewImage(event)">
                                <p class="text-muted text-center" style="font-size: 0.725rem; line-height: 1.3;">Formats: JPG, PNG, WEBP<br>Maximum file size: 2MB</p>
                            </div>

                            <div class="col-md-8 col-lg-9 ps-md-4">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="ep-field-label">Full Name <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control ep-control" value="<?php echo htmlspecialchars($user_data['name']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="ep-field-label">Username <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control ep-control" value="<?php echo htmlspecialchars($user_data['username']); ?>" disabled>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="ep-field-label">Email Address <span class="text-danger">*</span></label>
                                        <input type="email" name="email" class="form-control ep-control" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="ep-field-label">System Role</label>
                                        <input type="text" class="form-control ep-control" value="<?php echo ucfirst($user_data['role']); ?>" disabled>
                                    </div>
                                    <div class="col-12 mt-4">
                                        <div class="d-flex gap-2">
                                            <button type="submit" name="update_profile" class="btn btn-ep-primary"><i class="fas fa-check-circle me-1"></i> Save Changes</button>
                                            <button type="reset" class="btn btn-ep-secondary">Cancel</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 2. ACCOUNT TAB -->
            <div id="tab-account" class="settings-tab-content" style="display: none;">
                <div class="ep-section-body">
                    <div class="ep-section-heading"><i class="fas fa-id-card text-primary"></i> Account Details</div>
                    <div class="ep-section-sub">Manage your account identification and status.</div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="ep-field-label">Account ID</label>
                            <input type="text" class="form-control ep-control" value="ACC-<?php echo str_pad($user_id, 5, '0', STR_PAD_LEFT); ?>" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="ep-field-label">Account Status</label>
                            <input type="text" class="form-control ep-control" value="<?php echo htmlspecialchars($user_data['status']); ?>" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="ep-field-label">Date Joined</label>
                            <input type="text" class="form-control ep-control" value="<?php echo date('F d, Y', strtotime($user_data['created_at'])); ?>" disabled>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. APPEARANCE TAB -->
            <div id="tab-appearance" class="settings-tab-content" style="display: none;">
                <div class="ep-section-body">
                    <div class="ep-section-heading"><i class="fas fa-palette text-primary"></i> Display & Appearance</div>
                    <div class="ep-section-sub">Choose how the system looks for you.</div>
                    
                    <label class="ep-field-label mb-2">Theme Mode</label>
                    <div class="theme-btn-group">
                        <button type="button" class="theme-btn" id="btn-light" onclick="setTheme('light')"><i class="fas fa-sun me-2"></i> Light</button>
                        <button type="button" class="theme-btn" id="btn-dark" onclick="setTheme('dark')"><i class="fas fa-moon me-2"></i> Dark</button>
                        <button type="button" class="theme-btn" id="btn-system" onclick="setTheme('system')"><i class="fas fa-desktop me-2"></i> System</button>
                    </div>
                </div>
            </div>

            <!-- 4. NOTIFICATIONS TAB -->
            <div id="tab-notifications" class="settings-tab-content" style="display: none;">
                <div class="ep-section-body">
                    <div class="ep-section-heading"><i class="fas fa-bell text-primary"></i> Notifications</div>
                    <div class="ep-section-sub">Choose what alerts you want to receive.</div>
                    
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="notif-exam" checked>
                        <label class="form-check-label" for="notif-exam">Exam Announcements (New exams available)</label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="notif-results" checked>
                        <label class="form-check-label" for="notif-results">Result Published (When your grades are ready)</label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="notif-system">
                        <label class="form-check-label" for="notif-system">System Updates (Maintenance alerts)</label>
                    </div>
                </div>
            </div>

            <!-- 5. SECURITY TAB -->
            <div id="tab-security" class="settings-tab-content" style="display: none;">
                <div class="ep-section-body">
                    <div class="ep-section-heading"><i class="fas fa-shield-alt text-primary"></i> Security</div>
                    <div class="ep-section-sub">Manage your password and active sessions.</div>
                    <button class="btn btn-ep-primary" data-bs-toggle="modal" data-bs-target="#passwordModal">
                        <i class="fas fa-key me-2"></i> Change Password
                    </button>
                </div>
            </div>

            <!-- 6. PRIVACY TAB -->
            <div id="tab-privacy" class="settings-tab-content" style="display: none;">
                <div class="ep-section-body">
                    <div class="ep-section-heading"><i class="fas fa-user-shield text-primary"></i> Privacy</div>
                    <div class="ep-section-sub">Control who can see your profile information.</div>
                    
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="privacy-show-email" checked>
                        <label class="form-check-label" for="privacy-show-email">Show my Email to Teachers</label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="privacy-show-results" checked>
                        <label class="form-check-label" for="privacy-show-results">Allow classmates to see my results</label>
                    </div>
                </div>
            </div>

            <!-- 7. SYSTEM TAB -->
            <div id="tab-system" class="settings-tab-content" style="display: none;">
                <div class="ep-section-body">
                    <div class="ep-section-heading"><i class="fas fa-cogs text-primary"></i> System Preferences</div>
                    <div class="ep-section-sub">Language and timezone settings.</div>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="ep-field-label">Language</label>
                            <select class="form-select ep-control">
                                <option>English</option>
                                <option>Amharic</option>
                                <option>Oromo</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="ep-field-label">Timezone</label>
                            <select class="form-select ep-control">
                                <option>Africa/Addis_Ababa (EAT)</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- RIGHT SIDE: QUICK ACTIONS -->
        <div class="ep-sidebar">
            <div class="ep-sidebar-card">
                <div class="ep-sidebar-heading">Quick Actions</div>
                <div class="ep-sidebar-desc">Manage your account and data</div>
                
                <button class="ep-action-tile" data-bs-toggle="modal" data-bs-target="#passwordModal">
                    <div class="ep-tile-icon" style="background: #eff6ff; color: #2563eb;"><i class="fas fa-lock"></i></div>
                    <div>
                        <div style="font-size: 0.875rem; font-weight: 600;">Change Password</div>
                        <div style="font-size: 0.75rem; color: var(--ep-text-sub);">Update security credentials</div>
                    </div>
                </button>

                <button class="ep-action-tile" onclick="alert('Account deletion is disabled for demo purposes.')" style="border-color: #fecdd3;">
                    <div class="ep-tile-icon" style="background: #fff1f2; color: #e11d48;"><i class="fas fa-user-slash"></i></div>
                    <div>
                        <div style="font-size: 0.875rem; font-weight: 600; color: #e11d48;">Delete Account</div>
                        <div style="font-size: 0.75rem; color: var(--ep-text-sub);">Deactivate account data</div>
                    </div>
                </button>

                <button class="ep-action-tile" onclick="window.print()">
                    <div class="ep-tile-icon" style="background: #f0fdf4; color: #16a34a;"><i class="fas fa-file-download"></i></div>
                    <div>
                        <div style="font-size: 0.875rem; font-weight: 600;">Download Data</div>
                        <div style="font-size: 0.75rem; color: var(--ep-text-sub);">Print/Save profile</div>
                    </div>
                </button>
            </div>

            <div class="ep-sidebar-card">
                <div class="ep-sidebar-heading mb-3 d-flex align-items-center gap-2"><i class="fas fa-info-circle text-primary"></i> System Information</div>
                <div class="ep-sys-row"><span class="text-muted">Version</span><span class="fw-semibold text-dark">v1.0.0</span></div>
                <div class="ep-sys-row"><span class="text-muted">Last Login</span><span class="text-dark"><?php echo date('M d, Y h:i A'); ?></span></div>
                <div class="ep-sys-row"><span class="text-muted">Device</span><span class="text-dark">Windows 11 • Chrome</span></div>
                <div class="ep-sys-row"><span class="text-muted">Status</span><span class="text-success fw-semibold"><i class="fas fa-circle me-1" style="font-size: 0.45rem;"></i> Active</span></div>
            </div>
        </div>
    </div>
</div>

<!-- CHANGE PASSWORD MODAL -->
<div class="modal fade" id="passwordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 12px; border: none;">
            <div class="modal-header border-bottom py-3">
                <h5 class="modal-title fw-bold text-dark" style="font-size: 1.05rem;"><i class="fas fa-lock text-primary me-2"></i> Update Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="ep-field-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control ep-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="ep-field-label">New Password</label>
                        <input type="password" name="new_password" class="form-control ep-control" placeholder="Min 5 characters" required>
                    </div>
                    <div class="mb-3">
                        <label class="ep-field-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control ep-control" required>
                    </div>
                </div>
                <div class="modal-footer border-top py-2 px-4">
                    <button type="button" class="btn btn-ep-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="change_password" class="btn btn-ep-primary">Update Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // --- 1. TAB SWITCHING LOGIC ---
    function switchSettingsTab(tabName, element) {
        // Hide all tabs
        document.querySelectorAll('.settings-tab-content').forEach(tab => {
            tab.style.display = 'none';
        });
        
        // Remove active class from all tab buttons
        document.querySelectorAll('.ep-tabs-bar a').forEach(a => {
            a.classList.remove('active');
        });

        // Show the selected tab
        document.getElementById('tab-' + tabName).style.display = 'block';
        
        // Add active class to clicked button
        element.classList.add('active');
    }

    // --- 2. IMAGE PREVIEW LOGIC ---
    function previewImage(event) {
        const reader = new FileReader();
        reader.onload = function() {
            const output = document.getElementById('avatarPreview');
            if(output.tagName === 'DIV') {
                const newImg = document.createElement('img');
                newImg.src = reader.result;
                newImg.className = 'avatar-img-large';
                newImg.id = 'avatarPreview';
                output.parentNode.replaceChild(newImg, output);
            } else {
                output.src = reader.result;
            }
        };
        if(event.target.files[0]) {
            reader.readAsDataURL(event.target.files[0]);
        }
    }

    // --- 3. THEME SWITCHING LOGIC ---
    function setTheme(theme) {
        // Remove active from all buttons
        document.querySelectorAll('.theme-btn').forEach(btn => btn.classList.remove('active'));
        
        if(theme === 'dark') {
            document.body.classList.add('dark-mode');
            document.getElementById('btn-dark').classList.add('active');
            localStorage.setItem('exam_theme', 'dark');
        } else if(theme === 'light') {
            document.body.classList.remove('dark-mode');
            document.getElementById('btn-light').classList.add('active');
            localStorage.setItem('exam_theme', 'light');
        } else {
            // System Default
            document.body.classList.remove('dark-mode');
            document.getElementById('btn-system').classList.add('active');
            localStorage.setItem('exam_theme', 'system');
        }
    }

    // Check saved theme on load
    document.addEventListener('DOMContentLoaded', () => {
        const savedTheme = localStorage.getItem('exam_theme') || 'light';
        if(savedTheme === 'dark') {
            setTheme('dark');
        } else if (savedTheme === 'system') {
            setTheme('system');
        } else {
            setTheme('light');
        }
    });
</script>

<?php include ROOT_PATH . '/includes/footer.php'; ?>