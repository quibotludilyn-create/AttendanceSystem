<?php
session_start();
if (!isset($_SESSION['student_id'])) {
    header('Location: ../student_login.php');
    exit;
}
$student_name    = $_SESSION['student_name']    ?? '';
$student_sid     = $_SESSION['student_sid']     ?? '';
$student_course  = $_SESSION['student_course']  ?? '';
$student_year    = $_SESSION['student_year']    ?? '';
$student_section = $_SESSION['student_section'] ?? '';
require_once 'layout.php';
?>
<style>
    body { background: var(--gray) !important; }

    .scan-card {
        background: white; border-radius: 18px;
        padding: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        border: 1px solid #eef0f7;
    }

    /* Steps */
    .steps { display:flex; justify-content:center; align-items:center; margin-bottom:20px; }
    .step  { display:flex; flex-direction:column; align-items:center; gap:4px; font-size:0.68rem; color:#ccc; font-weight:700; min-width:54px; text-align:center; }
    .step-num { width:28px; height:28px; border-radius:50%; background:#eee; color:#bbb; display:flex; align-items:center; justify-content:center; font-size:0.78rem; font-weight:800; transition:all 0.3s; }
    .step.active  { color:var(--blue); }
    .step.done    { color:#00a87a; }
    .step.active .step-num { background:var(--blue); color:white; box-shadow:0 0 0 4px #c5cae9; }
    .step.done   .step-num { background:#00a87a; color:white; }
    .step-line { flex:1; height:3px; background:#eee; margin-bottom:16px; max-width:44px; transition:background 0.4s; }
    .step-line.done { background:#00a87a; }

    /* Panels */
    .panel { display:none; }
    .panel.visible { display:block; }

    /* Camera */
    #video-wrap { position:relative; border-radius:14px; overflow:hidden; background:#111; width:100%; aspect-ratio:4/3; }
    #video  { width:100%; height:100%; object-fit:cover; display:block; }
    #canvas { position:absolute; top:0; left:0; width:100%; height:100%; }

    .scan-oval {
        position:absolute; top:50%; left:50%;
        transform:translate(-50%,-52%);
        width:160px; height:195px; border-radius:50%;
        border:3px dashed rgba(255,255,255,0.45);
        pointer-events:none; z-index:10; transition:all 0.3s;
    }
    .scan-oval.idle    { animation:opulse 1.5s infinite; }
    .scan-oval.match   { border-color:#00c896; border-style:solid; box-shadow:0 0 20px rgba(0,200,150,0.5); }
    .scan-oval.nomatch { border-color:#ef5350; border-style:solid; }
    @keyframes opulse { 0%,100%{border-color:rgba(100,181,246,0.45);} 50%{border-color:rgba(100,181,246,1);} }

    /* Challenge progress */
    .ch-progress { display:flex; align-items:center; gap:8px; margin-bottom:12px; }
    .ch-step-item {
        flex:1; border-radius:8px; padding:9px 6px; text-align:center;
        border:2px solid #eee; background:#fafafa; transition:all 0.3s;
    }
    .ch-step-item .csi-icon { font-size:1.4rem; margin-bottom:2px; }
    .ch-step-item .csi-lbl  { font-size:0.62rem; font-weight:700; color:#aaa; }
    .ch-step-item.waiting   { border-color:#eee; }
    .ch-step-item.current   { border-color:var(--blue); background:#f0f4ff; }
    .ch-step-item.current .csi-lbl { color:var(--blue); }
    .ch-step-item.passed    { border-color:#00a87a; background:#e8fdf5; }
    .ch-step-item.passed .csi-lbl { color:#00a87a; }
    .ch-arrow { color:#ddd; font-size:1rem; flex-shrink:0; }

    /* Challenge banner */
    .ch-banner {
        background:linear-gradient(135deg,#0a0e2e,#1565c0);
        border-radius:12px; padding:12px 14px;
        text-align:center; color:white;
        margin-bottom:12px; min-height:62px;
        display:flex; flex-direction:column;
        align-items:center; justify-content:center;
        gap:3px; transition:background 0.4s;
    }
    .ch-banner .ci { font-size:1.6rem; line-height:1; }
    .ch-banner .ct { font-size:0.92rem; font-weight:800; letter-spacing:0.4px; }
    .ch-banner .cs { font-size:0.7rem; opacity:0.75; }

    /* Conf bar */
    .conf-wrap { margin-top:10px; }
    .conf-bar  { height:7px; border-radius:4px; background:#eee; overflow:hidden; }
    .conf-fill { height:100%; width:0%; border-radius:4px; transition:width 0.4s, background 0.4s; }
    .conf-lbl  { display:flex; justify-content:space-between; font-size:0.75rem; color:#aaa; margin-top:3px; }

    /* Timer */
    .ch-timer { margin-bottom:10px; display:none; }
    .ch-timer-bar  { height:5px; background:#eee; border-radius:3px; overflow:hidden; }
    .ch-timer-fill { height:100%; width:0%; background:var(--blue); border-radius:3px; transition:width 0.15s linear; }
    .ch-timer-lbl  { font-size:0.68rem; color:#aaa; text-align:center; margin-top:3px; }

    /* Status bar */
    .sbar { text-align:center; padding:10px 14px; border-radius:10px; font-size:0.86rem; font-weight:700; margin-top:10px; transition:all 0.3s; }
    .sb-load { background:#e3f2fd; color:#0277bd; }
    .sb-scan { background:#fff8e1; color:#e65100; }
    .sb-ok   { background:#e8fdf5; color:#00a87a; }
    .sb-fail { background:#ffebee; color:#e53935; }

    /* Subject input */
    .subject-row { background:#f8f9ff; border-radius:12px; padding:14px 16px; margin-bottom:16px; }
    .subject-row label { font-size:0.85rem; font-weight:700; color:#555; display:block; margin-bottom:8px; }
    .subject-row select { width:100%; border:1.5px solid #dde0ec; border-radius:9px; padding:10px 13px; font-size:0.92rem; background:white; outline:none; cursor:pointer; transition:border-color 0.2s; }
    .subject-row select:focus { border-color:var(--accent); box-shadow:0 0 0 3px rgba(0,180,216,0.12); }

    /* Subject card info */
    .subject-info-card {
        display:none;
        background:#e8fdf5; border:1px solid #a5d6a7; border-radius:10px;
        padding:12px 14px; margin-bottom:14px; font-size:0.84rem;
    }
    .subject-info-card.show { display:block; }
    .subject-info-card .si-name { font-weight:700; color:#1b5e20; font-size:0.96rem; margin-bottom:4px; }
    .subject-info-card .si-details { color:#555; }
    .subject-info-card .si-already { background:#ffebee; border:1px solid #ffcdd2; border-radius:7px; padding:7px 10px; color:#e53935; font-weight:600; font-size:0.82rem; margin-top:8px; }

    .fail-ctr { text-align:center; font-size:0.76rem; color:#e53935; margin-top:6px; }

    /* Overlays */
    .overlay { display:none; position:fixed; inset:0; z-index:9999; justify-content:center; align-items:center; background:rgba(0,0,0,0.6); backdrop-filter:blur(5px); }
    .overlay.show { display:flex; animation:fadeIn 0.25s ease; }
    @keyframes fadeIn { from{opacity:0;} to{opacity:1;} }
    .ov-box { background:white; border-radius:22px; padding:36px 28px; max-width:360px; width:90%; text-align:center; box-shadow:0 24px 60px rgba(0,0,0,0.3); animation:popIn 0.4s cubic-bezier(.175,.885,.32,1.275); }
    @keyframes popIn { from{transform:scale(0.7);opacity:0;} to{transform:scale(1);opacity:1;} }
    .ov-box.success { border-top:6px solid #00a87a; }
    .ov-box.failed  { border-top:6px solid #e53935; }
    .ov-icon { font-size:4.5rem; line-height:1; margin-bottom:12px; }
    .ov-box h2 { font-size:1.3rem; margin-bottom:8px; }
    .ov-box.success h2 { color:#00a87a; }
    .ov-box.failed  h2 { color:#e53935; }
    .ov-box p { color:#666; font-size:0.86rem; margin-bottom:8px; line-height:1.5; }
    .ov-details { background:#f6f8fb; border-radius:12px; padding:14px; margin:12px 0; text-align:left; font-size:0.83rem; }
    .ov-row { display:flex; justify-content:space-between; padding:4px 0; border-bottom:1px solid #eee; }
    .ov-row:last-child { border-bottom:none; }
    .ov-lbl { color:#888; }
    .ov-val { font-weight:700; }
    .fail-hint { background:#fff1f1; border-radius:8px; padding:10px 14px; font-size:0.8rem; color:#e53935; margin:10px 0; line-height:1.5; }
</style>

<div class="s-page-title">📷 Take Attendance</div>

<div class="scan-card">

    <!-- Steps -->
    <div class="steps">
        <div class="step done"   id="step1"><div class="step-num">✓</div>Login</div>
        <div class="step-line done"></div>
        <div class="step active" id="step2"><div class="step-num">2</div>Face Challenge</div>
        <div class="step-line"   id="line2"></div>
        <div class="step"        id="step3"><div class="step-num">3</div>Done</div>
    </div>

    <!-- Subject Dropdown -->
    <div class="subject-row">
        <label>📚 Select Your Subject</label>
        <select id="subject-select" onchange="onSubjectChange(this)">
            <option value="">— Loading your subjects... —</option>
        </select>
    </div>

    <!-- Subject Info Card -->
    <div class="subject-info-card" id="subject-info">
        <div class="si-name" id="si-name">—</div>
        <div class="si-details" id="si-details">—</div>
        <div class="si-already" id="si-already" style="display:none;">
            ✅ Already recorded attendance for this subject today.
        </div>
    </div>

    <!-- Loading state (silent location check) -->
    <div id="scan-loading" style="display:none; text-align:center; padding:20px; color:#0277bd; font-weight:600; background:#e3f2fd; border-radius:10px; margin-bottom:16px;"></div>

    <!-- Location blocked panel -->
    <div class="panel" id="loc-blocked">
        <div style="text-align:center; padding:30px 16px;">
            <div style="font-size:4rem; margin-bottom:12px;">📍</div>
            <h3 style="color:#e53935; margin-bottom:8px;">Outside Campus Area</h3>
            <p style="color:#666; font-size:0.88rem; margin-bottom:6px;" id="loc-blocked-msg">
                You are outside the allowed area.
            </p>
            <p style="color:#aaa; font-size:0.82rem; margin-bottom:20px;">
                You must be within the campus to record attendance.
            </p>
            <a href="../student_logout.php" class="s-btn s-btn-danger">🚪 Logout</a>
            <button onclick="retryLocation()" class="s-btn s-btn-primary" style="margin-top:8px;">
                📍 Try Again
            </button>
        </div>
    </div>

    <!-- STEP 2: Face + Challenge -->
    <div class="panel" id="panel-face">

        <!-- Challenge progress (2 steps) -->
        <div class="ch-progress" id="ch-progress">
            <div class="ch-step-item waiting" id="cstep0">
                <div class="csi-icon" id="csi-icon0">❓</div>
                <div class="csi-lbl"  id="csi-lbl0">STEP 1</div>
            </div>
            <div class="ch-arrow">›</div>
            <div class="ch-step-item waiting" id="cstep1">
                <div class="csi-icon" id="csi-icon1">❓</div>
                <div class="csi-lbl"  id="csi-lbl1">STEP 2</div>
            </div>
        </div>

        <!-- Challenge instruction banner -->
        <div class="ch-banner" id="ch-banner">
            <div class="ci">👀</div>
            <div class="ct">LOOK AT CAMERA</div>
            <div class="cs">Center your face in the oval</div>
        </div>

        <!-- Challenge timer bar -->
        <div class="ch-timer" id="ch-timer">
            <div class="ch-timer-bar"><div class="ch-timer-fill" id="ch-timer-fill"></div></div>
            <div class="ch-timer-lbl" id="ch-timer-lbl">Hold position...</div>
        </div>

        <!-- Camera -->
        <div id="video-wrap">
            <video id="video" autoplay muted playsinline></video>
            <canvas id="canvas"></canvas>
            <div class="scan-oval idle" id="scan-oval"></div>
        </div>

        <!-- Confidence bar -->
        <div class="conf-wrap">
            <div class="conf-bar"><div class="conf-fill" id="conf-fill"></div></div>
            <div class="conf-lbl"><span>Face Match</span><span id="conf-pct">0%</span></div>
        </div>

        <div class="sbar sb-load" id="face-status" style="margin-top:10px;">⏳ Loading face recognition...</div>
        <div class="fail-ctr" id="fail-ctr" style="display:none;"></div>
    </div>

</div>

<a href="home.php" class="s-btn s-btn-primary" style="margin-bottom:16px; display:inline-block;">← Back</a>


<!-- Clock -->
<div style="text-align:center; color:#aaa; font-size:0.78rem; margin-top:14px;" id="clock"></div>

<!-- SUCCESS OVERLAY -->
<div class="overlay" id="ov-success">
    <div class="ov-box success">
        <div class="ov-icon">🎉</div>
        <h2>Attendance Recorded!</h2>
        <p>You are marked as <strong id="suc-status" style="color:#00a87a;">PRESENT</strong></p>
        <div class="ov-details">
            <div class="ov-row"><span class="ov-lbl">Name</span><span class="ov-val" id="suc-name">—</span></div>
            <div class="ov-row"><span class="ov-lbl">Student ID</span><span class="ov-val" id="suc-id">—</span></div>
            <div class="ov-row"><span class="ov-lbl">Subject</span><span class="ov-val" id="suc-subject">—</span></div>
            <div class="ov-row"><span class="ov-lbl">Date</span><span class="ov-val" id="suc-date">—</span></div>
            <div class="ov-row"><span class="ov-lbl">Time In</span><span class="ov-val" id="suc-time">—</span></div>
            <div class="ov-row"><span class="ov-lbl">Face</span><span class="ov-val" style="color:#00a87a;">✅ Liveness Verified</span></div>
            <div class="ov-row"><span class="ov-lbl">Location</span><span class="ov-val" style="color:#00a87a;">✅ Verified</span></div>
        </div>
        <button onclick="recordAnother()" class="s-btn s-btn-green s-btn-block" style="margin-bottom:8px; padding:12px;">
            📋 Record Another Subject
        </button>
        <a href="home.php" class="s-btn s-btn-primary s-btn-block" style="padding:12px;">
            🏠 Back to Home
        </a>
    </div>
</div>

<!-- FAILED OVERLAY -->
<div class="overlay" id="ov-failed">
    <div class="ov-box failed">
        <div class="ov-icon">❌</div>
        <h2>Face Not Recognized</h2>
        <p>Too many failed attempts. Your face did not match your registered profile.</p>
        <div class="fail-hint">
            💡 Tips: Face the light source · Remove glasses/hat · Center your face · Do the gesture clearly
        </div>
        <button onclick="retryFace()" class="s-btn s-btn-primary s-btn-block" style="margin-bottom:8px; padding:12px;">
            🔄 Try Again
        </button>
        <a href="../student_logout.php" class="s-btn s-btn-danger s-btn-block" style="padding:11px;">
            🚪 Logout
        </a>
    </div>
</div>

<script src="../assets/js/face-api.min.js"></script>
<script>
// ════════════════════════════════════════════════════════
//  TAKE ATTENDANCE — Silent Location + Face Match + 2 Liveness Challenges
// ════════════════════════════════════════════════════════

const MODEL_URL = (function() { const p = window.location.pathname; if (p.includes('/AttendanceSystem/')) { return '/AttendanceSystem/assets/models'; } const d = (p.match(/\\\//g)||[]).length-1; return '../'.repeat(Math.max(d,1))+'assets/models'; })();
const DIST_THRESHOLD = 0.65;
const FACE_CONF_MIN  = 45;
const FRAMES_CONFIRM = 3;   // frames to confirm face before showing challenge
const FRAMES_HOLD    = 3;   // frames to hold gesture = passed
const MAX_FAILS      = 5;
const TOTAL_CH       = 2;

const CHALLENGE_POOL = [
    { id:'open_mouth', icon:'😮', text:'OPEN YOUR MOUTH', sub:'Open wide and hold it open' },
    { id:'blink',      icon:'😉', text:'BLINK YOUR EYES', sub:'Close both eyes for a moment' },
    { id:'smile',      icon:'😁', text:'GIVE A BIG SMILE',sub:'Show your teeth, smile wide' },
    { id:'look_left',  icon:'👈', text:'TURN HEAD LEFT',  sub:'Turn your head to your left' },
    { id:'look_right', icon:'👉', text:'TURN HEAD RIGHT', sub:'Turn your head to your right' },
];

// State
let locVerified  = false, userLat = null, userLon = null;
let faceMatcher  = null, brightness = 110;
let scanTimer    = null, isProc = false, scanDone = false;
let faceReadyCnt = 0,    faceReady = false;
let challenges   = [],   challIdx = 0, holdCnt = 0;
let failCnt      = 0,    prevPos = null;

// Clock
setInterval(() => {
    const el = document.getElementById('clock');
    if (el) el.textContent = new Date().toLocaleString('en-PH', {
        weekday:'long', year:'numeric', month:'long',
        day:'numeric', hour:'2-digit', minute:'2-digit', second:'2-digit'
    });
}, 1000);

// ── UI Helpers ────────────────────────────────────────
function setSbar(el, cls, msg) { el.className = 'sbar ' + cls; el.textContent = msg; }
function setStep(id, state) {
    if (typeof id === 'number') {
        const el = document.getElementById('step' + id);
        if (!el) return;
        el.className = 'step ' + state;
        el.querySelector('.step-num').textContent = state === 'done' ? '✓' : id;
    } else {
        const el = document.getElementById(id);
        if (el && state === 'done') el.classList.add('done');
    }
}
function setOval(cls) { document.getElementById('scan-oval').className = 'scan-oval ' + cls; }
function applyBrightness(b) {
    const v = document.getElementById('video');
    if (v) v.style.filter = `brightness(${b}%) contrast(115%)`;
}
function setConf(pct) {
    document.getElementById('conf-fill').style.cssText =
        `width:${pct}%; background:${pct>=55?'#00a87a':pct>=35?'#ffa726':'#ef5350'}`;
    document.getElementById('conf-pct').textContent = pct + '%';
}
function setBanner(icon, text, sub, bg) {
    const b = document.getElementById('ch-banner');
    b.innerHTML = `<div class="ci">${icon}</div><div class="ct">${text}</div><div class="cs">${sub}</div>`;
    b.style.background = bg || '';
}
function setChallStep(idx, state) {
    const el   = document.getElementById('cstep' + idx);
    const icon = document.getElementById('csi-icon' + idx);
    const lbl  = document.getElementById('csi-lbl' + idx);
    if (!el) return;
    el.className = 'ch-step-item ' + state;
    if (state === 'passed') { icon.textContent = '✅'; lbl.textContent = 'DONE'; }
    else if (state === 'current') { icon.textContent = challenges[idx]?.icon || '?'; lbl.textContent = 'DO THIS'; }
    else { icon.textContent = challenges[idx]?.icon || '❓'; lbl.textContent = 'STEP ' + (idx+1); }
}
function showTimer(pct, txt) {
    const w = document.getElementById('ch-timer');
    w.style.display = 'block';
    document.getElementById('ch-timer-fill').style.width = pct + '%';
    document.getElementById('ch-timer-lbl').textContent  = txt;
}
function hideTimer() { document.getElementById('ch-timer').style.display = 'none'; }
function pickChallenges() { return [...CHALLENGE_POOL].sort(() => Math.random()-0.5).slice(0,2); }

// ════════════════════════════════════════════════════
//  SILENT LOCATION CHECK (background)
// ════════════════════════════════════════════════════
function showLoadingState(msg) {
    document.getElementById('scan-loading').style.display = 'block';
    document.getElementById('scan-loading').textContent = msg;
}

function showLocationBlocked(distance, radius, teacherName) {
    document.getElementById('panel-face').classList.remove('visible');
    document.getElementById('loc-blocked').classList.add('visible');
    let msg = `You are ${distance}m away from campus. Must be within ${radius}m.`;
    if (teacherName) msg += ` (Set by: ${teacherName})`;
    document.getElementById('loc-blocked-msg').textContent = msg;
}

async function silentLocationCheck() {
    showLoadingState('📍 Verifying your location...');

    return new Promise((resolve) => {
        if (!navigator.geolocation) {
            resolve(true); // no GPS = allow (browser limitation)
            return;
        }
        navigator.geolocation.getCurrentPosition(
            async (pos) => {
                userLat = pos.coords.latitude;
                userLon = pos.coords.longitude;
                try {
                    const res  = await fetch('../api/verify_location.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ latitude: userLat, longitude: userLon })
                    });
                    const data = await res.json();
                    if (data.allowed) {
                        locVerified = true;
                        resolve(true);
                    } else {
                        showLocationBlocked(data.distance_meters, data.allowed_radius, data.teacher_name || null);
                        resolve(false);
                    }
                } catch(e) {
                    resolve(true); // server error = allow
                }
            },
            () => resolve(true), // GPS error = allow
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
        );
    });
}

async function retryLocation() {
    document.getElementById('loc-blocked').classList.remove('visible');
    const allowed = await silentLocationCheck();
    if (allowed) {
        document.getElementById('scan-loading').style.display = 'none';
        document.getElementById('panel-face').classList.add('visible');
        goFaceScan();
    }
}

// ════════════════════════════════════════════════════
//  STEP 2 — FACE SCAN + LIVENESS (formerly step 3)
// ════════════════════════════════════════════════════
async function goFaceScan() {
    // Camera security check
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        const msg = (!['localhost','127.0.0.1'].includes(location.hostname) && location.protocol !== 'https:')
            ? '❌ Camera blocked: Open via http://localhost/... instead of IP address. Or use HTTPS.'
            : '❌ Camera not available on this browser/device.';
        setSbar(document.getElementById('face-status'), 'sb-fail', msg);
        return;
    }
    setStep(2,'active');
    document.getElementById('panel-face').classList.add('visible');

    const st = document.getElementById('face-status');
    setSbar(st, 'sb-load', '⏳ Loading face recognition...');
    applyBrightness(110);

    try {
        await Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
            faceapi.nets.faceLandmark68TinyNet.loadFromUri(MODEL_URL),
            faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL),
        ]);

        setSbar(st, 'sb-load', '📡 Loading your face profile...');

        const res  = await fetch('../api/get_my_face.php');
        const data = await res.json();

        if (!data.face_descriptor) {
            setSbar(st, 'sb-fail', '❌ Face not registered. Contact admin.');
            setOval('nomatch'); return;
        }

        const desc    = new Float32Array(JSON.parse(data.face_descriptor));
        const labeled = [new faceapi.LabeledFaceDescriptors('student', [desc])];
        faceMatcher   = new faceapi.FaceMatcher(labeled, DIST_THRESHOLD);

        // Pick 2 random challenges
        challenges = pickChallenges();
        for (let i = 0; i < 2; i++) setChallStep(i, 'waiting');

        const stream = await navigator.mediaDevices.getUserMedia({
            video: { width:640, height:480, facingMode:'user' }
        });
        const videoEl = document.getElementById('video');
        const canvas  = document.getElementById('canvas');
        videoEl.srcObject = stream;

        videoEl.addEventListener('loadedmetadata', () => {
            canvas.width  = videoEl.videoWidth;
            canvas.height = videoEl.videoHeight;
        });

        videoEl.addEventListener('play', () => {
            setBanner('👀','LOOK AT CAMERA','Center your face in the oval and hold still');
            setStar(st, 'sb-scan', '🔍 Confirming your face...');
            setOval('idle');
            runScanLoop(videoEl, canvas);
        });

    } catch(err) {
        setSbar(document.getElementById('face-status'), 'sb-fail', '❌ ' + err.message);
        console.error(err);
    }
}

function setStar(el, cls, msg) { setSbar(el, cls, msg); } // alias

function runScanLoop(videoEl, canvas) {
    const ctx = canvas.getContext('2d');
    const st  = document.getElementById('face-status');

    scanTimer = setInterval(async () => {
        if (isProc || scanDone) return;
        isProc = true;
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        let curPos = null;

        try {
            const det = await faceapi
                .detectSingleFace(videoEl,
                    new faceapi.TinyFaceDetectorOptions({ inputSize:416, scoreThreshold:0.4 })
                )
                .withFaceLandmarks(true)
                .withFaceDescriptor();

            // No face
            if (!det) {
                faceReadyCnt = 0; holdCnt = 0;
                setOval('idle'); setConf(0); hideTimer();
                brightness = Math.min(brightness+5, 150); applyBrightness(brightness);
                if (faceReady) {
                    setBanner(challenges[challIdx].icon, challenges[challIdx].text,
                              challenges[challIdx].sub + ' — face lost, reposition');
                } else {
                    setBanner('👀','LOOK AT CAMERA','No face — move closer or face the camera');
                }
                setSbar(st, 'sb-scan', '🔍 No face detected — move closer');
                prevPos = null; isProc = false; return;
            }

            brightness = 110; applyBrightness(110);
            curPos = det.landmarks.positions;

            // Draw box
            const sx = canvas.width  / videoEl.videoWidth;
            const sy = canvas.height / videoEl.videoHeight;
            const b  = det.detection.box;
            ctx.strokeStyle = '#64b5f6'; ctx.lineWidth = 2;
            ctx.strokeRect(b.x*sx, b.y*sy, b.width*sx, b.height*sy);

            // Face match
            const match = faceMatcher.findBestMatch(det.descriptor);
            const conf  = Math.round((1 - match.distance) * 100);
            setConf(conf);

            const isMatch = (match.label === 'student' && match.distance <= DIST_THRESHOLD && conf >= FACE_CONF_MIN);

            if (!isMatch) {
                faceReadyCnt = 0; holdCnt = 0;
                setOval('nomatch'); hideTimer();
                failCnt++;
                const fc = document.getElementById('fail-ctr');
                fc.style.display = 'block';
                fc.textContent   = `⚠️ Failed attempts: ${failCnt}/${MAX_FAILS}`;
                setBanner('❌','FACE NOT RECOGNIZED',`${conf}% — attempt ${failCnt}/${MAX_FAILS}`,'linear-gradient(135deg,#b71c1c,#e53935)');
                setSbar(st, 'sb-fail', `❌ Face doesn't match (${conf}%) — ${failCnt}/${MAX_FAILS}`);

                if (failCnt >= MAX_FAILS) {
                    clearInterval(scanTimer); scanTimer = null;
                    stopScan(videoEl);
                    document.getElementById('ov-failed').classList.add('show');
                }
                prevPos = curPos; isProc = false; return;
            }

            // Face confirmed
            setOval('match');
            faceReadyCnt++;

            if (!faceReady) {
                setSbar(st, 'sb-ok', `✅ Face matched ${conf}% — confirming... (${faceReadyCnt}/${FRAMES_CONFIRM})`);
                setBanner('🔍','FACE MATCHED',`Hold still... confirming (${faceReadyCnt}/${FRAMES_CONFIRM})`);
                if (faceReadyCnt >= FRAMES_CONFIRM) {
                    faceReady = true; challIdx = 0;
                    showChallenge(challIdx);
                }
                prevPos = curPos; isProc = false; return;
            }

            // Liveness challenge
            const c      = challenges[challIdx];
            const passed = detectGesture(c.id, curPos, prevPos);

            if (passed) {
                holdCnt++;
                const pct = Math.round((holdCnt / FRAMES_HOLD) * 100);
                showTimer(pct, `Hold position... ${holdCnt}/${FRAMES_HOLD}`);
                setSbar(st, 'sb-ok', `✅ ${c.text} — hold it (${holdCnt}/${FRAMES_HOLD})`);

                if (holdCnt >= FRAMES_HOLD) {
                    holdCnt = 0;
                    setChallStep(challIdx, 'passed');
                    challIdx++;

                    if (challIdx >= TOTAL_CH) {
                        // All done — save attendance
                        scanDone = true;
                        clearInterval(scanTimer); scanTimer = null;
                        stopScan(videoEl);
                        hideTimer();
                        setStep(2,'done'); setStep('line2','done'); setStep(3,'done');
                        setBanner('✅','ALL VERIFIED!','Saving your attendance...','linear-gradient(135deg,#1b5e20,#2e7d32)');
                        setSbar(st, 'sb-ok', `✅ Liveness confirmed! Saving attendance...`);
                        setTimeout(saveAttendance, 700);
                    } else {
                        showChallenge(challIdx);
                    }
                }
            } else {
                holdCnt = 0; hideTimer();
                setBanner(c.icon, c.text, c.sub);
                setSbar(st, 'sb-scan', `🔍 Face matched (${conf}%) — do: ${c.icon} ${c.text}`);
            }

        } catch(e) { console.error('Scan error:', e); }

        prevPos = curPos; isProc = false;
    }, 280);
}

function showChallenge(idx) {
    const c = challenges[idx];
    setChallStep(idx, 'current');
    setBanner(c.icon, c.text, c.sub);
    setSbar(document.getElementById('face-status'), 'sb-scan',
        `Challenge ${idx+1}/${TOTAL_CH}: ${c.text} — ${c.sub}`);
    holdCnt = 0; hideTimer();
}

// ── Gesture Detectors ──────────────────────────────────
function earValue(pts) {
    const d = (a,b) => Math.hypot(a.x-b.x, a.y-b.y);
    return (d(pts[1],pts[5]) + d(pts[2],pts[4])) / (2*d(pts[0],pts[3]) + 0.001);
}

function detectGesture(id, pos, prev) {
    if (!pos) return false;
    try {
        const faceW = Math.abs(pos[16].x - pos[0].x)  + 0.001;
        const faceH = Math.abs(pos[8].y  - pos[27].y) + 0.001;
        switch (id) {
            case 'open_mouth': {
                return Math.abs(pos[62].y - pos[66].y) / faceH > 0.08;
            }
            case 'blink': {
                const avg = (earValue(pos.slice(36,42)) + earValue(pos.slice(42,48))) / 2;
                return avg < 0.25;
            }
            case 'smile': {
                const mW = Math.abs(pos[54].x - pos[48].x);
                const mH = Math.abs(pos[57].y - pos[51].y) + 0.001;
                return (mW/faceW) > 0.33 && (mW/mH) > 2.1;
            }
            case 'look_left': {
                const cx = (pos[36].x + pos[45].x) / 2;
                const ew = pos[45].x - pos[36].x + 0.001;
                return (pos[30].x - cx) / ew < -0.06;
            }
            case 'look_right': {
                const cx = (pos[36].x + pos[45].x) / 2;
                const ew = pos[45].x - pos[36].x + 0.001;
                return (pos[30].x - cx) / ew > 0.06;
            }
            default: return false;
        }
    } catch(e) { return false; }
}

// ── Save Attendance ────────────────────────────────────
async function saveAttendance() {
    const sel        = document.getElementById('subject-select');
    const subject_id = parseInt(sel.value);

    if (!subject_id) {
        setSbar(document.getElementById('face-status'), 'sb-fail', '❌ Please select a subject first.');
        scanDone = false;
        return;
    }

    const now    = new Date();
    const timeIn = now.toTimeString().slice(0,8);
    const h = now.getHours(), m = now.getMinutes();
    const nowMins = h * 60 + m;

    // Get schedule start/end time from selected subject
    const selData = JSON.parse(sel.options[sel.selectedIndex].dataset.subject || '{}');
    const startTime = selData.start_time || null;
    const endTime   = selData.end_time   || null;

    let status = 'present';

    if (startTime) {
        const [sH, sM] = startTime.split(':').map(Number);
        const startMins = sH * 60 + sM;
        const earlyMins = startMins - 30; // 30 mins before = earliest allowed
        const graceMins = startMins + 15; // 15 mins grace = still present

        // Check end time
        if (endTime) {
            const [eH, eM] = endTime.split(':').map(Number);
            const endMins = eH * 60 + eM;
            if (nowMins >= endMins) {
                setSbar(document.getElementById('face-status'), 'sb-fail', '❌ Class has already ended. Time-in is no longer allowed.');
                scanDone = false;
                return;
            }
        }

        // Too early
        if (nowMins < earlyMins) {
            const diff = earlyMins - nowMins;
            setSbar(document.getElementById('face-status'), 'sb-fail', `❌ Too early. You can time-in ${diff} minute(s) before class starts.`);
            scanDone = false;
            return;
        }

        // Late or present
        status = nowMins > graceMins ? 'late' : 'present';
    } else {
        // No schedule — fallback: late if after 8:15 AM
        status = (h > 8 || (h === 8 && m > 15)) ? 'late' : 'present';
    }

    try {
        const res    = await fetch('../api/student_attendance.php', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({
                subject_id,
                time_in:  timeIn,
                status,
                latitude:  userLat,
                longitude: userLon
            })
        });
        const result = await res.json();
        if (result.success) {
            showSuccess(result.subject_name || sel.options[sel.selectedIndex].text, timeIn, status, null);
        } else {
            showSuccess(sel.options[sel.selectedIndex].text, timeIn, status, result.message);
        }
    } catch(err) {
        setSbar(document.getElementById('face-status'), 'sb-fail', '❌ Network error: ' + err.message);
        scanDone = false;
    }
}

function showSuccess(subject, timeIn, status, note) {
    const sl = status === 'late' ? 'LATE' : 'PRESENT';
    const sc = status === 'late' ? '#e65100' : '#00a87a';
    document.getElementById('suc-status').textContent = sl;
    document.getElementById('suc-status').style.color = sc;
    document.getElementById('suc-name').textContent    = '<?= htmlspecialchars($student_name, ENT_QUOTES) ?>';
    document.getElementById('suc-id').textContent      = '<?= htmlspecialchars($student_sid,  ENT_QUOTES) ?>';
    document.getElementById('suc-subject').textContent = subject;
    document.getElementById('suc-date').textContent    = new Date().toLocaleDateString('en-PH',{weekday:'short',year:'numeric',month:'long',day:'numeric'});
    document.getElementById('suc-time').textContent    = timeIn;
    if (note) {
        const row = document.createElement('div');
        row.className = 'ov-row';
        row.innerHTML = `<span class="ov-lbl" style="color:#e65100">Note</span><span class="ov-val" style="color:#e65100;font-size:0.76rem">${note}</span>`;
        document.querySelector('#ov-success .ov-details').appendChild(row);
    }
    document.getElementById('ov-success').classList.add('show');
}

// ── Record another subject ─────────────────────────────
function recordAnother() {
    document.getElementById('ov-success').classList.remove('show');
    locVerified  = false; userLat = null; userLon = null;
    faceReadyCnt = 0; faceReady  = false;
    challIdx     = 0; holdCnt   = 0;
    failCnt      = 0; scanDone  = false;
    isProc       = false; brightness = 110; prevPos = null;

    for (let i = 0; i < 2; i++) setChallStep(i, 'waiting');

    setStep(2,'active'); setStep(3,'');
    document.getElementById('line2').className = 'step-line';
    document.getElementById('panel-face').classList.remove('visible');

    document.getElementById('fail-ctr').style.display = 'none';
    setConf(0); hideTimer();

    // Remove extra note rows
    document.querySelectorAll('#ov-success .ov-details .ov-row').forEach(el => {
        if (el.querySelector('[style*="e65100"]')) el.remove();
    });

    // Re-run silent location check
    silentLocationCheck().then(allowed => {
        if (allowed) {
            document.getElementById('scan-loading').style.display = 'none';
            document.getElementById('panel-face').classList.add('visible');
            goFaceScan();
        }
    });
}

function retryFace() {
    document.getElementById('ov-failed').classList.remove('show');
    faceReadyCnt = 0; faceReady = false;
    challIdx     = 0; holdCnt  = 0;
    failCnt      = 0; scanDone = false;
    isProc       = false; brightness = 110; prevPos = null;
    for (let i = 0; i < 2; i++) setChallStep(i, 'waiting');
    document.getElementById('fail-ctr').style.display = 'none';
    setConf(0); setOval('idle'); hideTimer();
    setSbar(document.getElementById('face-status'), 'sb-load', '🔄 Restarting...');
    setBanner('👀','LOOK AT CAMERA','Center your face in the oval');
    goFaceScan();
}

function stopScan(videoEl) {
    if (videoEl && videoEl.srcObject) videoEl.srcObject.getTracks().forEach(t => t.stop());
}

// ── Load enrolled subjects ────────────────────────────
let enrolledSubjects = [];

async function loadSubjects() {
    try {
        const res  = await fetch('../api/get_enrolled_subjects.php');
        const data = await res.json();
        enrolledSubjects = data.subjects || [];
        const sel = document.getElementById('subject-select');
        sel.innerHTML = '';

        if (enrolledSubjects.length === 0) {
            sel.innerHTML = '<option value="">No enrolled subjects. Contact your teacher.</option>';
            return;
        }

        const todaySubjects = enrolledSubjects.filter(s => s.start_time);
        const otherSubjects = enrolledSubjects.filter(s => !s.start_time);

        sel.innerHTML = '<option value="">— Select a subject to attend —</option>';

        if (todaySubjects.length > 0) {
            const grp = document.createElement('optgroup');
            grp.label = `📅 Today's Schedule (${data.day})`;
            todaySubjects.forEach(s => addOption(grp, s));
            sel.appendChild(grp);
        }

        if (otherSubjects.length > 0) {
            const grp2 = document.createElement('optgroup');
            grp2.label = '📚 Other Enrolled Subjects';
            otherSubjects.forEach(s => addOption(grp2, s));
            sel.appendChild(grp2);
        }
    } catch(err) {
        document.getElementById('subject-select').innerHTML =
            '<option value="">Failed to load subjects.</option>';
        console.error(err);
    }
}

function addOption(parent, s) {
    const opt = document.createElement('option');
    opt.value = s.subject_id;
    opt.dataset.subject = JSON.stringify(s);
    const time = s.start_time ? ` · ${fmt(s.start_time)}–${fmt(s.end_time)}` : '';
    opt.textContent = `${s.subject_name} (${s.section})${time}`;
    if (s.already_recorded > 0) { opt.textContent += ' ✅'; opt.style.color = '#2e7d32'; }
    parent.appendChild(opt);
}

function onSubjectChange(sel) {
    const infoCard = document.getElementById('subject-info');
    if (!sel.value) { infoCard.classList.remove('show'); return; }
    const s = JSON.parse(sel.options[sel.selectedIndex].dataset.subject || '{}');
    document.getElementById('si-name').textContent = `${s.subject_name} — Section ${s.section}`;
    const timeStr = s.start_time ? ` · ${fmt(s.start_time)} – ${fmt(s.end_time)}` : '';
    const room    = s.room ? ` · Room ${s.room}` : '';
    document.getElementById('si-details').innerHTML =
        `👨‍🏫 ${s.teacher_name} &nbsp;·&nbsp; ${s.course || ''} Yr${s.year_level}${timeStr}${room}`;
    document.getElementById('si-already').style.display = s.already_recorded > 0 ? 'block' : 'none';
    infoCard.classList.add('show');
}

function fmt(t) {
    if (!t) return '';
    const [h, m] = t.split(':');
    const hr = parseInt(h);
    return `${hr > 12 ? hr-12 : (hr===0?12:hr)}:${m} ${hr>=12?'PM':'AM'}`;
}

window.addEventListener('load', async () => {
    await loadSubjects();
    const allowed = await silentLocationCheck();
    if (allowed) {
        document.getElementById('scan-loading').style.display = 'none';
        document.getElementById('panel-face').classList.add('visible');
        goFaceScan();
    }
});
</script>

</main>
</body>
</html>
