<?php
session_start();

if (isset($_SESSION['student_id'])) {
    header('Location: student/scan.php');
    exit;
}

require_once 'config/database.php';
$conn = getConnection();

$step    = 1;
$student = null;
$error   = '';

// ── STEP 1: Verify Student ID + Full Name ──────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify'])) {
    $sid  = $conn->real_escape_string(trim($_POST['student_id']));
    $name = trim($_POST['full_name']);

    $res = $conn->query("SELECT * FROM students WHERE student_id='$sid' AND is_active=1");

    if (!$res || $res->num_rows === 0) {
        $error = "Student ID not found. Contact your admin.";
    } else {
        $student = $res->fetch_assoc();
        if (strtolower(trim($student['full_name'])) !== strtolower($name)) {
            $error   = "Full name does not match our records.";
            $student = null;
        } elseif ($student['password'] && $student['face_descriptor']) {
            $error   = "Account already registered. Please login instead.";
            $student = null;
        } else {
            // Validate Gmail
            $gmail_input = trim($_POST['gmail'] ?? '');
            if (!filter_var($gmail_input, FILTER_VALIDATE_EMAIL)) {
                $error = "Please enter a valid Gmail address.";
            } else {
                $_SESSION['signup_student_id'] = $student['id'];
                $_SESSION['signup_gmail']      = $gmail_input;
                $step = 2;
            }
        }
    }
}

// ── STEP 2: Set Password → submitted via POST ──────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_password'])) {
    if (!isset($_SESSION['signup_student_id'])) {
        $error = "Session expired. Please start again.";
        $step  = 1;
    } else {
        $pw  = $_POST['password'];
        $pw2 = $_POST['confirm_password'];
        if (strlen($pw) < 6) {
            $error = "Password must be at least 6 characters.";
            $step  = 2;
        } elseif ($pw !== $pw2) {
            $error = "Passwords do not match.";
            $step  = 2;
        } else {
            $hashed = $conn->real_escape_string(password_hash($pw, PASSWORD_DEFAULT));
            $sid_db = intval($_SESSION['signup_student_id']);
            $gmail  = $conn->real_escape_string($_SESSION['signup_gmail'] ?? '');
            // if($conn->query("UPDATE students SET password='$hashed', google_email='$gmail', email='$gmail' WHERE id=$sid_db")){
            //     $error = 'Dili ma-save ang account details.';
            //     $step = 2;
            // } else {
            //     try {
            //         require_once __DIR__ . '/config/send_student_verification.php';
            //         sendStudentVerificationEmail($conn, $sid_db, $_SESSION['signup_gmail']);

            //         $_SESSION['signup_pw_done'] = true;
            //         $step = 3;
            //     } catch (RuntimeException $e) {
            //         error_log('Student verification email error: ' . $e->getMessage());
            //         $error = 'Na-save ang account pero wala napadala ang verification email. Susiha ang SMTP settings.';
            //         $step = 2;
            //     }
            // }

            if (!$conn->query("UPDATE students SET password='$hashed', google_email='$gmail', email='$gmail' WHERE id=$sid_db")) {
    $error = 'Could not save the account details.';
    $step = 2;
} else {
    try {
        require_once __DIR__ . '/config/send_student_verification.php';
        sendStudentVerificationEmail($conn, $sid_db, $_SESSION['signup_gmail']);

        $_SESSION['signup_pw_done'] = true;
        $step = 3;
    } catch (RuntimeException $e) {
        error_log('Student verification email error: ' . $e->getMessage());
        $error = 'Account saved, but the verification email could not be sent. Check the SMTP settings.';
        $step = 2;
    }
}


                  
               // ;
            $_SESSION['signup_pw_done'] = true;
            $step = 3;  // go to face registration
        }
    }
}

// Resume steps from session on GET
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_SESSION['signup_student_id'])) {
        $sid_db  = intval($_SESSION['signup_student_id']);
        $res     = $conn->query("SELECT * FROM students WHERE id=$sid_db");
        $student = ($res && $res->num_rows > 0) ? $res->fetch_assoc() : null;
        $step    = isset($_SESSION['signup_pw_done']) ? 3 : 2;
    }
}

// Load student for step 2/3
if (($step === 2 || $step === 3) && !$student && isset($_SESSION['signup_student_id'])) {
    $sid_db  = intval($_SESSION['signup_student_id']);
    $res     = $conn->query("SELECT * FROM students WHERE id=$sid_db");
    $student = ($res && $res->num_rows > 0) ? $res->fetch_assoc() : null;
}

$total_steps = 4;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Sign Up — Attendance System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #050818 0%, #0d1b6e 50%, #1a237e 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 24px 16px 40px;
        }

        .signup-wrap { width: 100%; max-width: 480px; }

        .signup-card {
            background: white;
            border-radius: 18px;
            padding: 36px 32px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.25);
        }

        .signup-header { text-align: center; margin-bottom: 24px; }
        .signup-icon   { font-size: 2.8rem; margin-bottom: 6px; }
        .signup-header h2 { font-size: 1.25rem; color: #1a237e; margin-bottom: 3px; }
        .signup-header p  { color: #888; font-size: 0.84rem; }

        /* ── Progress ── */
        .prog-bar {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 26px;
            gap: 0;
        }
        .prog-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
            min-width: 60px;
        }
        .prog-num {
            width: 32px; height: 32px;
            border-radius: 50%;
            background: #e0e0e0;
            color: #bbb;
            font-size: 0.82rem;
            font-weight: 700;
            display: flex; align-items: center; justify-content: center;
            transition: all 0.3s;
        }
        .prog-label { font-size: 0.68rem; color: #bbb; font-weight: 600; text-align: center; }
        .prog-line  { flex: 1; height: 3px; background: #e0e0e0; margin-bottom: 20px; max-width: 40px; transition: background 0.4s; }

        .prog-step.active .prog-num   { background: #1a237e; color: white; box-shadow: 0 0 0 4px #c5d0f0; }
        .prog-step.done   .prog-num   { background: #1565c0; color: white; }
        .prog-step.active .prog-label { color: #1a237e; font-weight: 700; }
        .prog-step.done   .prog-label { color: #1565c0; }
        .prog-line.done               { background: #1565c0; }

        /* ── Student info box ── */
        .stu-box {
            background: #f0f4ff;
            border: 1px solid #c5d0f0;
            border-radius: 10px;
            padding: 13px 16px;
            margin-bottom: 18px;
        }
        .stu-box .sn { font-weight: 700; color: #1a237e; font-size: 0.97rem; }
        .stu-box .sd { font-size: 0.81rem; color: #555; margin-top: 2px; }

        /* ── Password strength ── */
        .str-bar  { height: 5px; background: #eee; border-radius: 3px; margin-top: 6px; overflow: hidden; }
        .str-fill { height: 100%; width: 0%; border-radius: 3px; transition: width 0.3s, background 0.3s; }
        .str-lbl  { font-size: 0.74rem; color: #aaa; margin-top: 3px; }

        /* ── Face Camera (Step 3) ── */
        .camera-wrap {
            position: relative;
            width: 100%;
            aspect-ratio: 4/3;
            border-radius: 14px;
            overflow: hidden;
            background: #111;
            margin-bottom: 12px;
        }
        #reg-video  { width: 100%; height: 100%; object-fit: cover; display: block; }
        #reg-canvas { position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; }

        /* Face guide oval */
        .face-guide {
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -52%);
            width: 160px; height: 200px;
            border: 3px dashed rgba(255,255,255,0.5);
            border-radius: 50%;
            pointer-events: none;
            z-index: 10;
            transition: border-color 0.4s, border-style 0.4s;
        }
        .face-guide.ready   { border-color: #64b5f6; border-style: solid; }
        .face-guide.pulse   { animation: pulse-border 1s infinite; }
        @keyframes pulse-border {
            0%,100% { border-color: rgba(255,255,255,0.4); }
            50%      { border-color: rgba(100,181,246,0.9); }
        }

        /* Challenge banner */
        .challenge-banner {
            position: absolute;
            bottom: 0; left: 0; right: 0;
            background: rgba(0,0,0,0.65);
            color: white;
            text-align: center;
            padding: 10px 12px;
            font-size: 1rem;
            font-weight: 700;
            z-index: 20;
            backdrop-filter: blur(2px);
        }

        .camera-status {
            text-align: center;
            padding: 11px 14px;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 12px;
            transition: all 0.3s;
        }
        .cs-load    { background: #e3f2fd; color: #0277bd; }
        .cs-wait    { background: #fff8e1; color: #e65100; }
        .cs-go      { background: #e8edff; color: #1a237e; }
        .cs-fail    { background: #ffebee; color: #c62828; }

        /* Progress dots for liveness */
        .liveness-dots { display: flex; justify-content: center; gap: 8px; margin-bottom: 12px; }
        .ldot {
            width: 12px; height: 12px; border-radius: 50%;
            background: #e0e0e0; transition: background 0.3s, transform 0.3s;
        }
        .ldot.done    { background: #1565c0; transform: scale(1.2); }
        .ldot.active  { background: #1a237e; animation: ldot-pulse 0.8s infinite; }
        @keyframes ldot-pulse { 0%,100%{ transform:scale(1); } 50%{ transform:scale(1.4); } }

        /* ── Buttons ── */
        .btn-green {
            background: linear-gradient(135deg, #1a237e, #1565c0);
            color: white; padding: 13px; font-size: 1rem;
            border: none; border-radius: 8px; width: 100%;
            cursor: pointer; font-weight: 600;
            transition: opacity 0.2s, transform 0.2s;
        }
        .btn-green:hover  { opacity: 0.9; transform: translateY(-1px); }
        .btn-green:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }

        .bottom-link { text-align: center; margin-top: 18px; font-size: 0.82rem; color: #999; }
        .bottom-link a { color: #1a237e; font-weight: 600; text-decoration: none; }

        /* ── Success (Step 4) ── */
        .done-box { text-align: center; padding: 10px 0; }
        .done-icon { font-size: 5rem; animation: pop 0.5s cubic-bezier(.175,.885,.32,1.275); }
        @keyframes pop { from{ transform:scale(0); } to{ transform:scale(1); } }
    </style>
</head>
<body>
<div class="signup-wrap">
<div class="signup-card">

    <div class="signup-header">
        <div class="signup-icon"><img src="assets/img/logo.png" style="width:56px;height:56px;object-fit:cover;border-radius:12px;margin-bottom:6px;" alt="Logo"></div>
        <h2>Student Sign Up</h2>
        <p>Create your attendance account</p>
    </div>

    <!-- ── Progress Bar ── -->
    <?php
    $labels = ['Verify','Password','Face Scan','Done'];
    echo '<div class="prog-bar">';
    for ($i = 1; $i <= 4; $i++) {
        $cls = $i < $step ? 'done' : ($i === $step ? 'active' : '');
        $num = $i < $step ? '✓' : $i;
        echo "<div class='prog-step $cls'><div class='prog-num'>$num</div><div class='prog-label'>{$labels[$i-1]}</div></div>";
        if ($i < 4) {
            $lcls = $i < $step ? 'done' : '';
            echo "<div class='prog-line $lcls'></div>";
        }
    }
    echo '</div>';
    ?>

    <?php if ($error): ?>
        <div class="alert alert-danger">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- ════ STEP 1: Verify Identity ════ -->
    <?php if ($step === 1): ?>
        <p style="font-size:0.87rem;color:#666;text-align:center;margin-bottom:18px;">
            Enter your <strong>Student ID</strong> and <strong>Full Name</strong>
            exactly as registered by your admin.
        </p>
        <form method="POST" autocomplete="off">
            <div class="form-group">
                <label>Student ID</label>
                <input type="text" name="student_id" class="form-control"
                       placeholder="e.g. 2024-0001" required autofocus
                       value="<?= htmlspecialchars($_POST['student_id'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Full Name <span style="color:#bbb;font-size:0.77rem">(as registered by admin)</span></label>
                <input type="text" name="full_name" class="form-control"
                       placeholder="e.g. Juan Dela Cruz" required
                       value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Gmail Address <span style="color:#aaa;font-size:0.77rem">(your school Gmail)</span></label>
                <input type="email" name="gmail" class="form-control"
                       placeholder="e.g. juandelacruz@gmail.com" required
                       value="<?= htmlspecialchars($_POST['gmail'] ?? '') ?>">
                <p style="color:#aaa; font-size:0.77rem; margin-top:4px;">
                    This Gmail will be used to login. Must be a valid Gmail address.
                </p>
            </div>
            <button type="submit" name="verify" class="btn-green">✅ Verify My Identity</button>
        </form>

    <!-- ════ STEP 2: Set Password ════ -->
    <?php elseif ($step === 2): ?>
        <?php if ($student): ?>
        <div class="stu-box">
            <div class="sn">✅ <?= htmlspecialchars($student['full_name']) ?></div>
            <div class="sd"><?= htmlspecialchars($student['student_id']) ?> · <?= htmlspecialchars($student['course']) ?> Year <?= $student['year_level'] ?><?= $student['section'] ? ' - '.$student['section'] : '' ?></div>
            <?php if (!empty($_SESSION['signup_gmail'])): ?>
            <div class="sd" style="margin-top:4px;">📧 <?= htmlspecialchars($_SESSION['signup_gmail']) ?></div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <p style="font-size:0.87rem;color:#666;margin-bottom:16px;">
            Great! Now set a password for your account.
        </p>
        <form method="POST" autocomplete="off" onsubmit="return validatePw()">
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="password" id="pw1" class="form-control"
                       placeholder="At least 6 characters" required minlength="6"
                       oninput="checkStr(this.value)">
                <div class="str-bar"><div class="str-fill" id="str-fill"></div></div>
                <div class="str-lbl" id="str-lbl">Enter a password</div>
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" id="pw2" class="form-control"
                       placeholder="Re-enter your password" required
                       oninput="checkMatch()">
                <div id="match-msg" style="font-size:0.77rem;margin-top:3px;"></div>
            </div>
            <button type="submit" name="set_password" class="btn-green">Next: Register My Face →</button>
        </form>
        <div style="text-align:center;margin-top:12px;">
            <a href="student_signup.php" style="font-size:0.8rem;color:#aaa;">← Start over</a>
        </div>

    <!-- ════ STEP 3: Face Registration ════ -->
    <?php elseif ($step === 3): ?>
        <?php if ($student): ?>
        <div class="stu-box">
            <div class="sn">📷 Register Your Face</div>
            <div class="sd"><?= htmlspecialchars($student['full_name']) ?> · <?= htmlspecialchars($student['student_id']) ?></div>
            <?php if (!empty($_SESSION['signup_gmail'])): ?>
            <div class="sd" style="margin-top:4px;">📧 <?= htmlspecialchars($_SESSION['signup_gmail']) ?></div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <p style="font-size:0.86rem;color:#666;text-align:center;margin-bottom:14px;">
            📸 Just <strong>look straight at the camera</strong> and stay still.
            Good lighting helps — face the light source if possible.
        </p>

        <!-- Capture progress bar -->
        <div style="margin-bottom:10px;">
            <div style="display:flex;justify-content:space-between;font-size:0.78rem;color:#888;margin-bottom:4px;">
                <span>Face Capture Progress</span>
                <span id="cap-pct">0%</span>
            </div>
            <div style="height:8px;background:#eee;border-radius:4px;overflow:hidden;">
                <div id="cap-bar" style="height:100%;width:0%;background:#1565c0;border-radius:4px;transition:width 0.3s;"></div>
            </div>
        </div>

        <!-- Camera -->
        <div class="camera-wrap">
            <video id="reg-video" autoplay muted playsinline></video>
            <canvas id="reg-canvas"></canvas>
            <div class="face-guide pulse" id="face-guide"></div>
            <div class="challenge-banner" id="challenge-banner">⏳ Loading camera...</div>
        </div>

        <div class="camera-status cs-load" id="cam-status">
            ⏳ Loading face recognition models...
        </div>

        <button class="btn-green" id="btn-capture" onclick="startLiveness()" disabled>
            📷 Start Face Registration
        </button>

        <div style="text-align:center;margin-top:10px;">
            <a href="?restart=1" onclick="return confirm('Restart from beginning?')"
               style="font-size:0.8rem;color:#aaa;">← Restart</a>
        </div>

    <!-- ════ STEP 4: Done ════ -->
    <?php elseif ($step === 4): ?>
        <div class="done-box">
            <div class="done-icon">🎉</div>
            <h2 style="color:#1a237e;margin:12px 0 8px;">Account Created!</h2>
            <p style="color:#555;margin-bottom:18px;">
                Your account and face are registered. You can now login.
            </p>
            <?php if ($student): ?>
            <div class="stu-box" style="text-align:left;margin-bottom:20px;">
                <div class="sn">👨‍🎓 <?= htmlspecialchars($student['full_name']) ?></div>
                <div class="sd">
                    <?= htmlspecialchars($student['student_id']) ?> ·
                    <?= htmlspecialchars($student['course']) ?> Year <?= $student['year_level'] ?>
                </div>
                <?php
                $display_gmail = $student['google_email'] ?? $student['email'] ?? ($_SESSION['signup_gmail'] ?? '');
                if (!empty($display_gmail)): ?>
                <div class="sd" style="margin-top:4px;">📧 <?= htmlspecialchars($display_gmail) ?></div>
                <?php endif; ?>
                <div class="sd" style="margin-top:6px;color:#1a237e;">
                    ✅ Face Registered &nbsp; ✅ Password Set
                </div>
            </div>
            <?php endif; ?>
            <a href="student_login.php" class="btn-green"
               style="display:block;text-align:center;text-decoration:none;padding:13px;">
                🔐 Go to Login
            </a>
        </div>
    <?php endif; ?>

    <div class="bottom-link">
        Already have an account? <a href="student_login.php">Login here →</a>
    </div>
</div>
</div>

<?php if ($step === 3 && isset($_SESSION['signup_student_id'])): ?>
<script src="assets/js/face-api.min.js"></script>
<script>
// ═══════════════════════════════════════════════════
//  FAST FACE REGISTRATION — tinyFaceDetector
// ═══════════════════════════════════════════════════
const MODEL_URL = (function() {
    const p = window.location.pathname;
    if (p.includes('/AttendanceSystem/')) { return '/AttendanceSystem/assets/models'; }
    const depth = (p.match(/\//g) || []).length - 1;
    return '../'.repeat(Math.max(depth,1)) + 'assets/models';
})();

let videoStream        = null;
let detectLoop         = null;
let capturedDescriptor = null;
let isCapturing        = false;
let faceDetected       = false;
let captureCountdown   = 0;
let brightnessLevel    = 100;

const video   = document.getElementById('reg-video');
const canvas  = document.getElementById('reg-canvas');
const guide   = document.getElementById('face-guide');
const banner  = document.getElementById('challenge-banner');
const camStat = document.getElementById('cam-status');
const btnCap  = document.getElementById('btn-capture');

// Apply brightness/contrast filter to video for better detection in any lighting
function applyVideoFilter(brightness) {
    video.style.filter = `brightness(${brightness}%) contrast(110%) saturate(90%)`;
}

window.addEventListener('load', async () => {
    camStat.textContent = '⏳ Loading models (fast mode)...';
    camStat.className   = 'camera-status cs-load';
    applyVideoFilter(110);

    try {
        // Load ONLY what we need — tiny is 10x faster than ssd
        await Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
            faceapi.nets.faceLandmark68TinyNet.loadFromUri(MODEL_URL),
            faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL),
        ]);

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        camStat.textContent = '❌ Camera blocked. Use http://localhost/... instead of IP, or use HTTPS.';
        camStat.className   = 'camera-status cs-fail';
        banner.textContent  = '🔒 Camera not available (HTTPS required for LAN access)';
        return;
    }
    videoStream = await navigator.mediaDevices.getUserMedia({
            video: { width: 320, height: 240, facingMode: 'user' }
        });
        video.srcObject = videoStream;

        video.addEventListener('loadedmetadata', () => {
            canvas.width  = video.videoWidth;
            canvas.height = video.videoHeight;
        });

        video.addEventListener('play', () => {
            camStat.textContent = '✅ Ready! Click "Start Registration" and look at the camera.';
            camStat.className   = 'camera-status cs-go';
            banner.textContent  = '📷 Center your face in the oval';
            guide.className     = 'face-guide pulse';
            btnCap.disabled     = false;
            startLivePreview();
        });

    } catch(err) {
        camStat.textContent = '❌ ' + err.message;
        camStat.className   = 'camera-status cs-fail';
    }
});

// Live preview — just show face detection box, no capturing yet
function startLivePreview() {
    const ctx = canvas.getContext('2d');
    let previewRunning = true;

    btnCap.addEventListener('click', () => { previewRunning = false; }, { once: true });

    const preview = setInterval(async () => {
        if (!previewRunning) { clearInterval(preview); return; }

        ctx.clearRect(0, 0, canvas.width, canvas.height);
        try {
            const det = await faceapi.detectSingleFace(
                video,
                new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.3 })
            );

            if (det) {
                guide.className     = 'face-guide ready';
                banner.textContent  = '😊 Face detected! Click "Start Registration"';
                banner.style.background = 'rgba(21,101,192,0.75)';

                // Draw a simple box
                const scaleX = canvas.width  / video.videoWidth;
                const scaleY = canvas.height / video.videoHeight;
                const b = det.box;
                ctx.strokeStyle = '#64b5f6';
                ctx.lineWidth   = 3;
                ctx.strokeRect(b.x * scaleX, b.y * scaleY, b.width * scaleX, b.height * scaleY);
            } else {
                guide.className     = 'face-guide pulse';
                banner.textContent  = '📷 Center your face in the oval';
                banner.style.background = '';
            }
        } catch(e) {}
    }, 200);
}

async function startLiveness() {
    btnCap.disabled    = true;
    btnCap.textContent = '⏳ Capturing...';
    captureCountdown   = 0;
    capturedDescriptor = null;

    banner.textContent  = '👀 Look straight at the camera — stay still';
    banner.style.background = 'rgba(13,71,161,0.75)';
    camStat.textContent = '🔍 Scanning your face...';
    camStat.className   = 'camera-status cs-wait';

    runCapture();
}

function runCapture() {
    const ctx = canvas.getContext('2d');
    let  goodFrames = 0;
    const NEEDED    = 5; // 5 good consecutive frames

    detectLoop = setInterval(async () => {
        if (isCapturing) return;
        isCapturing = true;
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        try {
            const det = await faceapi
                .detectSingleFace(video,
                    new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.3 })
                )
                .withFaceLandmarks(true)   // true = use tiny landmark model
                .withFaceDescriptor();

            if (!det) {
                goodFrames = 0;
                guide.className     = 'face-guide pulse';
                banner.textContent  = '❌ No face found — move closer or improve lighting';
                banner.style.background = 'rgba(198,40,40,0.75)';
                autoBrightness(false);
                isCapturing = false;
                return;
            }

            // Face found!
            goodFrames++;
            guide.className    = 'face-guide ready';
            banner.style.background = 'rgba(21,101,192,0.75)';
            banner.textContent = `✅ Scanning... ${goodFrames}/${NEEDED}`;
            camStat.textContent = `✅ Face detected! Hold still... (${goodFrames}/${NEEDED})`;
            camStat.className   = 'camera-status cs-go';
            autoBrightness(true);

            // Update progress bar
            const pct = Math.round((goodFrames / NEEDED) * 100);
            const bar = document.getElementById('cap-bar');
            const lbl = document.getElementById('cap-pct');
            if (bar) bar.style.width = pct + '%';
            if (lbl) lbl.textContent = pct + '%';

            // Draw box
            const scaleX = canvas.width  / video.videoWidth;
            const scaleY = canvas.height / video.videoHeight;
            const b = det.detection.box;
            ctx.strokeStyle = '#64b5f6';
            ctx.lineWidth   = 3;
            ctx.strokeRect(b.x * scaleX, b.y * scaleY, b.width * scaleX, b.height * scaleY);

            // Save best descriptor
            capturedDescriptor = Array.from(det.descriptor);

            if (goodFrames >= NEEDED) {
                clearInterval(detectLoop);
                await finishRegistration();
            }

        } catch(e) {
            console.error(e);
        }

        isCapturing = false;
    }, 250); // Run every 250ms — faster than before
}

function autoBrightness(faceFound) {
    // If face not found, try increasing brightness to help detection
    if (!faceFound) {
        brightnessLevel = Math.min(brightnessLevel + 5, 150);
    } else {
        brightnessLevel = 110; // reset to normal when face found
    }
    applyVideoFilter(brightnessLevel);
}

async function finishRegistration() {
    if (!capturedDescriptor) {
        camStat.textContent = '❌ No face captured. Try again.';
        camStat.className   = 'camera-status cs-fail';
        resetReg();
        return;
    }

    if (videoStream) videoStream.getTracks().forEach(t => t.stop());
    banner.textContent      = '💾 Saving your face...';
    banner.style.background = 'rgba(13,71,161,0.75)';
    camStat.textContent     = '💾 Saving face data to server...';
    camStat.className       = 'camera-status cs-load';

    try {
        const res  = await fetch('api/register_face.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ descriptor: capturedDescriptor })
        });
        const data = await res.json();

        if (data.success) {
            banner.textContent      = '🎉 Face registered!';
            banner.style.background = 'rgba(21,101,192,0.9)';
            camStat.textContent     = '✅ Done! Redirecting...';
            camStat.className       = 'camera-status cs-go';
            setTimeout(() => { window.location.href = 'student_signup_done.php'; }, 1000);
        } else {
            camStat.textContent = '❌ ' + (data.message || 'Save failed.');
            camStat.className   = 'camera-status cs-fail';
            resetReg();
        }
    } catch(err) {
        camStat.textContent = '❌ Network error: ' + err.message;
        camStat.className   = 'camera-status cs-fail';
        resetReg();
    }
}

function resetReg() {
    capturedDescriptor = null;
    isCapturing        = false;
    btnCap.disabled    = false;
    btnCap.textContent = '📷 Try Again';
    guide.className    = 'face-guide pulse';
    brightnessLevel    = 110;
    applyVideoFilter(brightnessLevel);
}
</script>
<?php endif; ?>

<?php
// Handle ?done=1 redirect — show step 4
if (isset($_GET['done']) && isset($_SESSION['signup_student_id'])):
    $sid_db  = intval($_SESSION['signup_student_id']);
    $res     = $conn->query("SELECT * FROM students WHERE id=$sid_db");
    $student = ($res && $res->num_rows > 0) ? $res->fetch_assoc() : null;
    unset($_SESSION['signup_student_id'], $_SESSION['signup_pw_done']);
?>
<script>
// Reload page to show step 4 cleanly
<?php if ($student): ?>
// Store done flag and reload
window.location.replace('student_signup_done.php');
<?php endif; ?>
</script>
<?php endif; ?>

<script>
function checkStr(pw) {
    const fill = document.getElementById('str-fill');
    const lbl  = document.getElementById('str-lbl');
    if (!fill) return;
    let s = 0;
    if (pw.length >= 6)           s++;
    if (pw.length >= 10)          s++;
    if (/[A-Z]/.test(pw))         s++;
    if (/[0-9]/.test(pw))         s++;
    if (/[^A-Za-z0-9]/.test(pw))  s++;
    const lvl = [
        {p:'0%',   c:'#eee',    t:'Enter a password'},
        {p:'25%',  c:'#ef5350', t:'Weak'},
        {p:'50%',  c:'#ffa726', t:'Fair'},
        {p:'75%',  c:'#64b5f6', t:'Good'},
        {p:'100%', c:'#1565c0', t:'Strong 💪'},
    ][Math.min(s, 4)];
    fill.style.width      = lvl.p;
    fill.style.background = lvl.c;
    lbl.textContent       = lvl.t;
    lbl.style.color       = lvl.c;
}

function checkMatch() {
    const v1 = document.getElementById('pw1')?.value;
    const v2 = document.getElementById('pw2')?.value;
    const el = document.getElementById('match-msg');
    if (!el || !v2) return;
    el.textContent = v1 === v2 ? '✅ Passwords match' : '❌ Passwords do not match';
    el.style.color = v1 === v2 ? '#1565c0' : '#c62828';
}

function validatePw() {
    const v1 = document.getElementById('pw1').value;
    const v2 = document.getElementById('pw2').value;
    if (v1.length < 6)  { alert('Password must be at least 6 characters.'); return false; }
    if (v1 !== v2)       { alert('Passwords do not match.');                return false; }
    return true;
}
</script>
<script src="assets/js/toggle-password.js"></script>
</body>
</html>
