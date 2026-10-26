<?php
session_start();

if (!isset($_SESSION['login_student_id'])) {
    header('Location: student_login.php');
    exit;
}

require_once 'config/database.php';
$conn = getConnection();

$sid_db       = intval($_SESSION['login_student_id']);
$student_name = $_SESSION['login_student_name'] ?? '';
$via_google   = $_SESSION['login_via_google']   ?? false;

$res     = $conn->query("SELECT * FROM students WHERE id=$sid_db AND is_active=1");
$student = ($res && $res->num_rows) ? $res->fetch_assoc() : null;

if (!$student) {
    session_destroy();
    header('Location: student_login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Face Verification — AttendEase</title>
    <style>
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }

        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: linear-gradient(135deg, #0a0e2e 0%, #1565c0 60%, #0d47a1 100%);
            min-height: 100vh;
            display: flex; flex-direction: column;
            justify-content: center; align-items: center;
            padding: 20px;
        }

        .wrap { width: 100%; max-width: 440px; }

        /* ── Brand ── */
        .brand { text-align:center; margin-bottom:16px; color:white; }
        .brand .logo {
            width:44px; height:44px;
            background:linear-gradient(135deg,#00b4d8,#1565c0);
            border-radius:12px; display:inline-flex;
            align-items:center; justify-content:center;
            font-size:1.4rem; margin-bottom:6px;
        }
        .brand h1 { font-size:1.1rem; font-weight:800; }
        .brand h1 span { color:#00b4d8; }
        .brand p { font-size:0.76rem; opacity:0.5; margin-top:2px; }

        /* ── Card ── */
        .card {
            background:white; border-radius:20px;
            padding:24px 22px;
            box-shadow:0 24px 70px rgba(0,0,0,0.3);
        }

        /* ── Steps ── */
        .steps { display:flex; justify-content:center; align-items:center; margin-bottom:18px; }
        .step  {
            display:flex; flex-direction:column; align-items:center;
            gap:3px; font-size:0.64rem; color:#ccc; font-weight:700;
            min-width:48px; text-align:center;
        }
        .step-num {
            width:26px; height:26px; border-radius:50%;
            background:#eee; color:#bbb;
            display:flex; align-items:center; justify-content:center;
            font-size:0.74rem; font-weight:800; transition:all 0.3s;
        }
        .step.done .step-num   { background:#00a87a; color:white; }
        .step.active .step-num { background:#1565c0; color:white; box-shadow:0 0 0 3px #bbdefb; }
        .step.done   { color:#00a87a; }
        .step.active { color:#1565c0; }
        .s-line { flex:1; height:3px; background:#eee; margin-bottom:14px; max-width:34px; }
        .s-line.done { background:#00a87a; }

        /* ── Greeting ── */
        .greeting { text-align:center; margin-bottom:14px; }
        .greeting h3 { font-size:0.95rem; color:#0a0e2e; font-weight:800; margin-bottom:2px; }
        .greeting p  { font-size:0.78rem; color:#888; }
        .g-chip {
            display:inline-flex; align-items:center; gap:5px;
            background:#f0f4ff; border:1px solid #c5cae9;
            padding:3px 10px; border-radius:20px;
            font-size:0.71rem; color:#3949ab; font-weight:600; margin-top:5px;
        }

        /* ── Challenge progress bar ── */
        .ch-progress {
            display:flex; align-items:center; gap:8px;
            margin-bottom:12px;
        }
        .ch-step-item {
            flex:1; border-radius:8px;
            padding:10px 8px; text-align:center;
            border:2px solid #eee; transition:all 0.3s;
            background:#fafafa;
        }
        .ch-step-item .csi-icon { font-size:1.6rem; margin-bottom:3px; }
        .ch-step-item .csi-lbl  { font-size:0.65rem; font-weight:700; color:#aaa; }
        .ch-step-item.waiting   { border-color:#eee; }
        .ch-step-item.current   { border-color:#1565c0; background:#f0f4ff; }
        .ch-step-item.current .csi-lbl { color:#1565c0; }
        .ch-step-item.passed    { border-color:#00a87a; background:#e8fdf5; }
        .ch-step-item.passed .csi-lbl { color:#00a87a; }
        .ch-arrow { color:#ddd; font-size:1.1rem; flex-shrink:0; }

        /* ── Challenge instruction banner ── */
        .ch-banner {
            background:linear-gradient(135deg,#0a0e2e,#1565c0);
            border-radius:12px; padding:13px 14px;
            text-align:center; color:white;
            margin-bottom:12px; min-height:64px;
            display:flex; flex-direction:column;
            align-items:center; justify-content:center;
            gap:3px; transition:background 0.4s;
        }
        .ch-banner .ci { font-size:1.8rem; line-height:1; }
        .ch-banner .ct { font-size:0.96rem; font-weight:800; letter-spacing:0.5px; }
        .ch-banner .cs { font-size:0.72rem; opacity:0.75; }

        /* ── Camera ── */
        .cam-wrap {
            position:relative; border-radius:14px;
            overflow:hidden; background:#111;
            aspect-ratio:4/3; margin-bottom:10px;
        }
        #fv-video  { width:100%; height:100%; object-fit:cover; display:block; }
        #fv-canvas { position:absolute; top:0; left:0; width:100%; height:100%; pointer-events:none; }

        .scan-oval {
            position:absolute; top:50%; left:50%;
            transform:translate(-50%,-52%);
            width:155px; height:190px; border-radius:50%;
            border:3px dashed rgba(255,255,255,0.45);
            pointer-events:none; z-index:10; transition:all 0.3s;
        }
        .scan-oval.idle    { animation:opulse 1.5s infinite; }
        .scan-oval.match   { border-color:#00c896; border-style:solid; box-shadow:0 0 20px rgba(0,200,150,0.5); }
        .scan-oval.nomatch { border-color:#ef5350; border-style:solid; }
        @keyframes opulse { 0%,100%{border-color:rgba(100,181,246,0.45);} 50%{border-color:rgba(100,181,246,1);} }

        /* ── Conf bar ── */
        .conf-wrap { margin-bottom:10px; }
        .conf-bar  { height:7px; background:#eee; border-radius:4px; overflow:hidden; }
        .conf-fill { height:100%; width:0%; border-radius:4px; transition:width 0.3s, background 0.3s; }
        .conf-row  { display:flex; justify-content:space-between; font-size:0.71rem; color:#aaa; margin-top:3px; }

        /* ── Status ── */
        .fv-stat {
            text-align:center; padding:10px 12px;
            border-radius:9px; font-size:0.84rem;
            font-weight:700; margin-bottom:10px; transition:all 0.3s;
        }
        .st-load { background:#e3f2fd; color:#0277bd; }
        .st-scan { background:#fff8e1; color:#e65100; }
        .st-ok   { background:#e8fdf5; color:#00a87a; }
        .st-fail { background:#ffebee; color:#e53935; }

        /* ── Challenge timer bar ── */
        .ch-timer-wrap { margin-bottom:10px; display:none; }
        .ch-timer-bar  { height:5px; background:#eee; border-radius:3px; overflow:hidden; }
        .ch-timer-fill { height:100%; width:0%; background:#1565c0; border-radius:3px; transition:width 0.15s linear; }
        .ch-timer-lbl  { font-size:0.68rem; color:#aaa; text-align:center; margin-top:3px; }

        .fail-ctr { text-align:center; font-size:0.72rem; color:#e53935; margin-bottom:8px; }

        .btn-back {
            display:block; width:100%; padding:10px;
            border:1px solid rgba(229,57,53,0.3);
            background:rgba(229,57,53,0.06);
            color:#e53935; border-radius:9px;
            font-size:0.86rem; font-weight:700;
            cursor:pointer; text-align:center;
            text-decoration:none; transition:all 0.2s;
        }
        .btn-back:hover { background:#e53935; color:white; }

        /* ── Overlay ── */
        .overlay { display:none; position:fixed; inset:0; z-index:9999; justify-content:center; align-items:center; background:rgba(0,0,0,0.65); backdrop-filter:blur(6px); }
        .overlay.show { display:flex; animation:fIn 0.25s ease; }
        @keyframes fIn { from{opacity:0;} to{opacity:1;} }
        .ov-box {
            background:white; border-radius:22px; padding:32px 24px;
            max-width:340px; width:90%; text-align:center;
            animation:pIn 0.4s cubic-bezier(.175,.885,.32,1.275);
            box-shadow:0 24px 60px rgba(0,0,0,0.3);
        }
        @keyframes pIn { from{transform:scale(0.7);opacity:0;} to{transform:scale(1);opacity:1;} }
        .ov-box.fail { border-top:6px solid #e53935; }
        .ov-icon { font-size:3.8rem; margin-bottom:10px; }
        .ov-box h2 { font-size:1.15rem; color:#e53935; margin-bottom:8px; }
        .ov-box p  { color:#666; font-size:0.83rem; margin-bottom:12px; line-height:1.6; }
        .ov-hint   { background:#fff1f1; border-radius:8px; padding:10px 12px; font-size:0.77rem; color:#e53935; margin-bottom:12px; line-height:1.5; }
        .btn-retry  { background:linear-gradient(135deg,#00b4d8,#1565c0); color:white; border:none; border-radius:10px; padding:11px 20px; font-size:0.88rem; font-weight:700; cursor:pointer; width:100%; margin-bottom:8px; }
        .btn-cancel { background:none; border:1px solid #ccc; color:#777; border-radius:10px; padding:9px 18px; font-size:0.82rem; cursor:pointer; width:100%; }
    </style>
</head>
<body>
<div class="wrap">

    <div class="brand">
        <div class="logo"><img src="assets/img/logo.png" style="width:100%;height:100%;object-fit:cover;border-radius:8px;" alt="Logo"></div>
        <h1>Attend<span>Ease</span></h1>
        <p>Face Recognition Attendance System</p>
    </div>

    <div class="card">

        <!-- Steps -->
        <div class="steps">
            <div class="step done"><div class="step-num">✓</div>Login</div>
            <div class="s-line done"></div>
            <div class="step done"><div class="step-num">✓</div>Google</div>
            <div class="s-line done"></div>
            <div class="step active"><div class="step-num">3</div>Face</div>
            <div class="s-line" id="sl3"></div>
            <div class="step" id="step4"><div class="step-num">4</div>Done</div>
        </div>

        <!-- Greeting -->
        <div class="greeting">
            <h3>Hello, <?= htmlspecialchars(explode(' ',$student_name)[0]) ?>! 👋</h3>
            <p>2 quick face challenges to confirm it's you.</p>
            <?php if ($via_google && !empty($_SESSION['login_google_email'])): ?>
            <div class="g-chip">🔵 <?= htmlspecialchars($_SESSION['login_google_email']) ?></div>
            <?php endif; ?>
        </div>

        <!-- Challenge progress (2 steps) -->
        <div class="ch-progress" id="ch-progress">
            <div class="ch-step-item waiting" id="cstep0">
                <div class="csi-icon" id="csi-icon0">❓</div>
                <div class="csi-lbl" id="csi-lbl0">STEP 1</div>
            </div>
            <div class="ch-arrow">›</div>
            <div class="ch-step-item waiting" id="cstep1">
                <div class="csi-icon" id="csi-icon1">❓</div>
                <div class="csi-lbl" id="csi-lbl1">STEP 2</div>
            </div>
        </div>

        <!-- Challenge instruction -->
        <div class="ch-banner" id="ch-banner">
            <div class="ci">⏳</div>
            <div class="ct">Loading...</div>
            <div class="cs">Please wait</div>
        </div>

        <!-- Challenge timer -->
        <div class="ch-timer-wrap" id="ch-timer-wrap">
            <div class="ch-timer-bar"><div class="ch-timer-fill" id="ch-timer-fill"></div></div>
            <div class="ch-timer-lbl" id="ch-timer-lbl">Hold position...</div>
        </div>

        <!-- Camera -->
        <div class="cam-wrap">
            <video id="fv-video" autoplay muted playsinline></video>
            <canvas id="fv-canvas"></canvas>
            <div class="scan-oval idle" id="scan-oval"></div>
        </div>

        <!-- Confidence -->
        <div class="conf-wrap">
            <div class="conf-bar"><div class="conf-fill" id="cf"></div></div>
            <div class="conf-row">
                <span>Face Match Confidence</span>
                <span id="cf-pct">0%</span>
            </div>
        </div>

        <div class="fv-stat st-load" id="fv-stat">⏳ Loading models...</div>
        <div class="fail-ctr" id="fail-ctr" style="display:none;"></div>

        <a href="student_login.php?cancel=1" class="btn-back">← Back to Login</a>
    </div>
</div>

<!-- Failure overlay -->
<div class="overlay" id="ov-fail">
    <div class="ov-box fail">
        <div class="ov-icon">❌</div>
        <h2>Verification Failed</h2>
        <p>Your face did not match after multiple attempts.</p>
        <div class="ov-hint">
            💡 Tips:<br>
            • Face a light source — avoid backlighting<br>
            • Remove glasses, hat, or mask<br>
            • Center your face in the oval<br>
            • Do the challenge gesture clearly and slowly
        </div>
        <button class="btn-retry"  onclick="retryAll()">🔄 Try Again</button>
        <button class="btn-cancel" onclick="window.location.href='student_login.php?cancel=1'">← Back to Login</button>
    </div>
</div>

<script src="assets/js/face-api.min.js"></script>
<script>
// ══════════════════════════════════════════════════════
//  FACE VERIFICATION — Google OAuth + 2 Liveness Challenges
//  Flow:
//    1. Camera opens, face matched against stored descriptor
//    2. First challenge (random): smile / blink / open_mouth / look_left / look_right
//    3. Second challenge (different): different random challenge
//    4. Both passed → login
// ══════════════════════════════════════════════════════

const MODEL_URL = (function() { const p = window.location.pathname; if (p.includes('/AttendanceSystem/')) { return '/AttendanceSystem/assets/models'; } const d = (p.match(/\\\//g)||[]).length-1; return '../'.repeat(Math.max(d,1))+'assets/models'; })();
const DIST_THRESHOLD = 0.65;   // lenient match threshold
const FACE_CONF_MIN  = 45;     // min % to attempt challenge
const HOLD_FRAMES    = 3;      // frames to hold gesture = passed
const MAX_FAILS      = 5;      // max face mismatches before lockout
const TOTAL_CHALLENGES = 2;

// Only use challenges that work reliably with tiny landmarks
const POOL = [
    { id:'open_mouth', icon:'😮', text:'OPEN YOUR MOUTH', sub:'Open wide and hold it open' },
    { id:'blink',      icon:'😉', text:'BLINK YOUR EYES', sub:'Close both eyes for a moment' },
    { id:'smile',      icon:'😁', text:'GIVE A BIG SMILE',sub:'Show your teeth, smile wide' },
    { id:'look_left',  icon:'👈', text:'TURN HEAD LEFT',  sub:'Turn your head to your left' },
    { id:'look_right', icon:'👉', text:'TURN HEAD RIGHT', sub:'Turn your head to your right' },
];

// State
let faceMatcher  = null;
let videoStream  = null;
let timer        = null;
let isProc       = false;
let verified     = false;
let brightness   = 110;

let challenges   = [];   // 2 picked
let challIdx     = 0;    // which challenge we're on (0 or 1)
let holdCount    = 0;    // consecutive passing frames
let faceReady    = false;
let faceReadyCnt = 0;
let failCount    = 0;
let prevPos      = null;

const video  = document.getElementById('fv-video');
const canvas = document.getElementById('fv-canvas');
const oval   = document.getElementById('scan-oval');
const statEl = document.getElementById('fv-stat');
const banner = document.getElementById('ch-banner');

// ── UI helpers ────────────────────────────────────────

function setStat(cls, msg) {
    statEl.className   = 'fv-stat ' + cls;
    statEl.textContent = msg;
}

function setOval(cls) { oval.className = 'scan-oval ' + cls; }

function setBrightness(b) {
    video.style.filter = `brightness(${b}%) contrast(115%) saturate(90%)`;
}

function setConf(pct) {
    document.getElementById('cf').style.cssText =
        `width:${pct}%; background:${pct>=55?'#00a87a':pct>=35?'#ffa726':'#ef5350'}`;
    document.getElementById('cf-pct').textContent = pct + '%';
}

function setBanner(icon, text, sub, bg) {
    banner.innerHTML = `<div class="ci">${icon}</div><div class="ct">${text}</div><div class="cs">${sub}</div>`;
    banner.style.background = bg || '';
}

function setChallStep(idx, state) {
    const el   = document.getElementById('cstep' + idx);
    const icon = document.getElementById('csi-icon' + idx);
    const lbl  = document.getElementById('csi-lbl' + idx);
    if (!el) return;
    el.className = 'ch-step-item ' + state;
    if (state === 'passed') {
        icon.textContent = '✅';
        lbl.textContent  = 'DONE';
    } else if (state === 'current') {
        icon.textContent = challenges[idx]?.icon || '?';
        lbl.textContent  = 'DO THIS';
    } else {
        icon.textContent = challenges[idx]?.icon || '❓';
        lbl.textContent  = 'STEP ' + (idx + 1);
    }
}

function setTimer(pct, lbl) {
    const wrap = document.getElementById('ch-timer-wrap');
    const fill = document.getElementById('ch-timer-fill');
    const txt  = document.getElementById('ch-timer-lbl');
    wrap.style.display = 'block';
    fill.style.width   = pct + '%';
    txt.textContent    = lbl;
}

function hideTimer() {
    document.getElementById('ch-timer-wrap').style.display = 'none';
}

// ── Pick 2 different challenges ───────────────────────
function pickChallenges() {
    const shuffled = [...POOL].sort(() => Math.random() - 0.5);
    return shuffled.slice(0, 2);
}

// ── Boot ─────────────────────────────────────────────
window.addEventListener('load', async () => {
    setStat('st-load', '⏳ Loading face recognition models...');
    setBrightness(110);
        // ── Camera security check — must be localhost or HTTPS ──
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            const isLocalhost = ['localhost','127.0.0.1'].includes(location.hostname);
            const isHttps     = location.protocol === 'https:';
            if (!isLocalhost && !isHttps) {
                setStat('st-fail', '❌ Camera requires HTTPS or localhost. Current: ' + location.protocol + '//' + location.hostname);
                setBanner('🔒', 'HTTPS REQUIRED', 'Open via https:// or use localhost instead of IP address');
            } else {
                setStat('st-fail', '❌ Camera not available on this browser.');
            }
            return;
        }


    try {
        await Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
            faceapi.nets.faceLandmark68TinyNet.loadFromUri(MODEL_URL),
            faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL),
        ]);

        setStat('st-load', '📡 Loading your face profile...');

        const res  = await fetch('api/get_my_face.php');
        const data = await res.json();

        if (!data.face_descriptor) {
            setStat('st-fail', '❌ Face not registered. Contact admin.');
            setBanner('❌', 'No face data found', 'Contact your administrator');
            return;
        }

        const desc    = new Float32Array(JSON.parse(data.face_descriptor));
        const labeled = [new faceapi.LabeledFaceDescriptors('student', [desc])];
        faceMatcher   = new faceapi.FaceMatcher(labeled, DIST_THRESHOLD);

        // Pick challenges
        challenges = pickChallenges();
        // Pre-set icons in step items
        for (let i = 0; i < 2; i++) setChallStep(i, 'waiting');

        // Start camera
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            setStat('st-fail', '❌ Camera not available. This page must be opened over HTTPS or on localhost.');
            setBanner('❌', 'HTTPS REQUIRED', 'Camera access requires a secure connection (https://)');
            return;
        }
        videoStream = await navigator.mediaDevices.getUserMedia({
            video: { width:640, height:480, facingMode:'user' }
        });
        video.srcObject = videoStream;

        video.addEventListener('loadedmetadata', () => {
            canvas.width  = video.videoWidth;
            canvas.height = video.videoHeight;
        });

        video.addEventListener('play', () => {
            setBanner('👀', 'LOOK AT CAMERA', 'Center your face in the oval and hold still');
            setStat('st-scan', '🔍 Confirming your face...');
            setOval('idle');
            startLoop();
        });

    } catch(err) {
        setStat('st-fail', '❌ Error: ' + err.message);
        console.error(err);
    }
});

// ── Detection loop ────────────────────────────────────
function startLoop() {
    const ctx = canvas.getContext('2d');

    timer = setInterval(async () => {
        if (isProc || verified) return;
        isProc = true;
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        let curPos = null;

        try {
            const det = await faceapi
                .detectSingleFace(video,
                    new faceapi.TinyFaceDetectorOptions({ inputSize:416, scoreThreshold:0.4 })
                )
                .withFaceLandmarks(true)
                .withFaceDescriptor();

            // ── No face ──────────────────────────────
            if (!det) {
                faceReadyCnt = 0;
                holdCount    = 0;
                setOval('idle');
                setConf(0);
                hideTimer();
                brightness = Math.min(brightness + 5, 150);
                setBrightness(brightness);
                setStat('st-scan', '🔍 No face detected — move closer');
                if (faceReady) {
                    setBanner(challenges[challIdx].icon, challenges[challIdx].text,
                              challenges[challIdx].sub + ' — face lost, reposition');
                } else {
                    setBanner('👀','LOOK AT CAMERA','No face — move closer and face the camera');
                }
                prevPos  = null;
                isProc   = false;
                return;
            }

            // Face found
            brightness = 110; setBrightness(110);

            curPos     = det.landmarks.positions;

            // Draw detection box
            const sx = canvas.width  / video.videoWidth;
            const sy = canvas.height / video.videoHeight;
            const b  = det.detection.box;
            ctx.strokeStyle = '#64b5f6'; ctx.lineWidth = 2;
            ctx.strokeRect(b.x*sx, b.y*sy, b.width*sx, b.height*sy);

            // ── Face match ────────────────────────────
            const match = faceMatcher.findBestMatch(det.descriptor);
            const conf  = Math.round((1 - match.distance) * 100);
            setConf(conf);

            const matched = (match.label === 'student' && match.distance <= DIST_THRESHOLD);

            if (!matched || conf < FACE_CONF_MIN) {
                faceReadyCnt = 0;
                holdCount    = 0;
                setOval('nomatch');
                hideTimer();
                failCount++;
                updateFailCtr();
                setBanner('❌', 'FACE NOT RECOGNIZED',
                    `${conf}% match — attempt ${failCount}/${MAX_FAILS}`,
                    'linear-gradient(135deg,#b71c1c,#e53935)');
                setStat('st-fail', `❌ Face doesn't match (${conf}%) — ${failCount}/${MAX_FAILS}`);

                if (failCount >= MAX_FAILS) {
                    clearInterval(timer);
                    timer = null;
                    stopCam();
                    document.getElementById('ov-fail').classList.add('show');
                }
                prevPos = curPos;
                isProc  = false;
                return;
            }

            // ── Face confirmed ─────────────────────────
            setOval('match');

            if (!faceReady) {
                faceReadyCnt++;
                setStat('st-ok', `✅ Face matched ${conf}% — confirming... (${faceReadyCnt}/3)`);
                setBanner('🔍','FACE MATCHED', `Hold still... confirming (${faceReadyCnt}/3)`);

                if (faceReadyCnt >= 3) {
                    faceReady = true;
                    challIdx  = 0;
                    showCurrentChallenge();
                }
                prevPos = curPos;
                isProc  = false;
                return;
            }

            // ── Liveness challenge ────────────────────
            const c      = challenges[challIdx];
            const passed = detectGesture(c.id, curPos, prevPos);

            if (passed) {
                holdCount++;
                const pct = Math.round((holdCount / HOLD_FRAMES) * 100);
                setTimer(pct, `Hold position... ${holdCount}/${HOLD_FRAMES}`);
                setStat('st-ok', `✅ ${c.text} — hold it (${holdCount}/${HOLD_FRAMES})`);

                if (holdCount >= HOLD_FRAMES) {
                    // Challenge passed!
                    holdCount = 0;
                    setChallStep(challIdx, 'passed');
                    challIdx++;

                    if (challIdx >= TOTAL_CHALLENGES) {
                        // All challenges done → login
                        verified = true;
                        clearInterval(timer);
                        timer = null;
                        stopCam();
                        hideTimer();
                        document.getElementById('sl3').classList.add('done');
                        document.getElementById('step4').querySelector('.step-num').textContent = '✓';
                        document.getElementById('step4').className = 'step done';
                        setBanner('✅', 'ALL VERIFIED!', 'Logging you in now...', 'linear-gradient(135deg,#1b5e20,#2e7d32)');
                        setStat('st-ok', `✅ Identity confirmed! (${conf}%) Logging in...`);
                        setTimeout(completeLogin, 700);
                    } else {
                        // Next challenge
                        showCurrentChallenge();
                    }
                }
            } else {
                holdCount = 0;
                hideTimer();
                setStat('st-scan', `🔍 Face matched (${conf}%) — do: ${c.icon} ${c.text}`);
                setBanner(c.icon, c.text, c.sub);
            }

        } catch(e) { console.error('Detection error:', e); }

        prevPos = curPos;
        isProc  = false;
    }, 280);
}

// ── Show current challenge ────────────────────────────
function showCurrentChallenge() {
    const c = challenges[challIdx];
    setChallStep(challIdx, 'current');
    setBanner(c.icon, c.text, c.sub);
    setStat('st-scan', `Challenge ${challIdx+1}/${TOTAL_CHALLENGES}: ${c.text} — ${c.sub}`);
    holdCount = 0;
    hideTimer();
}

// ── Gesture detectors ─────────────────────────────────
// All measurements normalized to face size for lighting/distance independence

function earValue(pts) {
    const d = (a, b) => Math.hypot(a.x - b.x, a.y - b.y);
    return (d(pts[1],pts[5]) + d(pts[2],pts[4])) / (2 * d(pts[0],pts[3]) + 0.001);
}

function detectGesture(id, pos, prev) {
    if (!pos) return false;
    try {
        // Normalization distances
        const faceW = Math.abs(pos[16].x - pos[0].x)  + 0.001;  // face width (jaw)
        const faceH = Math.abs(pos[8].y  - pos[27].y) + 0.001;  // chin to brow bridge

        switch (id) {

            case 'open_mouth': {
                // Inner lip vertical gap (62=upper inner, 66=lower inner)
                const innerGap = Math.abs(pos[62].y - pos[66].y);
                // Normalize by face height — open mouth should be > 8% of face height
                return (innerGap / faceH) > 0.08;
            }

            case 'blink': {
                // Eye Aspect Ratio — blink when EAR < 0.25
                const leftEAR  = earValue(pos.slice(36, 42));
                const rightEAR = earValue(pos.slice(42, 48));
                return (leftEAR + rightEAR) / 2 < 0.25;
            }

            case 'smile': {
                // Mouth corner-to-corner width relative to face width
                // AND mouth open height — smile = wide + slightly open
                const mouthW = Math.abs(pos[54].x - pos[48].x);
                const mouthH = Math.abs(pos[57].y - pos[51].y) + 0.001;
                const wRatio = mouthW / faceW;
                const aspect = mouthW / mouthH;
                // smile: mouth width > 33% of face, and wide aspect ratio
                return wRatio > 0.33 && aspect > 2.1;
            }

            case 'look_left': {
                // Nose tip x relative to midpoint of eye corners
                // When looking left, nose tip shifts LEFT of eye center
                const eyeMidX  = (pos[36].x + pos[45].x) / 2;
                const eyeSpanX = pos[45].x - pos[36].x + 0.001;
                const ratio    = (pos[30].x - eyeMidX) / eyeSpanX;
                // Looking left = ratio < -0.06 (nose tip is left of center)
                return ratio < -0.06;
            }

            case 'look_right': {
                const eyeMidX  = (pos[36].x + pos[45].x) / 2;
                const eyeSpanX = pos[45].x - pos[36].x + 0.001;
                const ratio    = (pos[30].x - eyeMidX) / eyeSpanX;
                // Looking right = ratio > 0.06
                return ratio > 0.06;
            }

            default: return false;
        }
    } catch(e) { return false; }
}

// ── Complete login ────────────────────────────────────
async function completeLogin() {
    setStat('st-load', '⏳ Completing login...');
    try {
        const res  = await fetch('api/face_login.php', { method:'POST' });
        const data = await res.json();
        if (data.success) {
            window.location.href = data.redirect || 'student/home.php';
        } else {
            setStat('st-fail', '❌ ' + (data.message || 'Login failed. Try again.'));
            // Don't reset verified — show error and let user retry manually
        }
    } catch(err) {
        setStat('st-fail', '❌ Network error: ' + err.message);
    }
}

// ── Retry ─────────────────────────────────────────────
function retryAll() {
    document.getElementById('ov-fail').classList.remove('show');

    // Reset state
    faceReady    = false;
    faceReadyCnt = 0;
    challIdx     = 0;
    holdCount    = 0;
    failCount    = 0;
    verified     = false;
    isProc       = false;
    brightness   = 110;
    prevPos      = null;

    // New random challenges
    challenges = pickChallenges();
    for (let i = 0; i < 2; i++) setChallStep(i, 'waiting');
    setConf(0); setOval('idle');
    hideTimer();
    document.getElementById('fail-ctr').style.display = 'none';
    setBanner('👀','LOOK AT CAMERA','Center your face in the oval');
    setStat('st-scan','🔍 Confirming your face...');

    // Restart camera
    restartCamera();
}

async function restartCamera() {
    stopCam();
    try {
        videoStream = await navigator.mediaDevices.getUserMedia({
            video: { width:640, height:480, facingMode:'user' }
        });
        video.srcObject = videoStream;
        video.addEventListener('play', () => startLoop(), { once:true });
    } catch(err) {
        setStat('st-fail', '❌ Camera error: ' + err.message);
    }
}

function updateFailCtr() {
    const el = document.getElementById('fail-ctr');
    el.style.display = 'block';
    el.textContent   = `⚠️ Failed attempts: ${failCount}/${MAX_FAILS}`;
}

function stopCam() {
    if (timer) { clearInterval(timer); timer = null; }
    if (videoStream) {
        videoStream.getTracks().forEach(t => t.stop());
        videoStream = null;
    }
}
</script>
</body>
</html>
