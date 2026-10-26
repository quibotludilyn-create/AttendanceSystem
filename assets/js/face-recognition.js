// =============================================
// Face Recognition — Admin Attendance Page
// =============================================

const video    = document.getElementById('video');
const canvas   = document.getElementById('canvas');
const statusEl = document.getElementById('status');

// Dynamic MODEL_URL — works on both XAMPP (port 80) and php -S (port 8000)
const MODEL_URL = (function() {
    var loc  = window.location;
    var base = loc.protocol + '//' + loc.host;
    var path = loc.pathname;
    // If running under /AttendanceSystem/ subfolder (XAMPP)
    if (path.indexOf('/AttendanceSystem/') !== -1) {
        return base + '/AttendanceSystem/assets/models';
    }
    // php -S localhost:8000 — project root IS the folder
    // Determine depth to go back to root
    var parts = path.replace(/\/+$/, '').split('/').filter(function(p){ return p !== ''; });
    var depth = parts.length;
    var prefix = '';
    for (var i = 0; i < depth; i++) prefix += '../';
    return prefix + 'assets/models';
})();

let faceMatcher    = null;
let currentStudent = null;
let isProcessing   = false;
let studentMap     = {};
let loggedToday    = new Set();

// ─── INIT ─────────────────────────────────────
async function init() {
    setStatus('loading', '⏳ Loading face recognition models...');
    try {
        setStatus('loading', '⏳ Loading tiny face detector...');
        await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL);
        setStatus('loading', '⏳ Loading landmark model...');
        await faceapi.nets.faceLandmark68TinyNet.loadFromUri(MODEL_URL);
        setStatus('loading', '⏳ Loading recognition model...');
        await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);
        setStatus('loading', '📡 Loading student face data...');
        await loadStudents();
        await startCamera();
    } catch (err) {
        var msg = err.message || String(err);
        if (msg.includes('DOCTYPE') || msg.includes('Unexpected token')) {
            setStatus('unknown', '❌ Model file error. Try: Ctrl+Shift+R to hard refresh.');
        } else {
            setStatus('unknown', '❌ Error: ' + msg.substring(0, 100));
        }
        console.error('Face recognition init error:', err);
    }
}

// ─── LOAD STUDENTS ────────────────────────────
async function loadStudents() {
    try {
        // Dynamic API path
        var apiPath = (window.location.pathname.indexOf('/AttendanceSystem/') !== -1)
            ? '/AttendanceSystem/api/get_students.php'
            : '../api/get_students.php';

        const res = await fetch(apiPath);
        const ct  = res.headers.get('content-type') || '';
        if (!ct.includes('application/json')) {
            console.warn('get_students.php returned non-JSON. Session may have expired.');
            setStatus('unknown', '⚠️ Session expired. Please refresh and log in again.');
            return;
        }
        const students = await res.json();
        const labeled  = [];
        for (const s of students) {
            studentMap[s.id] = s;
            if (!s.face_descriptor) continue;
            try {
                const desc = new Float32Array(JSON.parse(s.face_descriptor));
                labeled.push(new faceapi.LabeledFaceDescriptors(String(s.id), [desc]));
            } catch(e) { console.warn('Bad descriptor for student', s.id); }
        }
        if (labeled.length > 0) {
            faceMatcher = new faceapi.FaceMatcher(labeled, 0.60);
            console.log('Loaded ' + labeled.length + ' face profile(s)');
        }
    } catch (err) {
        console.error('Failed to load students:', err.message);
        setStatus('unknown', '❌ Could not load students: ' + err.message);
    }
}

// ─── CAMERA ───────────────────────────────────
async function startCamera() {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ video: { width:640, height:480 } });
        video.srcObject = stream;
        video.addEventListener('loadedmetadata', function() { canvas.width = video.videoWidth; canvas.height = video.videoHeight; });
        video.addEventListener('play', function() { setStatus('scanning', '🔍 Scanning for faces...'); detectLoop(); });
    } catch (err) {
        setStatus('unknown', '❌ Camera access denied.');
    }
}

// ─── DETECTION LOOP ───────────────────────────
async function detectLoop() {
    const ctx = canvas.getContext('2d');
    var run = async function() {
        if (isProcessing) { setTimeout(run, 300); return; }
        isProcessing = true;
        try {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            const detections = await faceapi
                .detectAllFaces(video, new faceapi.TinyFaceDetectorOptions({ inputSize:320, scoreThreshold:0.4 }))
                .withFaceLandmarks(true).withFaceDescriptors();
            if (detections.length === 0) {
                setStatus('scanning', '🔍 No face detected. Look at the camera.');
                hideDetectedCard(); currentStudent = null;
                isProcessing = false; setTimeout(run, 400); return;
            }
            const resized = faceapi.resizeResults(detections, { width: canvas.width, height: canvas.height });
            faceapi.draw.drawDetections(canvas, resized);
            if (!faceMatcher) {
                setStatus('unknown', '⚠️ No students with registered faces yet.');
                isProcessing = false; setTimeout(run, 600); return;
            }
            const match = faceMatcher.findBestMatch(resized[0].descriptor);
            if (match.label === 'unknown') {
                setStatus('unknown', '❓ Face not recognized.'); hideDetectedCard(); currentStudent = null;
            } else {
                const student = studentMap[match.label];
                if (student) { currentStudent = student; showDetectedCard(student); setStatus('detected', '✅ Recognized: ' + student.full_name); }
            }
        } catch(e) { console.error('Detection error:', e); }
        isProcessing = false; setTimeout(run, 400);
    };
    run();
}

// ─── UI HELPERS ───────────────────────────────
function setStatus(type, msg) {
    if (!statusEl) return;
    statusEl.textContent = msg;
    statusEl.className = 'recognition-status status-' + (type==='scanning'?'scanning':type==='detected'?'detected':type==='unknown'?'unknown':'loading');
}
function showDetectedCard(s) {
    var c = document.getElementById('detected-card'); if (!c) return; c.style.display = 'block';
    document.getElementById('d-name').textContent   = s.full_name;
    document.getElementById('d-id').textContent     = s.student_id;
    document.getElementById('d-course').textContent = s.course || '—';
    document.getElementById('d-year').textContent   = 'Year '+(s.year_level||'?')+' — '+(s.section||'—');
    document.getElementById('d-time').textContent   = new Date().toLocaleTimeString();
}
function hideDetectedCard() { var c = document.getElementById('detected-card'); if(c) c.style.display='none'; }

// ─── SAVE ATTENDANCE ──────────────────────────
async function confirmAttendance() {
    if (!currentStudent) return alert('No student detected.');
    const subject = (document.getElementById('subject')||{value:'General'}).value.trim() || 'General';
    const key     = currentStudent.id + '_' + subject;
    if (loggedToday.has(key)) { alert(currentStudent.full_name + ' already recorded for ' + subject + ' today.'); return; }
    const now = new Date(), timeIn = now.toTimeString().slice(0,8);
    const status = (now.getHours()>8||(now.getHours()===8&&now.getMinutes()>15))?'late':'present';
    const btn = document.querySelector('#detected-card button');
    if (btn) { btn.disabled=true; btn.textContent='⏳ Saving...'; }
    try {
        var saveApi = (window.location.pathname.indexOf('/AttendanceSystem/')!==-1)
            ? '/AttendanceSystem/api/save_attendance.php'
            : '../api/save_attendance.php';
        const res    = await fetch(saveApi, { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({student_id:currentStudent.id,subject,time_in:timeIn,status}) });
        const ct     = res.headers.get('content-type')||'';
        if (!ct.includes('application/json')) { alert('Session expired. Please refresh.'); return; }
        const result = await res.json();
        if (result.success) {
            loggedToday.add(key); addToLog(currentStudent.full_name,subject,timeIn,status);
            setStatus('detected','✅ Saved! '+currentStudent.full_name+' marked '+status+'.'); hideDetectedCard(); currentStudent=null;
        } else { alert(result.message||'Failed.'); }
    } catch(err) { alert('Network error: '+err.message); }
    if (btn) { btn.disabled=false; btn.textContent='✅ Confirm & Save Attendance'; }
}

function addToLog(name, subject, time, status) {
    const ll = document.getElementById('log-list'); if (!ll) return;
    const emp = ll.querySelector('.empty-state'); if (emp) emp.remove();
    const item = document.createElement('div'); item.className='log-item';
    item.innerHTML = '<span style="font-weight:500">'+name+'</span><span style="color:#888;font-size:.8rem">'+subject+'</span><span style="color:#888;font-size:.8rem">'+time+'</span><span class="badge badge-'+status+'">'+status.charAt(0).toUpperCase()+status.slice(1)+'</span>';
    ll.insertBefore(item, ll.firstChild);
}

init();
