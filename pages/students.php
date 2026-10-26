<?php
require_once 'layout.php';
require_once '../config/database.php';
$conn = getConnection();

$success_msg = '';
$error_msg   = '';

// Add student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_student'])) {
    $sid    = $conn->real_escape_string(trim($_POST['student_id']));
    $name   = $conn->real_escape_string(trim($_POST['full_name']));
    $course = $conn->real_escape_string(trim($_POST['course']));
    $year   = intval($_POST['year_level']);
    $sec    = $conn->real_escape_string(trim($_POST['section']));
    $pass   = trim($_POST['password'] ?? '');

    // Check duplicate
    $check = $conn->query("SELECT id FROM students WHERE student_id='$sid'");
    if ($check->num_rows > 0) {
        $error_msg = "Student ID '$sid' already exists.";
    } else {
        $hashed = $pass ? "'" . $conn->real_escape_string(password_hash($pass, PASSWORD_DEFAULT)) . "'" : 'NULL';
        $conn->query("INSERT INTO students (student_id, full_name, course, year_level, section, password) 
                      VALUES ('$sid','$name','$course',$year,'$sec',$hashed)");
        $success_msg = "Student '$name' added successfully.";
    }
}

// Set / reset student password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_password'])) {
    $stu_id  = intval($_POST['stu_id']);
    $new_pass = trim($_POST['new_password']);
    if (strlen($new_pass) < 6) {
        $error_msg = "Password must be at least 6 characters.";
    } else {
        $hashed = $conn->real_escape_string(password_hash($new_pass, PASSWORD_DEFAULT));
        $conn->query("UPDATE students SET password='$hashed' WHERE id=$stu_id");
        $success_msg = "Password updated successfully.";
    }
}

// Delete student
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    $conn->query("DELETE FROM attendance WHERE student_id=$del_id");
    $conn->query("DELETE FROM students WHERE id=$del_id");
    $success_msg = "Student deleted successfully.";
}

$students = $conn->query("SELECT * FROM students ORDER BY full_name ASC");
?>

<?php if ($success_msg): ?>
    <div class="alert alert-success">✅ <?= $success_msg ?></div>
<?php endif; ?>
<?php if ($error_msg): ?>
    <div class="alert alert-danger">❌ <?= $error_msg ?></div>
<?php endif; ?>

<div class="grid-2">
    <!-- ── Add Student Form ── -->
    <div class="card">
        <div class="card-title">➕ Add New Student</div>
        <form method="POST">
            <div class="form-group">
                <label>Student ID *</label>
                <input type="text" name="student_id" class="form-control"
                       placeholder="e.g. 2024-0001" required>
            </div>
            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="full_name" class="form-control"
                       placeholder="e.g. Juan Dela Cruz" required>
            </div>
            <div class="form-group">
                <label>Course *</label>
                <input type="text" name="course" class="form-control"
                       placeholder="e.g. BSIT, BSCS" required>
            </div>
            <div class="form-group">
                <label>Year Level</label>
                <select name="year_level" class="form-control">
                    <option value="1">1st Year</option>
                    <option value="2">2nd Year</option>
                    <option value="3">3rd Year</option>
                    <option value="4">4th Year</option>
                </select>
            </div>
            <div class="form-group">
                <label>Section</label>
                <input type="text" name="section" class="form-control"
                       placeholder="e.g. A, B, C">
            </div>
            <div class="form-group">
                <label>Password <span style="color:#888; font-size:0.8rem;">(for student login)</span></label>
                <input type="password" name="password" class="form-control"
                       placeholder="Min. 6 characters">
                <p style="color:#aaa; font-size:0.78rem; margin-top:4px;">
                    Leave blank to set later. Student uses this to login.
                </p>
            </div>
            <button type="submit" name="add_student" class="btn btn-primary btn-block">
                ➕ Add Student
            </button>
        </form>
    </div>

    <!-- ── Student List ── -->
    <div class="card">
        <div class="card-title">
            👥 Student List
            <span style="margin-left:auto; font-size:0.82rem; font-weight:400; color:#888;">
                <?= $students->num_rows ?> student(s)
            </span>
        </div>

        <?php if ($students->num_rows > 0): ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Full Name</th>
                        <th>Course</th>
                        <th>Year</th>
                        <th>Password</th>
                        <th>Face</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php while($s = $students->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($s['student_id']) ?></td>
                        <td><?= htmlspecialchars($s['full_name']) ?></td>
                        <td><?= htmlspecialchars($s['course']) ?></td>
                        <td>Year <?= $s['year_level'] ?> - <?= htmlspecialchars($s['section']) ?></td>
                        <td>
                            <?= $s['password']
                                ? '<span class="badge badge-present">✅ Set</span>'
                                : '<span class="badge badge-danger">❌ None</span>' ?>
                        </td>
                        <td>
                            <?= $s['face_descriptor']
                                ? '<span class="badge badge-present">✅ Registered</span>'
                                : '<span class="badge badge-danger">❌ Not Set</span>' ?>
                        </td>
                        <td style="white-space:nowrap;">
                            <button onclick="openRegisterFace(<?= $s['id'] ?>, '<?= htmlspecialchars($s['full_name'], ENT_QUOTES) ?>')"
                                    class="btn btn-warning btn-sm">
                                📷 Face
                            </button>
                            <button onclick="openSetPassword(<?= $s['id'] ?>, '<?= htmlspecialchars($s['full_name'], ENT_QUOTES) ?>')"
                                    class="btn btn-info btn-sm">
                                🔑 Password
                            </button>
                            <a href="?delete=<?= $s['id'] ?>"
                               onclick="return confirm('Delete <?= htmlspecialchars($s['full_name'], ENT_QUOTES) ?>?')"
                               class="btn btn-danger btn-sm">
                                🗑️
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">👥</div>
                <p>No students yet. Add one using the form.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ═══ Set Password Modal ═══ -->
<div id="password-modal" class="modal-overlay">
    <div class="modal-box" style="max-width:400px;">
        <div class="modal-title">🔑 Set Password — <span id="pw-student-name"></span></div>
        <form method="POST">
            <input type="hidden" name="stu_id" id="pw-stu-id">
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="new_password" id="pw-input" class="form-control"
                       placeholder="Min. 6 characters" required minlength="6">
            </div>
            <p style="color:#888; font-size:0.82rem; margin-bottom:14px;">
                Student will use this password on the student login page.
            </p>
            <div style="display:flex; gap:10px;">
                <button type="submit" name="set_password" class="btn btn-success" style="flex:1">
                    💾 Save Password
                </button>
                <button type="button" onclick="closePasswordModal()" class="btn btn-danger" style="flex:1">
                    ✖ Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ═══ Face Registration Modal ═══ -->
<div id="face-modal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-title">📷 Register Face — <span id="modal-student-name"></span></div>

        <video id="reg-video" autoplay muted playsinline
               style="width:100%; border-radius:10px; background:#000;"></video>

        <div id="reg-status" class="recognition-status status-loading" style="margin:10px 0;">
            Starting camera...
        </div>

        <div style="display:flex; gap:10px; margin-top:10px;">
            <button onclick="captureAndRegister()" class="btn btn-success" style="flex:1">
                📸 Capture & Register
            </button>
            <button onclick="closeModal()" class="btn btn-danger" style="flex:1">
                ✖ Cancel
            </button>
        </div>
    </div>
</div>

<script src="../assets/js/face-api.min.js"></script>
<script>
const MODEL_URL = 'https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/weights';
let regStudentId = null;
let regStream    = null;
let modelsLoaded = false;

async function openRegisterFace(id, name) {
    regStudentId = id;
    document.getElementById('modal-student-name').textContent = name;
    document.getElementById('face-modal').classList.add('active');
    document.getElementById('reg-status').textContent = 'Starting camera...';
    document.getElementById('reg-status').className = 'recognition-status status-loading';

    try {
        regStream = await navigator.mediaDevices.getUserMedia({ video: true });
        document.getElementById('reg-video').srcObject = regStream;
        document.getElementById('reg-status').textContent =
            '👀 Look straight at the camera, then click Capture.';
        document.getElementById('reg-status').className = 'recognition-status status-scanning';
    } catch(e) {
        document.getElementById('reg-status').textContent = '❌ Camera error: ' + e.message;
        document.getElementById('reg-status').className = 'recognition-status status-unknown';
    }
}

async function captureAndRegister() {
    const statusEl = document.getElementById('reg-status');

    if (!modelsLoaded) {
        statusEl.textContent = '⏳ Loading models (first time only)...';
        statusEl.className = 'recognition-status status-loading';

        await Promise.all([
            faceapi.nets.ssdMobilenetv1.loadFromUri(MODEL_URL),
            faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
            faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL),
        ]);
        modelsLoaded = true;
    }

    statusEl.textContent = '🔍 Detecting face...';
    statusEl.className = 'recognition-status status-scanning';

    const videoEl  = document.getElementById('reg-video');
    const detection = await faceapi
        .detectSingleFace(videoEl, new faceapi.SsdMobilenetv1Options())
        .withFaceLandmarks()
        .withFaceDescriptor();

    if (!detection) {
        statusEl.textContent = '❌ No face detected. Make sure your face is visible and try again.';
        statusEl.className = 'recognition-status status-unknown';
        return;
    }

    statusEl.textContent = '💾 Saving face data...';

    const descriptor = Array.from(detection.descriptor);

    const res = await fetch('../api/register_face.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ student_id: regStudentId, descriptor })
    });

    const result = await res.json();

    if (result.success) {
        statusEl.textContent = '✅ Face registered successfully!';
        statusEl.className = 'recognition-status status-detected';
        setTimeout(() => { closeModal(); location.reload(); }, 1500);
    } else {
        statusEl.textContent = '❌ ' + (result.message || 'Failed to save.');
        statusEl.className = 'recognition-status status-unknown';
    }
}

function closeModal() {
    document.getElementById('face-modal').classList.remove('active');
    if (regStream) {
        regStream.getTracks().forEach(t => t.stop());
        regStream = null;
    }
}

function openSetPassword(id, name) {
    document.getElementById('pw-stu-id').value = id;
    document.getElementById('pw-student-name').textContent = name;
    document.getElementById('pw-input').value = '';
    document.getElementById('password-modal').classList.add('active');
}

function closePasswordModal() {
    document.getElementById('password-modal').classList.remove('active');
}
</script>

    </main>
</div>
</body>
</html>
