<?php
// Load .env early so MAPBOX_PUBLIC_TOKEN is available
if (empty($_ENV['MAPBOX_PUBLIC_TOKEN'])) {
    $envFile = dirname(__DIR__) . '/.env';
    if (file_exists($envFile)) {
        foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
            [$k, $v] = explode('=', $line, 2);
            $_ENV[trim($k)] = trim($v);
        }
    }
}
require_once 'layout.php';
$msg = ''; $err = '';

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save_location'])) {
    $loc_name = $conn->real_escape_string(trim($_POST['loc_name']));
    $lat      = floatval($_POST['latitude']);
    $lon      = floatval($_POST['longitude']);
    $radius   = intval($_POST['radius']);
    $sub_id   = intval($_POST['subject_id'] ?? 0);
    $sub_val  = $sub_id ? $sub_id : 'NULL';
    $edit_id  = intval($_POST['edit_id'] ?? 0);

    if ($lat == 0 || $lon == 0) { $err = "Invalid coordinates. Use the GPS button."; }
    elseif ($radius < 1 || $radius > 2000) { $err = "Radius must be 1–2000 meters."; }
    else {
        if ($edit_id > 0) {
            // UPDATE existing location
            $conn->query("UPDATE teacher_locations SET
                name='$loc_name', latitude=$lat, longitude=$lon,
                radius_meters=$radius, subject_id=$sub_val, is_active=1
                WHERE id=$edit_id AND teacher_id=$teacher_id");
            $msg = "Location updated successfully.";
        } else {
            // INSERT new location
            $conn->query("UPDATE teacher_locations SET is_active=0 WHERE teacher_id=$teacher_id AND ".($sub_id?"subject_id=$sub_id":"subject_id IS NULL"));
            $conn->query("INSERT INTO teacher_locations (teacher_id,subject_id,name,latitude,longitude,radius_meters,is_active)
                          VALUES ($teacher_id,$sub_val,'$loc_name',$lat,$lon,$radius,1)");
            $msg = "Location saved successfully.";
        }
    }
}

if (isset($_GET['delete_loc'])) {
    $lid = intval($_GET['delete_loc']);
    $conn->query("DELETE FROM teacher_locations WHERE id=$lid AND teacher_id=$teacher_id");
    $msg = "Location deleted.";
}

$subjects  = $conn->query("SELECT * FROM subjects WHERE teacher_id=$teacher_id AND is_active=1 ORDER BY subject_name");
$locations = $conn->query("
    SELECT tl.*, sub.subject_name, sub.section
    FROM teacher_locations tl LEFT JOIN subjects sub ON tl.subject_id=sub.id
    WHERE tl.teacher_id=$teacher_id ORDER BY tl.is_active DESC, tl.updated_at DESC
");

$defLoc  = $conn->query("SELECT latitude,longitude FROM teacher_locations WHERE teacher_id=$teacher_id AND is_active=1 ORDER BY updated_at DESC LIMIT 1")->fetch_assoc();
$defLat  = $defLoc ? floatval($defLoc['latitude'])  : 14.599512;   /* default: Manila */
$defLon  = $defLoc ? floatval($defLoc['longitude']) : 120.984222;
$defZoom = $defLoc ? 18 : 13;
?>

<?php if ($msg): ?><div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger">❌ <?= htmlspecialchars($err) ?></div><?php endif; ?>

<div class="grid-2">
<!-- Set Location Form -->
<div class="card">
    <div class="card-title" id="form-title">📍 Set Classroom Location</div>
    <input type="hidden" id="edit-loc-id" value="0">
    <div class="alert alert-success" style="font-size:0.82rem;margin-bottom:14px;">
        ⓘ Students must be within the allowed radius to record attendance.
    </div>
    <form method="POST">
        <div class="form-group">
            <label>Location Name</label>
            <input type="text" name="loc_name" class="form-control" placeholder="e.g. Room 201 / Building A" required value="My Classroom">
        </div>
        <div class="form-group">
            <label>Apply to Subject <span style="color:#aaa;font-size:0.76rem;">(blank = all classes)</span></label>
            <select name="subject_id" class="form-control">
                <option value="0">All My Classes (Default)</option>
                <?php $subjects->data_seek(0); while($s = $subjects->fetch_assoc()): ?>
                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['subject_name']) ?> (<?= htmlspecialchars($s['section']) ?>)</option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Latitude</label>
            <input type="number" name="latitude" id="lat-in" class="form-control" step="0.00000001" placeholder="e.g. 14.59950" required>
        </div>
        <div class="form-group">
            <label>Longitude</label>
            <input type="number" name="longitude" id="lon-in" class="form-control" step="0.00000001" placeholder="e.g. 120.98422" required>
        </div>
        <div class="form-group">
            <label>Allowed Radius: <strong id="rad-lbl">100m</strong></label>
            <input type="range" name="radius" id="rad-range" min="1" max="500" step="1" value="100"
                   oninput="onRadiusChange(this.value)"
                   style="width:100%;margin-top:4px;">
            <div style="display:flex;justify-content:space-between;font-size:0.72rem;color:#aaa;margin-top:2px;"><span>1m (strict)</span><span>500m</span></div>
            <p style="color:#aaa;font-size:0.77rem;margin-top:4px;">
                💡 Set 1–5m for strict classroom control, 10–50m for outdoor areas.
            </p>
        </div>
        <div class="form-group">
            <label>📍 Pin the exact location on the map</label>
            <div class="map-search-wrap">
                <input type="text" id="map-search" placeholder="Search school, street, building name...">
                <button type="button" id="search-btn" onclick="searchPlace()">🔍 Search</button>
            </div>
            <div id="map"></div>
            <div class="coord-display" id="coord-display">
                <div class="coord-box"><span>Latitude</span><span id="disp-lat">—</span></div>
                <div class="coord-box"><span>Longitude</span><span id="disp-lon">—</span></div>
            </div>
            <p style="color:#aaa;font-size:0.77rem;margin-top:6px;">
                💡 Tap/click the map to drop the pin · Drag the pin to move it · The green circle shows the allowed radius.
            </p>
        </div>
        <div class="form-group">
            <button type="button" onclick="getMyLocation()" class="btn btn-primary btn-block" style="margin-bottom:8px;">
                📡 Use My Current GPS Location
            </button>
            <div id="gps-status" style="font-size:0.78rem;color:#888;text-align:center;"></div>
        </div>
        <button type="submit" name="save_location" class="btn btn-success btn-block" id="save-btn">💾 Save Location</button>
        <button type="button" class="btn btn-block" id="cancel-edit-btn"
                style="display:none;margin-top:6px;background:#eee;color:#555;"
                onclick="cancelEdit()">✖ Cancel Edit</button>
        <input type="hidden" name="edit_id" id="edit-id-field" value="0">
    </form>
</div>

<!-- Saved Locations -->
<div class="card">
    <div class="card-title">📋 Saved Locations</div>
    <?php if ($locations->num_rows > 0): ?>
    <?php while($loc = $locations->fetch_assoc()): ?>
    <div style="background:<?= $loc['is_active']?'#e8f5e9':'#f9f9f9' ?>;border:1px solid <?= $loc['is_active']?'#c8e6c9':'#eee' ?>;border-radius:10px;padding:14px;margin-bottom:10px;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;">
            <div>
                <div style="font-weight:700;color:#1b5e20;font-size:0.92rem;">
                    <?= $loc['is_active'] ? '🟢' : '⚪' ?> <?= htmlspecialchars($loc['name']) ?>
                </div>
                <div style="font-size:0.78rem;color:#666;margin-top:4px;">
                    <?php if ($loc['subject_name']): ?>
                    📚 <?= htmlspecialchars($loc['subject_name']) ?> (<?= htmlspecialchars($loc['section']) ?>)<br>
                    <?php else: ?>📌 All Classes (Default)<br><?php endif; ?>
                    📍 <?= $loc['latitude'] ?>, <?= $loc['longitude'] ?> &nbsp;·&nbsp; Radius: <strong><?= $loc['radius_meters'] ?>m</strong>
                </div>
            </div>
            <div style="display:flex;gap:6px;">
                <button type="button" class="btn btn-primary btn-sm"
                    onclick="editLoc(<?= $loc['id'] ?>, <?= htmlspecialchars(json_encode($loc['name'])) ?>, <?= floatval($loc['latitude']) ?>, <?= floatval($loc['longitude']) ?>, <?= intval($loc['radius_meters']) ?>, <?= intval($loc['subject_id'] ?? 0) ?>)">
                    ✏️ Edit
                </button>
                <a href="?delete_loc=<?= $loc['id'] ?>" onclick="return confirm('Delete?')" class="btn btn-danger btn-sm">🗑️</a>
            </div>
        </div>
        <div style="margin-top:10px;border-radius:8px;overflow:hidden;height:150px;"
             data-minimap
             data-lat="<?= floatval($loc['latitude']) ?>"
             data-lon="<?= floatval($loc['longitude']) ?>"
             data-radius="<?= intval($loc['radius_meters']) ?>"
             data-name="<?= htmlspecialchars($loc['name']) ?>">
        </div>
    </div>
    <?php endwhile; ?>
    <?php else: ?>
    <div class="empty-state"><div class="empty-icon">📍</div><p>No locations saved yet.<br>Students can record attendance from anywhere.</p></div>
    <?php endif; ?>
</div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
/* ── Custom green pin ── */
.loc-pin {
    width:32px; height:42px; position:relative; cursor:pointer;
}
.loc-pin-head {
    width:32px; height:32px; border-radius:50% 50% 50% 0;
    transform:rotate(-45deg); background:#2e7d32;
    border:3px solid #fff; box-shadow:0 3px 10px rgba(0,0,0,0.35);
    display:flex; align-items:center; justify-content:center;
    transition:background 0.2s;
}
.loc-pin-head::after {
    content:'📍'; font-size:13px; transform:rotate(45deg); display:block;
}
.loc-pin-tail {
    width:4px; height:12px; background:#2e7d32;
    margin:0 auto; border-radius:0 0 3px 3px;
}
/* Search box */
.map-search-wrap {
    display:flex; gap:6px; margin-bottom:8px;
}
.map-search-wrap input {
    flex:1; border:1.5px solid #c8e6c9; border-radius:9px;
    padding:9px 13px; font-size:0.88rem; outline:none; transition:border-color 0.2s;
}
.map-search-wrap input:focus { border-color:#2e7d32; box-shadow:0 0 0 3px rgba(46,125,50,0.12); }
.map-search-wrap button {
    background:#2e7d32; color:white; border:none; border-radius:9px;
    padding:9px 14px; font-size:0.88rem; cursor:pointer; white-space:nowrap;
    transition:background 0.2s;
}
.map-search-wrap button:hover { background:#1b5e20; }
/* Map container */
#map {
    width:100%; height:320px; border-radius:12px; overflow:hidden;
    border:2px solid #c8e6c9; position:relative; z-index:0;
    box-shadow:0 2px 12px rgba(46,125,50,0.10);
}
/* Coord display under map */
.coord-display {
    display:flex; gap:8px; margin-top:7px;
}
.coord-box {
    flex:1; background:#f1f8e9; border:1px solid #c8e6c9; border-radius:8px;
    padding:7px 10px; font-size:0.78rem; color:#2e7d32; font-weight:700;
    text-align:center;
}
.coord-box span { display:block; font-size:0.68rem; color:#888; font-weight:400; }
/* Radius slider styled */
#rad-range { accent-color:#2e7d32; }
</style>

<script>
let locMap = null, locMarker = null, locCircle = null;

/* ── Custom green pin icon ── */
function greenIcon() {
    return L.divIcon({
        className: '',
        html: '<div class="loc-pin"><div class="loc-pin-head"></div><div class="loc-pin-tail"></div></div>',
        iconSize:   [32, 42],
        iconAnchor: [16, 42],
        popupAnchor:[0, -44]
    });
}

/* ── Draw/update radius circle ── */
function updateCircle(lat, lon) {
    const r = parseInt(document.getElementById('rad-range').value) || 100;
    if (locCircle) locMap.removeLayer(locCircle);
    locCircle = L.circle([lat, lon], {
        radius: r,
        color:       '#2e7d32',
        fillColor:   '#4caf50',
        fillOpacity: 0.15,
        weight:      2,
        dashArray:   '6,4'
    }).addTo(locMap);
}

/* ── Drop / move the pin ── */
function setLocMarker(lat, lon, pan) {
    if (locMarker) locMap.removeLayer(locMarker);
    locMarker = L.marker([lat, lon], { icon: greenIcon(), draggable: true }).addTo(locMap);

    locMarker.on('drag', function (ev) {
        const p = ev.target.getLatLng();
        updateCoordDisplay(p.lat, p.lng);
        updateCircle(p.lat, p.lng);
    });
    locMarker.on('dragend', function (ev) {
        const p = ev.target.getLatLng();
        document.getElementById('lat-in').value = p.lat.toFixed(8);
        document.getElementById('lon-in').value = p.lng.toFixed(8);
        updateCoordDisplay(p.lat, p.lng);
        updateCircle(p.lat, p.lng);
        locMarker.bindPopup('<b>📍 Pinned Location</b><br>' + p.lat.toFixed(6) + ', ' + p.lng.toFixed(6)).openPopup();
    });

    document.getElementById('lat-in').value = lat.toFixed(8);
    document.getElementById('lon-in').value = lon.toFixed(8);
    updateCoordDisplay(lat, lon);
    updateCircle(lat, lon);
    locMarker.bindPopup('<b>📍 Pinned Location</b><br>' + lat.toFixed(6) + ', ' + lon.toFixed(6)).openPopup();
    if (pan) locMap.setView([lat, lon], 17);
}

function updateCoordDisplay(lat, lon) {
    const el = document.getElementById('coord-display');
    if (el) {
        document.getElementById('disp-lat').textContent = lat.toFixed(6);
        document.getElementById('disp-lon').textContent = lon.toFixed(6);
    }
}

/* ── Radius slider live update ── */
function onRadiusChange(val) {
    document.getElementById('rad-lbl').textContent = val + 'm';
    if (locMarker) {
        const p = locMarker.getLatLng();
        updateCircle(p.lat, p.lng);
    }
}

/* ── Search ── */
function searchPlace() {
    const q = document.getElementById('map-search').value.trim();
    if (!q) return;
    const btn = document.getElementById('search-btn');
    btn.textContent = '⏳'; btn.disabled = true;
    fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' + encodeURIComponent(q))
        .then(r => r.json())
        .then(data => {
            btn.textContent = '🔍 Search'; btn.disabled = false;
            if (data && data[0]) {
                const la = parseFloat(data[0].lat), lo = parseFloat(data[0].lon);
                locMap.setView([la, lo], 17);
                setLocMarker(la, lo, false);
            } else {
                showMapMsg('Place not found — try a different name, or tap the map directly.', 'warn');
            }
        })
        .catch(() => { btn.textContent = '🔍 Search'; btn.disabled = false; showMapMsg('Search failed — tap the map to pin instead.', 'warn'); });
}

function showMapMsg(msg, type) {
    let el = document.getElementById('map-msg');
    if (!el) { el = document.createElement('div'); el.id = 'map-msg'; el.style.cssText='font-size:0.78rem;padding:6px 10px;border-radius:7px;margin-top:5px;'; document.getElementById('map').after(el); }
    el.style.background = type==='warn' ? '#fff8e1' : '#e8f5e9';
    el.style.color      = type==='warn' ? '#e65100' : '#2e7d32';
    el.textContent = msg;
    setTimeout(() => { if(el) el.textContent=''; }, 4000);
}

/* ── GPS ── */
function getMyLocation() {
    const el = document.getElementById('gps-status');
    el.textContent = '📡 Getting your location...'; el.style.color = '#888';
    if (!navigator.geolocation) { el.textContent = '❌ GPS not supported.'; el.style.color = '#e53935'; return; }
    navigator.geolocation.getCurrentPosition(
        (pos) => {
            const la = pos.coords.latitude, lo = pos.coords.longitude;
            setLocMarker(la, lo, true);
            el.textContent = '✅ ' + la.toFixed(5) + ', ' + lo.toFixed(5) + ' (±' + Math.round(pos.coords.accuracy) + 'm)';
            el.style.color = '#2e7d32';
        },
        (err) => { el.textContent = '❌ GPS blocked — use the map to pin instead.'; el.style.color = '#e53935'; },
        { enableHighAccuracy: true, timeout: 15000 }
    );
}

/* ── Edit existing location ── */
function editLoc(id, name, lat, lon, radius, subjectId) {
    document.getElementById('edit-id-field').value = id;
    document.getElementById('edit-loc-id').value   = id;
    document.querySelector('[name="loc_name"]').value = name;
    document.getElementById('lat-in').value   = lat;
    document.getElementById('lon-in').value   = lon;
    document.getElementById('rad-range').value = radius;
    document.getElementById('rad-lbl').textContent = radius + 'm';
    const sel = document.querySelector('[name="subject_id"]');
    if (sel) sel.value = subjectId || 0;
    setLocMarker(lat, lon, true);
    document.getElementById('form-title').textContent = '✏️ Edit Location';
    document.getElementById('save-btn').textContent   = '💾 Update Location';
    document.getElementById('cancel-edit-btn').style.display = 'block';
    document.querySelector('.card').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

/* ── Cancel edit ── */
function cancelEdit() {
    document.getElementById('edit-id-field').value = 0;
    document.getElementById('form-title').textContent = '📍 Set Classroom Location';
    document.getElementById('save-btn').textContent   = '💾 Save Location';
    document.getElementById('cancel-edit-btn').style.display = 'none';
    document.querySelector('[name="loc_name"]').value = 'My Classroom';
    document.getElementById('lat-in').value  = '';
    document.getElementById('lon-in').value  = '';
    document.getElementById('rad-range').value = 100;
    document.getElementById('rad-lbl').textContent = '100m';
    updateCoordDisplay(0,0);
    if (locMarker) { locMap.removeLayer(locMarker); locMarker = null; }
    if (locCircle) { locMap.removeLayer(locCircle); locCircle = null; }
}

/* ── Boot map ── */
window.addEventListener('load', function () {
    locMap = L.map('map', { zoomControl: true }).setView([<?= $defLat ?>, <?= $defLon ?>], <?= $defZoom ?>);

    <?php
    // Load .env if not already loaded
    if (empty($_ENV['MAPBOX_PUBLIC_TOKEN'])) {
        $envFile = dirname(__DIR__) . '/.env';
        if (file_exists($envFile)) {
            foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
                [$k, $v] = explode('=', $line, 2);
                $_ENV[trim($k)] = trim($v);
            }
        }
    }
    ?>
    const MAPBOX_TOKEN = '<?= htmlspecialchars($_ENV['MAPBOX_PUBLIC_TOKEN'] ?? '') ?>';

    /* Primary tile layer — Mapbox Streets (updated, with labels) */
    const osmLayer = L.tileLayer('https://api.mapbox.com/styles/v1/mapbox/streets-v12/tiles/{z}/{x}/{y}?access_token=' + MAPBOX_TOKEN, {
        maxZoom: 22,
        maxNativeZoom: 20,
        tileSize: 512,
        zoomOffset: -1,
        attribution: '© <a href="https://www.mapbox.com/">Mapbox</a>'
    });

    /* Fallback — Mapbox Satellite Streets */
    const cartoLayer = L.tileLayer('https://api.mapbox.com/styles/v1/mapbox/satellite-streets-v12/tiles/{z}/{x}/{y}?access_token=' + MAPBOX_TOKEN, {
        maxZoom: 22,
        maxNativeZoom: 20,
        tileSize: 512,
        zoomOffset: -1,
        attribution: '© <a href="https://www.mapbox.com/">Mapbox</a>'
    });

    osmLayer.addTo(locMap);

    /* If OSM tiles fail to load after 5 s, switch to CartoDB */
    let tilesLoaded = false;
    osmLayer.on('tileload', function() { tilesLoaded = true; });
    setTimeout(function() {
        if (!tilesLoaded) {
            locMap.removeLayer(osmLayer);
            cartoLayer.addTo(locMap);
        }
    }, 5000);

    locMap.on('click', function (e) { setLocMarker(e.latlng.lat, e.latlng.lng, false); });

    /* Enter key on search */
    document.getElementById('map-search').addEventListener('keydown', function(e){ if(e.key==='Enter'){e.preventDefault();searchPlace();} });

    /* Restore coords if already filled (after form error) */
    const la = parseFloat(document.getElementById('lat-in').value);
    const lo = parseFloat(document.getElementById('lon-in').value);
    if (!isNaN(la) && la !== 0) setLocMarker(la, lo, true);

    setTimeout(function () { locMap.invalidateSize(); }, 300);

    /* Mini-maps for saved locations */
    document.querySelectorAll('[data-minimap]').forEach(function(el) {
        const la  = parseFloat(el.dataset.lat);
        const lo  = parseFloat(el.dataset.lon);
        const r   = parseInt(el.dataset.radius);
        const nm  = el.dataset.name;
        const m   = L.map(el, { zoomControl:false, attributionControl:false, dragging:false, scrollWheelZoom:false, doubleClickZoom:false, boxZoom:false, keyboard:false });
        L.tileLayer('https://api.mapbox.com/styles/v1/mapbox/streets-v12/tiles/{z}/{x}/{y}?access_token=<?= htmlspecialchars($_ENV['MAPBOX_PUBLIC_TOKEN'] ?? '') ?>', { maxZoom:22, tileSize:512, zoomOffset:-1 }).addTo(m);
        m.setView([la, lo], 18);
        L.marker([la, lo], { icon: greenIcon() }).addTo(m).bindPopup('<b>' + nm + '</b>').openPopup();
        L.circle([la, lo], { radius:r, color:'#2e7d32', fillColor:'#4caf50', fillOpacity:0.15, weight:2, dashArray:'6,4' }).addTo(m);
        setTimeout(function(){ m.invalidateSize(); }, 200);
    });
});
</script>

</main>
</div>
</body>
</html>
