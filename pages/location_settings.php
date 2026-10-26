<?php
require_once 'layout.php';
require_once '../config/database.php';
$conn = getConnection();

$success_msg = '';
$error_msg   = '';

// Save location settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_location'])) {
    $name    = $conn->real_escape_string(trim($_POST['loc_name']));
    $lat     = floatval($_POST['latitude']);
    $lon     = floatval($_POST['longitude']);
    $radius  = intval($_POST['radius']);
    $user_id = intval($_SESSION['user_id']);

    if ($lat == 0 || $lon == 0) {
        $error_msg = "Invalid coordinates. Please use the map picker.";
    } elseif ($radius < 10 || $radius > 5000) {
        $error_msg = "Radius must be between 10 and 5000 meters.";
    } else {
        // Deactivate all existing
        $conn->query("UPDATE location_settings SET is_active = 0");

        // Check if record exists
        $existing = $conn->query("SELECT id FROM location_settings LIMIT 1");
        if ($existing && $existing->num_rows > 0) {
            $row = $existing->fetch_assoc();
            $conn->query("UPDATE location_settings SET
                name='$name', latitude=$lat, longitude=$lon,
                radius_meters=$radius, is_active=1, created_by=$user_id
                WHERE id=" . $row['id']);
        } else {
            $conn->query("INSERT INTO location_settings (name, latitude, longitude, radius_meters, is_active, created_by)
                          VALUES ('$name', $lat, $lon, $radius, 1, $user_id)");
        }
        $success_msg = "Location settings saved successfully.";
    }
}

// Get current location
$current = $conn->query("SELECT * FROM location_settings WHERE is_active=1 LIMIT 1");
$loc = ($current && $current->num_rows > 0) ? $current->fetch_assoc() : null;
?>

<?php if ($success_msg): ?>
    <div class="alert alert-success">✅ <?= $success_msg ?></div>
<?php endif; ?>
<?php if ($error_msg): ?>
    <div class="alert alert-danger">❌ <?= $error_msg ?></div>
<?php endif; ?>

<div class="grid-2">
    <!-- ── Settings Form ── -->
    <div class="card">
        <div class="card-title">📍 Set Allowed Location</div>
        <form method="POST" id="location-form">

            <div class="form-group">
                <label>Location Name</label>
                <input type="text" name="loc_name" class="form-control"
                       value="<?= htmlspecialchars($loc['name'] ?? 'School Campus') ?>"
                       placeholder="e.g. School Campus, Room 101" required>
            </div>

            <div class="form-group">
                <label>Latitude</label>
                <input type="number" name="latitude" id="lat-input" class="form-control"
                       step="0.00000001"
                       value="<?= $loc['latitude'] ?? '' ?>"
                       placeholder="e.g. 14.59950" required>
            </div>

            <div class="form-group">
                <label>Longitude</label>
                <input type="number" name="longitude" id="lon-input" class="form-control"
                       step="0.00000001"
                       value="<?= $loc['longitude'] ?? '' ?>"
                       placeholder="e.g. 120.98422" required>
            </div>

        <div class="form-group">
            <label>Allowed Radius: <strong id="rad-lbl">100m</strong></label>
            <input type="range" name="radius" id="rad-range" min="1" max="500" step="1"
                   value="<?= $loc['radius_meters'] ?? 100 ?>"
                   oninput="document.getElementById('rad-lbl').textContent=this.value+'m'"
                   style="width:100%;margin-top:4px;">
            <div style="display:flex;justify-content:space-between;font-size:0.72rem;color:#aaa;margin-top:2px;"><span>1m (strict)</span><span>500m</span></div>
            <p style="color:#aaa;font-size:0.77rem;margin-top:4px;">
                Set 1–5m for indoor classroom, 10–50m for outdoor areas.
            </p>
        </div>

            <div class="form-group">
                <button type="button" onclick="useMyLocation()"
                        class="btn btn-info btn-block" style="margin-bottom:10px;">
                    📡 Use My Current Location
                </button>
                <div id="my-loc-status" style="font-size:0.82rem; color:#888; text-align:center;"></div>
            </div>

            <button type="submit" name="save_location" class="btn btn-primary btn-block"
                    style="padding:13px; font-size:1rem;">
                💾 Save Location Settings
            </button>
        </form>
    </div>

    <!-- ── Current + Map ── -->
    <div>
        <!-- Current Settings -->
        <div class="card">
            <div class="card-title">✅ Current Location Settings</div>
            <?php if ($loc): ?>
                <table style="font-size:0.9rem;">
                    <tr>
                        <td style="padding:8px; color:#666; width:110px;">Location:</td>
                        <td style="padding:8px; font-weight:600;"><?= htmlspecialchars($loc['name']) ?></td>
                    </tr>
                    <tr>
                        <td style="padding:8px; color:#666;">Latitude:</td>
                        <td style="padding:8px;"><?= $loc['latitude'] ?></td>
                    </tr>
                    <tr>
                        <td style="padding:8px; color:#666;">Longitude:</td>
                        <td style="padding:8px;"><?= $loc['longitude'] ?></td>
                    </tr>
                    <tr>
                        <td style="padding:8px; color:#666;">Radius:</td>
                        <td style="padding:8px;">
                            <span style="font-weight:600; color:#1a237e;">
                                <?= $loc['radius_meters'] ?> meters
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:8px; color:#666;">Status:</td>
                        <td style="padding:8px;">
                            <span class="badge badge-present">✅ Active</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:8px; color:#666;">Updated:</td>
                        <td style="padding:8px; color:#888; font-size:0.82rem;">
                            <?= $loc['updated_at'] ?>
                        </td>
                    </tr>
                </table>

                <!-- OpenStreetMap preview -->
                <div style="margin-top:14px; border-radius:10px; overflow:hidden; height:220px;">
                    <iframe
                        src="https://www.openstreetmap.org/export/embed.html?bbox=<?= ($loc['longitude']-0.003) ?>,<?= ($loc['latitude']-0.003) ?>,<?= ($loc['longitude']+0.003) ?>,<?= ($loc['latitude']+0.003) ?>&layer=mapnik&marker=<?= $loc['latitude'] ?>,<?= $loc['longitude'] ?>"
                        style="width:100%; height:100%; border:none;"
                        loading="lazy">
                    </iframe>
                </div>
                <p style="text-align:center; font-size:0.75rem; color:#aaa; margin-top:6px;">
                    📍 <?= htmlspecialchars($loc['name']) ?> — radius: <?= $loc['radius_meters'] ?>m
                </p>

            <?php else: ?>
                <div class="alert alert-warning">
                    ⚠️ No location set yet. Students can currently log attendance from anywhere.
                </div>
            <?php endif; ?>
        </div>

        <!-- How to get coordinates -->
        <div class="card">
            <div class="card-title">💡 How to Get Coordinates</div>
            <ol style="font-size:0.88rem; line-height:2; padding-left:20px; color:#555;">
                <li>Click <strong>"Use My Current Location"</strong> button (easiest)</li>
                <li>Or open <a href="https://www.google.com/maps" target="_blank" style="color:#1a237e;">Google Maps</a>, right-click on your school location, and copy the coordinates</li>
                <li>Paste Latitude and Longitude in the fields</li>
                <li>Set the radius (100m = typical classroom building)</li>
            </ol>
        </div>
    </div>
</div>

<script>
function useMyLocation() {
    const statusEl = document.getElementById('my-loc-status');
    statusEl.textContent = '📡 Getting your location...';

    if (!navigator.geolocation) {
        statusEl.textContent = '❌ GPS not supported by your browser.';
        return;
    }

    navigator.geolocation.getCurrentPosition(
        (pos) => {
            const lat = pos.coords.latitude;
            const lon = pos.coords.longitude;
            document.getElementById('lat-input').value = lat.toFixed(8);
            document.getElementById('lon-input').value = lon.toFixed(8);
            statusEl.innerHTML =
                `✅ Got location: <strong>${lat.toFixed(5)}, ${lon.toFixed(5)}</strong> — Accuracy: ±${Math.round(pos.coords.accuracy)}m`;
        },
        (err) => {
            const msgs = {
                1: 'Permission denied. Allow location in browser settings.',
                2: 'Location unavailable.',
                3: 'Timeout. Try again.',
            };
            statusEl.textContent = '❌ ' + (msgs[err.code] || err.message);
        },
        { enableHighAccuracy: true, timeout: 15000 }
    );
}
</script>

    </main>
</div>
</body>
</html>
