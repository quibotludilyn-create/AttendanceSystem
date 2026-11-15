<?php
/**
 * One-time database installer
 * Visit: https://your-railway-url/install.php
 * DELETE this file after running!
 */

require_once __DIR__ . '/config/database.php';
$conn = getConnection();

$sql = file_get_contents(__DIR__ . '/database.sql');

// Split by semicolon and run each statement
$statements = array_filter(array_map('trim', explode(';', $sql)));
$success = 0;
$errors  = [];

foreach ($statements as $stmt) {
    if (empty($stmt) || str_starts_with($stmt, '--') || str_starts_with($stmt, '/*')) continue;
    if ($conn->query($stmt) === true) {
        $success++;
    } else {
        // Ignore "already exists" errors
        if (!str_contains($conn->error, 'already exists') && !str_contains($conn->error, 'Duplicate')) {
            $errors[] = $conn->error . ' — ' . substr($stmt, 0, 80);
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head><title>DB Install</title><style>body{font-family:sans-serif;padding:30px;}</style></head>
<body>
<h2>Database Installation</h2>
<?php if (empty($errors)): ?>
    <p style="color:green;">✅ Done! <?= $success ?> statements executed successfully.</p>
<?php else: ?>
    <p style="color:green;">✅ <?= $success ?> statements OK.</p>
    <p style="color:orange;">⚠️ <?= count($errors) ?> warnings (may be ignorable):</p>
    <ul><?php foreach($errors as $e): ?><li style="font-size:0.85rem"><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
<?php endif; ?>
<p><strong>⚠️ Delete install.php now for security!</strong></p>
</body>
</html>
