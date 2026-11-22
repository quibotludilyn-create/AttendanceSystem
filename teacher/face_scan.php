<?php
session_start();
if (!isset($_SESSION['teacher_id'])) { header('Location: ../teacher_login.php'); exit; }
require_once '../config/database.php';
$conn = getConnection();
$teacher_id = intval($_SESSION['teacher_id']);
$teacher_name = $_SESSION['teacher_name'] ?? 'Teacher';

$subject_id = intval($_GET['subject'] ?? 0);
if (!$subject_id) { header('Location: attendance.php'); exit; }

$sub = $conn->query("SELECT * FROM subjects WHERE id=$subject_id AND teacher_id=$teacher_id AND is_active=1");
if (!$sub || $sub->num_rows === 0) { header('Location: attendance.php'); exit; }
$subject = $sub->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Face Scan Attendance — <?= htmlspecialchars($subject['subject_name']) ?></title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',sans-serif; background:#0a0e1a; color:white; min-height:100vh; }

        .top-bar {
            background:linear-gradient(90deg,#263f60,#4d7193);
            padding:12px 24px;
            display:flex; align-items:center; justify-content:space-between;
            box-shadow:0 2px 10px rgba(0,0,0,0.3);
        }
        .top-bar h1 { font-size:1rem; font-weight:800; }
        .top-bar .sub { font-size:0.78rem; opacity:0.8; margin-top:2px; }
        .back-btn { background:rgba(255,255,255,0.15); border:1px solid rgba(255,255,255,0.3); color:white; padding:7px 16px; border-radius:8px; text-decoration:none; font-size:0.82rem; font-weight:600; }

        .main { display:grid; grid-template-columns:1fr 340px; gap:0; height:calc(100vh - 60px); }

        /* ── Camera side ── */
        .cam-side { position:relative; background:#000; display:flex; align-items:center; justify-content:center; overflow:hidden; }
        #video { width:100%; height:100%; object-fit:cover; display:block; }
        #canvas { position:absolute; top:0; left:0; width:100%; height:100%; pointer-events:none; }

        .cam-overlay {
            position:absolute; bottom:20px; left:50%; transform:translateX(-50%);
            background:rgba(0,0,0,0.7); border-radius:12px; padding:12px 24px;
            text-align:center; backdrop-filter:blur(8px); min-width:280px;
        }
        .cam-status { font-size:0.9rem; font-weight:700; color:#a8c2df; }
        .cam-status.scanning { color:#91abc2; animation: pulse 1s infinite; }
        .cam-status.matched  { color:#a8c2df; }
        .cam-status.no-face  { color:#ffa726; }
        .cam-status.error    { color:#e53935; }
        @keyframes pulse { 0%,100%{ opacity:1; } 50%{ opacity:0.5; } }

        .match-banner {
            position:absolute; top:20px; left:50%; transform:translateX(-50%);
            background:rgba(55,83,115,0.94); border-radius:12px; padding:14px 24px;
            text-align:center; backdrop-filter:blur(8px);
            font-size:1.1rem; font-weight:800;
            display:none; animation:slideDown 0.3s ease;
            min-width:260px;
        }
        @keyframes slideDown { from{ transform:translateX(-50%) translateY(-20px); opacity:0; } to{ transform:translateX(-50%) translateY(0); opacity:1; } }

        /* ── Students side ── */
        .students-side { background:#111827; border-left:1px solid rgba(255,255,255,0.1); overflow-y:auto; }
        .students-header { padding:16px; background:#1f2937; border-bottom:1px solid rgba(255,255,255,0.1); position:sticky; top:0; z-index:10; }
        .students-header h3 { font-size:0.9rem; font-weight:700; }
        .students-header p  { font-size:0.74rem; color:#9ca3af; margin-top:3px; }

        .stats-row { display:grid; grid-template-columns:1fr 1fr 1fr; gap:8px; padding:12px; border-bottom:1px solid rgba(255,255,255,0.08); }
        .stat-box { background:#1f2937; border-radius:8px; padding:8px; text-align:center; }
        .stat-box .n { font-size:1.2rem; font-weight:900; }
        .stat-box .l { font-size:0.6rem; color:#9ca3af; margin-top:2px; }

        .student-item {
            display:flex; align-items:center; gap:10px;
            padding:10px 16px; border-bottom:1px solid rgba(255,255,255,0.05);
            transition:background 0.2s;
        }
        .student-item:hover { background:rgba(255,255,255,0.03); }
        .student-item.present { background:rgba(105,147,189,0.12); border-left:3px solid #7897b5; }
        .student-item.late    { background:rgba(230,81,0,0.1);   border-left:3px solid #e65100; }
        .student-item.absent  { background:rgba(229,57,53,0.08); }
        .student-item.scanning { background:rgba(0,180,216,0.15); border-left:3px solid #00b4d8; animation:pulse 1s infinite; }

        .stu-avatar { width:36px; height:36px; border-radius:50%; background:linear-gradient(135deg,#374151,#4b5563); display:flex; align-items:center; justify-content:center; font-size:0.85rem; font-weight:700; flex-shrink:0; }
        .stu-name   { font-size:0.85rem; font-weight:600; }
        .stu-id     { font-size:0.7rem; color:#9ca3af; }
        .stu-badge  { margin-left:auto; padding:3px 10px; border-radius:20px; font-size:0.68rem; font-weight:700; white-space:nowrap; }
        .badge-present { background:#263f60; color:#b6cbe0; }
        .badge-late    { background:#7c2d12; color:#fdba74; }
        .badge-absent  { background:#374151; color:#9ca3af; }
        .badge-scanning{ background:#2d4966; color:#b6cbe0; }

        .btn-start { width:calc(100% - 24px); margin:12px; padding:12px; background:linear-gradient(135deg,#4d7193,#5e7f9e); color:white; border:none; border-radius:10px; font-size:0.95rem; font-weight:700; cursor:pointer; }
        .btn-start:hover { opacity:0.9; }
        .btn-stop  { width:calc(100% - 24px); margin:12px; padding:12px; background:rgba(229,57,53,0.2); color:#ef4444; border:1px solid rgba(229,57,53,0.4); border-radius:10px; font-size:0.95rem; font-weight:700; cursor:pointer; display:none; }
    </style>
</head>
<body>

<div class="top-bar">
    <div>
        <h1>📷 Face Scan Attendance</h1>
        <div class="sub"><?= htmlspecialchars($subject['subject_name']) ?> — Section <?= htmlspecialchars($subject['section']) ?></div>
    </div>
    <a href="attendance.php?subject=<?= $subject_id ?>" class="back-btn">← Back</a>
</div>

<div class="main">
    <!-- Camera -->
    <div class="cam-side">
        <video id="video" autoplay muted playsinline></video>
        <canvas id="canvas"></canvas>
        <div class="match-banner" id="match-banner">✅ <span id="match-name"></span></div>
        <div class="cam-overlay">
            <div class="cam-status" id="cam-status">⏳ Loading models...</div>
        </div>
    </div>

    <!-- Students list -->
    <div class="students-side">
        <div class="students-header">
            <h3>👥 Enrolled Students</h3>
            <p id="scan-info">Select subject and start scanning</p>
        </div>
        <div class="stats-row">
            <div class="stat-box"><div class="n" id="cnt-present" style="color:#a8c2df;">0</div><div class="l">Present</div></div>
            <div class="stat-box"><div class="n" id="cnt-late"    style="color:#e65100;">0</div><div class="l">Late</div></div>
            <div class="stat-box"><div class="n" id="cnt-absent"  style="color:#9ca3af;">0</div><div class="l">Remaining</div></div>
        </div>
        <div id="student-list"></div>
        <button class="btn-start" id="btn-start" onclick="startScan()">▶ Start Face Scanning</button>
        <button class="btn-stop"  id="btn-stop"  onclick="stopScan()">⏹ Stop Scanning</button>
    </div>
</div>

<script src="../assets/js/face-api.min.js"></script>
<script>
const SUBJECT_ID = <?= $subject_id ?>;
const TEACHER_ID = <?= $teacher_id ?>;
const MODEL_URL  = (function() {
    const p = window.location.pathname;
    if (p.includes('/AttendanceSystem/')) return '/AttendanceSystem/assets/models';
    return '../assets/models';
})();

let students       = [];
let recorded       = {};
let faceMatcher    = null;
let videoStream    = null;
let scanLoop       = null;
let isProcessing   = false;
let matchCooldown  = {};

const video    = document.getElementById('video');
const canvas   = document.getElementById('canvas');
const camStat  = document.getElementById('cam-status');
const matchBanner = document.getElementById('match-banner');
const matchName   = document.getElementById('match-name');

// ── Load models + students ──────────────────────────
async function init() {
    camStat.textContent = '⏳ Loading face models...';
    camStat.className   = 'cam-status scanning';

    await Promise.all([
        faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
        faceapi.nets.faceLandmark68TinyNet.loadFromUri(MODEL_URL),
        faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL),
    ]);

    camStat.textContent = '⏳ Loading student faces...';
    const res  = await fetch(`../api/get_subject_students.php?subject_id=${SUBJECT_ID}`);
    const data = await res.json();

    if (!data.success || data.students.length === 0) {
        camStat.textContent = '❌ No enrolled students with registered faces.';
        camStat.className = 'cam-status error';
        return;
    }

    students = data.students;
    recorded = data.recorded || {};

    // Build face matcher
    const labeled = students
        .filter(s => s.descriptor && s.descriptor.length === 128)
        .map(s => new faceapi.LabeledFaceDescriptors(
            String(s.id),
            [new Float32Array(s.descriptor)]
        ));

    if (labeled.length === 0) {
        camStat.textContent = '❌ No valid face descriptors found.';
        camStat.className = 'cam-status error';
        return;
    }

    faceMatcher = new faceapi.FaceMatcher(labeled, 0.55);
    renderStudentList();

    camStat.textContent = '✅ Ready — Click "Start Face Scanning"';
    camStat.className = 'cam-status matched';
}

// ── Render student list ─────────────────────────────
function renderStudentList() {
    const list = document.getElementById('student-list');
    let present = 0, late = 0, remaining = 0;

    list.innerHTML = students.map(s => {
        const rec = recorded[s.id];
        let status = 'absent', badgeClass = 'badge-absent', badgeText = '—';
        if (rec) {
            status    = rec.status;
            badgeClass= 'badge-' + rec.status;
            badgeText = ucfirst(rec.status) + ' ' + (rec.time_in ? rec.time_in.slice(0,5) : '');
            if (rec.status === 'present') present++;
            else if (rec.status === 'late') late++;
        } else {
            remaining++;
        }
        const initial = s.full_name.charAt(0).toUpperCase();
        return `<div class="student-item ${rec ? rec.status : ''}" id="stu-${s.id}">
            <div class="stu-avatar">${initial}</div>
            <div>
                <div class="stu-name">${s.full_name}</div>
                <div class="stu-id">${s.student_id}</div>
            </div>
            <span class="stu-badge ${badgeClass}" id="badge-${s.id}">${badgeText}</span>
        </div>`;
    }).join('');

    document.getElementById('cnt-present').textContent = present;
    document.getElementById('cnt-late').textContent    = late;
    document.getElementById('cnt-absent').textContent  = remaining;
}

function ucfirst(str) { return str.charAt(0).toUpperCase() + str.slice(1); }

// ── Start scanning ──────────────────────────────────
async function startScan() {
    document.getElementById('btn-start').style.display = 'none';
    document.getElementById('btn-stop').style.display  = 'block';

    videoStream = await navigator.mediaDevices.getUserMedia({
        video: { width: 640, height: 480, facingMode: 'user' }
    });
    video.srcObject = videoStream;
    await new Promise(r => video.addEventListener('loadedmetadata', r, { once: true }));

    canvas.width  = video.videoWidth;
    canvas.height = video.videoHeight;

    camStat.textContent = '🔍 Scanning for faces...';
    camStat.className   = 'cam-status scanning';

    scanLoop = setInterval(detectAndMatch, 400);
}

// ── Stop scanning ───────────────────────────────────
function stopScan() {
    clearInterval(scanLoop);
    if (videoStream) videoStream.getTracks().forEach(t => t.stop());
    document.getElementById('btn-start').style.display = 'block';
    document.getElementById('btn-stop').style.display  = 'none';
    camStat.textContent = '⏹ Scanning stopped.';
    camStat.className   = 'cam-status';
    canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
}

// ── Detect + match ──────────────────────────────────
async function detectAndMatch() {
    if (isProcessing || !faceMatcher) return;
    isProcessing = true;

    const ctx = canvas.getContext('2d');
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    try {
        const detections = await faceapi
            .detectAllFaces(video, new faceapi.TinyFaceDetectorOptions({ inputSize: 416, scoreThreshold: 0.4 }))
            .withFaceLandmarks(true)
            .withFaceDescriptors();

        if (!detections || detections.length === 0) {
            camStat.textContent = '👁 No face detected — point camera at student';
            camStat.className   = 'cam-status no-face';
            isProcessing = false;
            return;
        }

        camStat.textContent = `🔍 ${detections.length} face(s) detected — matching...`;
        camStat.className   = 'cam-status scanning';

        const scaleX = canvas.width  / video.videoWidth;
        const scaleY = canvas.height / video.videoHeight;

        for (const det of detections) {
            const match   = faceMatcher.findBestMatch(det.descriptor);
            const studentId = parseInt(match.label);
            const student = students.find(s => s.id === studentId);

            // Draw box
            const b = det.detection.box;
            ctx.strokeStyle = match.distance < 0.55 ? '#91abc2' : '#ffa726';
            ctx.lineWidth   = 3;
            ctx.strokeRect(b.x * scaleX, b.y * scaleY, b.width * scaleX, b.height * scaleY);

            if (match.distance < 0.55 && student) {
                // Label
                ctx.fillStyle = 'rgba(0,200,150,0.8)';
                ctx.fillRect(b.x * scaleX, (b.y + b.height) * scaleY, b.width * scaleX, 22);
                ctx.fillStyle = 'white';
                ctx.font = '12px Segoe UI';
                ctx.fillText(student.full_name, b.x * scaleX + 4, (b.y + b.height) * scaleY + 15);

                // Check cooldown (don't re-record within 5 seconds)
                const now = Date.now();
                if (matchCooldown[student.id] && now - matchCooldown[student.id] < 5000) continue;

                // Skip if already recorded today
                if (recorded[student.id]) continue;

                matchCooldown[student.id] = now;
                await recordAttendance(student);
            }
        }

    } catch(e) {
        console.error(e);
    }

    isProcessing = false;
}

// ── Record attendance via API ───────────────────────
async function recordAttendance(student) {
    // Show scanning indicator
    const item = document.getElementById(`stu-${student.id}`);
    if (item) {
        item.className = 'student-item scanning';
        document.getElementById(`badge-${student.id}`).className = 'stu-badge badge-scanning';
        document.getElementById(`badge-${student.id}`).textContent = '⏳ Recording...';
    }

    // Show match banner
    matchName.textContent = student.full_name;
    matchBanner.style.display = 'block';
    setTimeout(() => { matchBanner.style.display = 'none'; }, 2500);

    try {
        const res  = await fetch('../api/save_attendance.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                student_id: student.id,
                subject_id: SUBJECT_ID,
                subject:    document.querySelector('.top-bar .sub').textContent.split(' — ')[0],
                time_in:    new Date().toTimeString().slice(0, 8),
                status:     computeStatus(),
                latitude:   null,
                longitude:  null,
                teacher_scan: true
            })
        });
        const data = await res.json();

        if (data.success) {
            const status = data.status || computeStatus();
            recorded[student.id] = { status: status, time_in: new Date().toTimeString().slice(0,5) };
            camStat.textContent = `✅ ${student.full_name} marked ${status}!`;
            camStat.className   = 'cam-status matched';
        }
    } catch(e) {
        console.error(e);
    }

    renderStudentList();
}

function computeStatus() {
    // Simple: if within normal hours = present, else late
    const h = new Date().getHours();
    const m = new Date().getMinutes();
    return 'present'; // Teacher-initiated scan = always present
}

// ── Init on load ────────────────────────────────────
window.addEventListener('load', init);
</script>
</body>
</html>
