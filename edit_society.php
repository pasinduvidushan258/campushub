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

$foundedInput = $society['founded_date'] ?? '';
if (empty($foundedInput) && !empty($society['created_at'])) {
    $foundedInput = date('Y-m-d', strtotime($society['created_at']));
}

$adminEmail = '';
$adminEmailStmt = $pdo->prepare('SELECT email FROM users WHERE id = ? LIMIT 1');
$adminEmailStmt->execute([(int) $society['admin_id']]);
$adminEmailRaw = trim((string) $adminEmailStmt->fetchColumn());
if ($adminEmailRaw !== '') {
    $adminEmail = htmlspecialchars($adminEmailRaw);
}

include 'includes/header.php';
?>

<link rel="stylesheet" href="assets/css/society.css">

<div class="soc-container soc-edit-page">
    <div class="soc-edit-header">
        <div>
            <h1><i class="fas fa-cog"></i> Manage Society</h1>
        </div>
        <a href="society_dashboard.php?id=<?= $society_id ?>" class="btn-secondary soc-edit-back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>

    <form action="update_society_info.php" method="POST" class="soc-edit-form-shell">
        <input type="hidden" name="society_id" value="<?= $society_id ?>">

        <section class="info-card soc-edit-card">
            <h3><i class="fas fa-info-circle"></i> About</h3>
            <div class="soc-edit-fields">
                <div class="soc-edit-field full-row">
                    <label>Faculty</label>
                    <input type="text" name="faculty" value="<?= htmlspecialchars($society['faculty'] ?? '') ?>">
                </div>

                <div class="soc-edit-field full-row">
                    <label>Description</label>
                    <textarea name="description" rows="4"><?= htmlspecialchars($society['description'] ?? '') ?></textarea>
                </div>

                <div class="soc-edit-field full-row">
                    <label>Vision</label>
                    <textarea name="vision" rows="2"><?= htmlspecialchars($society['vision'] ?? '') ?></textarea>
                </div>

                <div class="soc-edit-field full-row">
                    <label>Mission</label>
                    <textarea name="mission" rows="2"><?= htmlspecialchars($society['mission'] ?? '') ?></textarea>
                </div>
            </div>
        </section>

        <section class="info-card soc-edit-card">
            <h3><i class="fas fa-address-card"></i> Contact &amp; Details</h3>
            <div class="soc-edit-fields two-cols">
                <div class="soc-edit-field full-row">
                    <label>Founded Date</label>
                    <input type="date" name="founded_date" value="<?= htmlspecialchars($foundedInput) ?>">
                </div>

                <div class="soc-edit-field full-row">
                    <label>Address</label>
                    <input type="text" name="address" value="<?= htmlspecialchars($society['address'] ?? '') ?>">
                </div>

                <div class="soc-edit-field full-row">
                    <label>Admin Email</label>
                    <input type="text" value="<?= $adminEmail ?: 'Not available' ?>" readonly>
                </div>

                <div class="soc-edit-field">
                    <label>Primary Email</label>
                    <input type="email" name="email_1" value="<?= htmlspecialchars($society['email_1'] ?? '') ?>">
                </div>

                <div class="soc-edit-field">
                    <label>Secondary Email</label>
                    <input type="email" name="email_2" value="<?= htmlspecialchars($society['email_2'] ?? '') ?>">
                </div>

                <div class="soc-edit-field">
                    <label>Contact Number</label>
                    <input type="text" name="contact_number" value="<?= htmlspecialchars($society['contact_number'] ?? '') ?>">
                </div>

                <div class="soc-edit-field">
                    <label>Website URL</label>
                    <input type="url" name="website_url" value="<?= htmlspecialchars($society['website_url'] ?? '') ?>" placeholder="https://">
                </div>

                <div class="soc-edit-field">
                    <label>Facebook URL</label>
                    <input type="url" name="facebook_url" value="<?= htmlspecialchars($society['facebook_url'] ?? '') ?>" placeholder="https://">
                </div>

                <div class="soc-edit-field">
                    <label>Instagram URL</label>
                    <input type="url" name="instagram_link" value="<?= htmlspecialchars($society['instagram_link'] ?? '') ?>" placeholder="https://">
                </div>
            </div>
        </section>

        <div class="soc-edit-actions">
            <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Save Changes</button>
            <a href="society_dashboard.php?id=<?= $society_id ?>" class="btn-secondary">Cancel</a>
        </div>
    </form>
</div>
