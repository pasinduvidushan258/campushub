<?php
// edit_society.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'config/database.php';
require_once 'includes/function.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$society_id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM societies WHERE id = ?");
$stmt->execute([$society_id]);
$society = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$society || (int) $society['admin_id'] !== (int) getUserId()) {
    header("Location: societies.php");
    exit();
}

$has_logo  = !empty($society['logo_path']) && file_exists('assets/images/uploads/' . $society['logo_path']);
$has_cover = !empty($society['cover_path']) && file_exists('assets/images/uploads/' . $society['cover_path']);
$logo_url  = $has_logo ? 'assets/images/uploads/' . htmlspecialchars($society['logo_path']) : '';
$cover_url = $has_cover ? 'assets/images/uploads/' . htmlspecialchars($society['cover_path']) : '';

include 'includes/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit <?= htmlspecialchars($society['society_name']) ?> | CampusHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/society.css">
</head>
<body>

<div class="soc-container">

    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px;">
        <h1 style="color:#fff; font-size:1.5rem;"><i class="fas fa-cog"></i> Manage Society</h1>
        <a href="society_dashboard.php?id=<?= $society_id ?>" class="btn-secondary"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>

    <!-- Photos -->
    <div class="info-card">
        <h3><i class="fas fa-images"></i> Photos</h3>

        <div style="display:flex; flex-wrap:wrap; gap:24px; margin-top:14px;">

            <div style="flex:1; min-width:220px;">
                <p style="color:#b0b3b8; margin-bottom:8px; font-weight:600;">Cover Photo</p>
                <?php if ($has_cover): ?>
                    <img src="<?= $cover_url ?>" alt="Cover" style="width:100%; height:140px; object-fit:cover; border-radius:10px; border:1px solid rgba(255,255,255,0.08);">
                    <form action="delete_society_photo.php" method="POST" onsubmit="return confirm('Delete this cover photo?');" style="margin-top:8px;">
                        <input type="hidden" name="society_id" value="<?= $society_id ?>">
                        <input type="hidden" name="photo_type" value="cover">
                        <button type="submit" class="btn-secondary" style="background:#3a1d1d; color:#ef4444; width:100%;">
                            <i class="fas fa-trash"></i> Delete Cover
                        </button>
                    </form>
                <?php else: ?>
                    <div class="soc-empty-state" style="padding:24px;"><i class="fas fa-image"></i><p style="margin-top:8px;">No cover photo</p></div>
                <?php endif; ?>
            </div>

            <div style="flex:1; min-width:220px;">
                <p style="color:#b0b3b8; margin-bottom:8px; font-weight:600;">Logo</p>
                <?php if ($has_logo): ?>
                    <img src="<?= $logo_url ?>" alt="Logo" style="width:140px; height:140px; object-fit:cover; border-radius:50%; border:1px solid rgba(255,255,255,0.08);">
                    <form action="delete_society_photo.php" method="POST" onsubmit="return confirm('Delete this logo?');" style="margin-top:8px; max-width:140px;">
                        <input type="hidden" name="society_id" value="<?= $society_id ?>">
                        <input type="hidden" name="photo_type" value="avatar">
                        <button type="submit" class="btn-secondary" style="background:#3a1d1d; color:#ef4444; width:100%;">
                            <i class="fas fa-trash"></i> Delete Logo
                        </button>
                    </form>
                <?php else: ?>
                    <div class="soc-empty-state" style="padding:24px;"><i class="fas fa-landmark"></i><p style="margin-top:8px;">No logo</p></div>
                <?php endif; ?>
            </div>
        </div>
        <p style="color:#888; font-size:0.85rem; margin-top:14px;">
            To upload a new cover or logo, go back to the dashboard and click the camera icon on the cover/logo.
        </p>
    </div>

    <!-- About / Contact & Details form -->
    <form action="update_society_info.php" method="POST">
        <input type="hidden" name="society_id" value="<?= $society_id ?>">

        <div class="info-card">
            <h3><i class="fas fa-info-circle"></i> About</h3>
            <div style="margin-top:12px;">
                <label style="color:#b0b3b8; font-size:0.85rem; display:block; margin-bottom:6px;">Faculty</label>
                <input type="text" name="faculty" value="<?= htmlspecialchars($society['faculty'] ?? '') ?>"
                       style="width:100%; padding:10px 12px; border-radius:8px; background:#242526; border:1px solid rgba(255,255,255,0.1); color:#fff; margin-bottom:14px;">

                <label style="color:#b0b3b8; font-size:0.85rem; display:block; margin-bottom:6px;">Description</label>
                <textarea name="description" rows="4"
                          style="width:100%; padding:10px 12px; border-radius:8px; background:#242526; border:1px solid rgba(255,255,255,0.1); color:#fff; margin-bottom:14px; resize:vertical;"><?= htmlspecialchars($society['description'] ?? '') ?></textarea>

                <label style="color:#b0b3b8; font-size:0.85rem; display:block; margin-bottom:6px;">Vision</label>
                <textarea name="vision" rows="2"
                          style="width:100%; padding:10px 12px; border-radius:8px; background:#242526; border:1px solid rgba(255,255,255,0.1); color:#fff; margin-bottom:14px; resize:vertical;"><?= htmlspecialchars($society['vision'] ?? '') ?></textarea>

                <label style="color:#b0b3b8; font-size:0.85rem; display:block; margin-bottom:6px;">Mission</label>
                <textarea name="mission" rows="2"
                          style="width:100%; padding:10px 12px; border-radius:8px; background:#242526; border:1px solid rgba(255,255,255,0.1); color:#fff; resize:vertical;"><?= htmlspecialchars($society['mission'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="info-card">
            <h3><i class="fas fa-address-card"></i> Contact &amp; Details</h3>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-top:12px;">

                <div style="grid-column:1 / -1;">
                    <label style="color:#b0b3b8; font-size:0.85rem; display:block; margin-bottom:6px;">Founded Date</label>
                    <input type="date" name="founded_date" value="<?= htmlspecialchars($society['founded_date'] ?? '') ?>"
                           style="width:100%; padding:10px 12px; border-radius:8px; background:#242526; border:1px solid rgba(255,255,255,0.1); color:#fff;">
                </div>

                <div style="grid-column:1 / -1;">
                    <label style="color:#b0b3b8; font-size:0.85rem; display:block; margin-bottom:6px;">Address</label>
                    <input type="text" name="address" value="<?= htmlspecialchars($society['address'] ?? '') ?>"
                           style="width:100%; padding:10px 12px; border-radius:8px; background:#242526; border:1px solid rgba(255,255,255,0.1); color:#fff;">
                </div>

                <div>
                    <label style="color:#b0b3b8; font-size:0.85rem; display:block; margin-bottom:6px;">Primary Email</label>
                    <input type="email" name="email_1" value="<?= htmlspecialchars($society['email_1'] ?? '') ?>"
                           style="width:100%; padding:10px 12px; border-radius:8px; background:#242526; border:1px solid rgba(255,255,255,0.1); color:#fff;">
                </div>

                <div>
                    <label style="color:#b0b3b8; font-size:0.85rem; display:block; margin-bottom:6px;">Secondary Email</label>
                    <input type="email" name="email_2" value="<?= htmlspecialchars($society['email_2'] ?? '') ?>"
                           style="width:100%; padding:10px 12px; border-radius:8px; background:#242526; border:1px solid rgba(255,255,255,0.1); color:#fff;">
                </div>

                <div>
                    <label style="color:#b0b3b8; font-size:0.85rem; display:block; margin-bottom:6px;">Contact Number</label>
                    <input type="text" name="contact_number" value="<?= htmlspecialchars($society['contact_number'] ?? '') ?>"
                           style="width:100%; padding:10px 12px; border-radius:8px; background:#242526; border:1px solid rgba(255,255,255,0.1); color:#fff;">
                </div>

                <div>
                    <label style="color:#b0b3b8; font-size:0.85rem; display:block; margin-bottom:6px;">Website URL</label>
                    <input type="url" name="website_url" value="<?= htmlspecialchars($society['website_url'] ?? '') ?>" placeholder="https://"
                           style="width:100%; padding:10px 12px; border-radius:8px; background:#242526; border:1px solid rgba(255,255,255,0.1); color:#fff;">
                </div>

                <div>
                    <label style="color:#b0b3b8; font-size:0.85rem; display:block; margin-bottom:6px;">Facebook URL</label>
                    <input type="url" name="facebook_url" value="<?= htmlspecialchars($society['facebook_url'] ?? '') ?>" placeholder="https://"
                           style="width:100%; padding:10px 12px; border-radius:8px; background:#242526; border:1px solid rgba(255,255,255,0.1); color:#fff;">
                </div>

                <div>
                    <label style="color:#b0b3b8; font-size:0.85rem; display:block; margin-bottom:6px;">Instagram URL</label>
                    <input type="url" name="instagram_link" value="<?= htmlspecialchars($society['instagram_link'] ?? '') ?>" placeholder="https://"
                           style="width:100%; padding:10px 12px; border-radius:8px; background:#242526; border:1px solid rgba(255,255,255,0.1); color:#fff;">
                </div>
            </div>
        </div>

        <div style="display:flex; gap:10px; margin-bottom:40px;">
            <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Save Changes</button>
            <a href="society_dashboard.php?id=<?= $society_id ?>" class="btn-secondary">Cancel</a>
        </div>
    </form>

</div>

</body>
</html>
