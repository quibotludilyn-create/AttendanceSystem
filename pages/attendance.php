<?php require_once 'layout.php'; ?>

<div class="card">
    <div class="card-title">📷 Face Recognition Attendance</div>

    <div class="grid-2">
        <!-- Camera Side -->
        <div>
            <div id="video-container">
                <video id="video" autoplay muted playsinline></video>
                <canvas id="canvas"></canvas>
            </div>

            <div id="status" class="recognition-status status-loading">
                ⏳ Initializing camera...
            </div>

            <div style="margin-top:14px;">
                <label style="font-weight:500; font-size:0.9rem; color:#444;">Subject / Class:</label>
                <input type="text" id="subject" class="form-control"
                       placeholder="e.g. Mathematics 101" style="margin-top:6px;">
            </div>
        </div>

        <!-- Info + Log Side -->
        <div>
            <!-- Detected Student Card -->
            <div id="detected-card" class="card" style="display:none; border: 2px solid #2e7d32;">
                <div class="card-title">✅ Student Detected</div>
                <table style="font-size:0.9rem;">
                    <tr>
                        <td style="padding:6px; color:#666; width:90px;">Name:</td>
                        <td style="padding:6px; font-weight:600;" id="d-name">—</td>
                    </tr>
                    <tr>
                        <td style="padding:6px; color:#666;">Student ID:</td>
                        <td style="padding:6px;" id="d-id">—</td>
                    </tr>
                    <tr>
                        <td style="padding:6px; color:#666;">Course:</td>
                        <td style="padding:6px;" id="d-course">—</td>
                    </tr>
                    <tr>
                        <td style="padding:6px; color:#666;">Year / Sec:</td>
                        <td style="padding:6px;" id="d-year">—</td>
                    </tr>
                    <tr>
                        <td style="padding:6px; color:#666;">Time:</td>
                        <td style="padding:6px;" id="d-time">—</td>
                    </tr>
                </table>
                <button onclick="confirmAttendance()" class="btn btn-success btn-block"
                        style="margin-top:14px; font-size:1rem; padding:13px;">
                    ✅ Confirm & Save Attendance
                </button>
            </div>

            <!-- Attendance Log -->
            <div class="card">
                <div class="card-title">📋 Today's Log</div>
                <div id="log-list">
                    <div class="empty-state" style="padding:20px 0">
                        <div class="empty-icon" style="font-size:2rem">📭</div>
                        <p style="font-size:0.85rem">No attendance recorded yet today.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- face-api.js -->
<script src="../assets/js/face-api.min.js"></script>
<script src="../assets/js/face-recognition.js"></script>

    </main>
</div>
</body>
</html>
