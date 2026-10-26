<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AttendEase — Face Recognition Attendance System</title>
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --navy:      #0a0e2e;
            --blue:      #1a237e;
            --blue-mid:  #1565c0;
            --accent:    #00b4d8;
            --accent2:   #48cae4;
            --green:     #00c896;
            --white:     #ffffff;
            --gray-light:#f4f6fb;
            --gray-text: #5a6478;
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: var(--white);
            color: #1a1a2e;
            overflow-x: hidden;
        }

        /* ── SCROLLBAR ── */
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: #f0f0f0; }
        ::-webkit-scrollbar-thumb { background: var(--blue-mid); border-radius: 3px; }

        /* ══════════════════════════════════════
           NAVBAR
        ══════════════════════════════════════ */
        .nav {
            position: fixed; top: 0; left: 0; right: 0;
            z-index: 999;
            padding: 0 5%;
            height: 68px;
            display: flex; align-items: center; justify-content: space-between;
            background: transparent;
            transition: background 0.4s, box-shadow 0.4s;
        }

        .nav.scrolled {
            background: rgba(10,14,46,0.97);
            backdrop-filter: blur(12px);
            box-shadow: 0 2px 30px rgba(0,0,0,0.3);
        }

        .nav-brand {
            display: flex; align-items: center; gap: 10px;
            text-decoration: none; color: white;
        }

        .brand-icon {
            width: 38px; height: 38px;
            background: linear-gradient(135deg, var(--accent), var(--blue-mid));
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem;
        }

        .brand-name {
            font-size: 1.15rem;
            font-weight: 800;
            letter-spacing: -0.3px;
        }

        .brand-name span { color: var(--accent); }

        .nav-menu {
            display: flex; align-items: center; gap: 6px;
        }

        .nav-menu a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.88rem;
            font-weight: 500;
            transition: all 0.2s;
        }

        .nav-menu a:hover { color: white; background: rgba(255,255,255,0.1); }

        .btn-nav {
            background: linear-gradient(135deg, var(--accent), var(--blue-mid)) !important;
            color: white !important;
            font-weight: 700 !important;
            padding: 9px 20px !important;
            box-shadow: 0 4px 15px rgba(0,180,216,0.35);
        }

        .btn-nav:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(0,180,216,0.5) !important;
        }

        /* ══════════════════════════════════════
           HERO
        ══════════════════════════════════════ */
        .hero {
            min-height: 100vh;
            background: var(--navy);
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
        }

        /* Grid background */
        .hero-grid {
            position: absolute; inset: 0;
            background-image:
                linear-gradient(rgba(0,180,216,0.06) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0,180,216,0.06) 1px, transparent 1px);
            background-size: 60px 60px;
        }

        /* Glow blobs */
        .blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            pointer-events: none;
        }

        .blob-1 {
            width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(21,101,192,0.35), transparent 70%);
            top: -100px; right: -100px;
            animation: blobMove 10s ease-in-out infinite;
        }

        .blob-2 {
            width: 350px; height: 350px;
            background: radial-gradient(circle, rgba(0,200,150,0.2), transparent 70%);
            bottom: 0; left: 10%;
            animation: blobMove 14s ease-in-out infinite reverse;
        }

        .blob-3 {
            width: 250px; height: 250px;
            background: radial-gradient(circle, rgba(0,180,216,0.25), transparent 70%);
            top: 40%; left: 45%;
            animation: blobMove 8s ease-in-out infinite 2s;
        }

        @keyframes blobMove {
            0%,100% { transform: translate(0,0) scale(1); }
            33%      { transform: translate(30px,-30px) scale(1.05); }
            66%      { transform: translate(-20px,20px) scale(0.96); }
        }

        .hero-inner {
            position: relative; z-index: 2;
            max-width: 1200px;
            margin: 0 auto;
            padding: 120px 5% 80px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
        }

        /* Left */
        .hero-left { color: white; }

        .hero-pill {
            display: inline-flex; align-items: center; gap: 8px;
            background: rgba(0,180,216,0.12);
            border: 1px solid rgba(0,180,216,0.3);
            color: var(--accent);
            padding: 6px 16px;
            border-radius: 100px;
            font-size: 0.78rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-bottom: 24px;
        }

        .hero-pill .dot {
            width: 6px; height: 6px;
            border-radius: 50%;
            background: var(--accent);
            animation: blink 1.5s ease-in-out infinite;
        }

        @keyframes blink { 0%,100%{ opacity:1; } 50%{ opacity:0.2; } }

        .hero-h1 {
            font-size: 3.4rem;
            font-weight: 900;
            line-height: 1.1;
            margin-bottom: 22px;
            letter-spacing: -1px;
        }

        .hero-h1 .line2 {
            background: linear-gradient(90deg, var(--accent), var(--green));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-p {
            font-size: 1.05rem;
            color: rgba(255,255,255,0.65);
            line-height: 1.75;
            margin-bottom: 36px;
            max-width: 480px;
        }

        .hero-cta {
            display: flex; gap: 14px; flex-wrap: wrap;
        }

        .cta-primary {
            background: linear-gradient(135deg, var(--accent), var(--blue-mid));
            color: white;
            padding: 15px 32px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
            font-size: 1rem;
            display: inline-flex; align-items: center; gap: 8px;
            box-shadow: 0 6px 25px rgba(0,180,216,0.4);
            transition: all 0.3s;
        }

        .cta-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(0,180,216,0.55);
        }

        .cta-secondary {
            background: rgba(255,255,255,0.08);
            border: 1.5px solid rgba(255,255,255,0.2);
            color: white;
            padding: 15px 32px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1rem;
            display: inline-flex; align-items: center; gap: 8px;
            transition: all 0.3s;
            backdrop-filter: blur(6px);
        }

        .cta-secondary:hover {
            background: rgba(255,255,255,0.15);
            transform: translateY(-3px);
        }

        /* Trust badges */
        .trust-row {
            display: flex; gap: 22px; margin-top: 40px; flex-wrap: wrap;
        }

        .trust-item {
            display: flex; align-items: center; gap: 8px;
            color: rgba(255,255,255,0.5);
            font-size: 0.82rem;
        }

        .trust-item .ti { color: var(--green); font-size: 0.9rem; }

        /* Right — face scan visual */
        .hero-right { display: flex; justify-content: center; align-items: center; }

        .phone-mockup {
            width: 280px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 36px;
            padding: 20px;
            position: relative;
            box-shadow: 0 30px 80px rgba(0,0,0,0.4), 0 0 0 1px rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
        }

        .phone-notch {
            width: 80px; height: 10px;
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            margin: 0 auto 20px;
        }

        .cam-screen {
            background: #0d1117;
            border-radius: 20px;
            overflow: hidden;
            aspect-ratio: 3/4;
            position: relative;
            display: flex; align-items: center; justify-content: center;
        }

        /* Scan frame */
        .scan-frame {
            width: 140px; height: 170px;
            position: relative;
        }

        .sf-corner {
            position: absolute;
            width: 22px; height: 22px;
            border-color: var(--accent);
            border-style: solid;
            border-width: 0;
        }

        .sf-corner.tl { top: 0; left: 0;  border-top-width: 3px; border-left-width: 3px;  border-radius: 4px 0 0 0; }
        .sf-corner.tr { top: 0; right: 0; border-top-width: 3px; border-right-width: 3px; border-radius: 0 4px 0 0; }
        .sf-corner.bl { bottom: 0; left: 0;  border-bottom-width: 3px; border-left-width: 3px;  border-radius: 0 0 0 4px; }
        .sf-corner.br { bottom: 0; right: 0; border-bottom-width: 3px; border-right-width: 3px; border-radius: 0 0 4px 0; }

        .scan-face { font-size: 5.5rem; line-height: 1; display: block; text-align: center; margin-top: 16px; }

        .scan-bar {
            position: absolute;
            left: 6px; right: 6px;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--accent), transparent);
            animation: scan-anim 2.5s ease-in-out infinite;
            border-radius: 2px;
        }

        @keyframes scan-anim {
            0%   { top: 10%; opacity: 0; }
            10%  { opacity: 1; }
            90%  { opacity: 1; }
            100% { top: 90%; opacity: 0; }
        }

        .scan-dots {
            position: absolute;
            bottom: 16px;
            width: 100%;
            display: flex; justify-content: center; gap: 6px;
        }

        .scan-dot {
            width: 6px; height: 6px;
            border-radius: 50%;
            background: rgba(0,180,216,0.4);
        }

        .scan-dot.active {
            background: var(--accent);
            box-shadow: 0 0 8px var(--accent);
            animation: dot-pulse 1s ease-in-out infinite;
        }

        @keyframes dot-pulse { 0%,100%{ transform:scale(1); } 50%{ transform:scale(1.5); } }

        .scan-status {
            position: absolute;
            bottom: 36px; left: 0; right: 0;
            text-align: center;
            font-size: 0.72rem;
            font-weight: 600;
            color: var(--accent);
            letter-spacing: 1px;
        }

        /* Floating cards */
        .float-card {
            position: absolute;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.14);
            border-radius: 12px;
            padding: 10px 14px;
            backdrop-filter: blur(10px);
            color: white;
            font-size: 0.78rem;
            white-space: nowrap;
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        }

        .fc-1 { top: 30px; right: -60px; animation: floatY 4s ease-in-out infinite; }
        .fc-2 { bottom: 60px; left: -70px; animation: floatY 5s ease-in-out infinite 1s; }
        .fc-3 { top: 50%; right: -80px; animation: floatY 6s ease-in-out infinite 0.5s; }

        @keyframes floatY { 0%,100%{ transform:translateY(0); } 50%{ transform:translateY(-10px); } }

        .fc-icon { font-size: 1.1rem; margin-right: 5px; }
        .fc-label { opacity: 0.65; font-size: 0.68rem; }
        .fc-value { font-weight: 700; color: var(--green); }

        /* ══════════════════════════════════════
           STATS BAR
        ══════════════════════════════════════ */
        .stats-bar {
            background: linear-gradient(135deg, var(--blue), var(--blue-mid));
            padding: 28px 5%;
        }

        .stats-inner {
            max-width: 1100px; margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            text-align: center;
        }

        .stat-item { color: white; }
        .stat-item .sv { font-size: 2rem; font-weight: 900; color: var(--accent); line-height: 1; }
        .stat-item .sl { font-size: 0.82rem; color: rgba(255,255,255,0.65); margin-top: 4px; }

        /* ══════════════════════════════════════
           HOW IT WORKS
        ══════════════════════════════════════ */
        .section {
            padding: 90px 5%;
        }

        .section-bg { background: var(--gray-light); }

        .section-head { text-align: center; margin-bottom: 60px; }

        .s-tag {
            display: inline-block;
            background: rgba(26,35,126,0.08);
            color: var(--blue);
            padding: 5px 18px;
            border-radius: 100px;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 14px;
        }

        .s-title {
            font-size: 2.2rem;
            font-weight: 900;
            color: var(--navy);
            margin-bottom: 14px;
            letter-spacing: -0.5px;
        }

        .s-sub {
            font-size: 1rem;
            color: var(--gray-text);
            max-width: 520px;
            margin: 0 auto;
            line-height: 1.7;
        }

        .max-w { max-width: 1100px; margin: 0 auto; }

        /* Steps */
        .hiw-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            position: relative;
        }

        /* Connecting line */
        .hiw-grid::before {
            content: '';
            position: absolute;
            top: 48px; left: 12.5%; right: 12.5%;
            height: 2px;
            background: linear-gradient(90deg, var(--accent), var(--green));
            opacity: 0.3;
        }

        .hiw-card {
            background: white;
            border-radius: 20px;
            padding: 32px 22px;
            text-align: center;
            box-shadow: 0 2px 20px rgba(0,0,0,0.06);
            border: 1px solid #eef0f7;
            transition: all 0.3s;
            position: relative;
        }

        .hiw-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 16px 50px rgba(26,35,126,0.12);
            border-color: rgba(0,180,216,0.3);
        }

        .hiw-num {
            width: 48px; height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent), var(--blue-mid));
            color: white;
            font-weight: 900;
            font-size: 1rem;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 18px;
            box-shadow: 0 4px 15px rgba(0,180,216,0.35);
            position: relative; z-index: 1;
        }

        .hiw-icon  { font-size: 2.6rem; margin-bottom: 14px; }
        .hiw-title { font-size: 1rem; font-weight: 800; color: var(--navy); margin-bottom: 10px; }
        .hiw-desc  { font-size: 0.84rem; color: var(--gray-text); line-height: 1.65; }

        /* ══════════════════════════════════════
           FEATURES
        ══════════════════════════════════════ */
        .feat-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 22px;
        }

        .feat-card {
            background: white;
            border-radius: 20px;
            padding: 30px 26px;
            border: 1px solid #eef0f7;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .feat-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--accent), var(--green));
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.3s;
        }

        .feat-card:hover { transform: translateY(-6px); box-shadow: 0 16px 50px rgba(26,35,126,0.1); }
        .feat-card:hover::before { transform: scaleX(1); }

        .feat-icon-box {
            width: 52px; height: 52px;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 18px;
        }

        .feat-title { font-size: 1rem; font-weight: 800; color: var(--navy); margin-bottom: 8px; }
        .feat-desc  { font-size: 0.85rem; color: var(--gray-text); line-height: 1.65; }

        .feat-tag {
            display: inline-block;
            margin-top: 14px;
            background: var(--gray-light);
            color: var(--blue);
            padding: 3px 12px;
            border-radius: 100px;
            font-size: 0.72rem;
            font-weight: 700;
        }

        /* ══════════════════════════════════════
           CTA SECTION
        ══════════════════════════════════════ */
        .cta-section {
            background: var(--navy);
            padding: 90px 5%;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .cta-section .blob-1 { opacity: 0.5; }

        .cta-section h2 {
            font-size: 2.4rem;
            font-weight: 900;
            color: white;
            margin-bottom: 14px;
            letter-spacing: -0.5px;
            position: relative; z-index: 1;
        }

        .cta-section h2 span {
            background: linear-gradient(90deg, var(--accent), var(--green));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .cta-section p {
            color: rgba(255,255,255,0.6);
            font-size: 1rem;
            margin-bottom: 40px;
            position: relative; z-index: 1;
        }

        .cta-cards {
            display: flex;
            justify-content: center;
            gap: 24px;
            flex-wrap: wrap;
            position: relative; z-index: 1;
        }

        .cta-card {
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 36px 32px;
            width: 280px;
            text-decoration: none;
            color: white;
            transition: all 0.3s;
            backdrop-filter: blur(10px);
        }

        .cta-card:hover {
            background: rgba(255,255,255,0.12);
            transform: translateY(-8px);
            border-color: rgba(0,180,216,0.4);
            box-shadow: 0 20px 50px rgba(0,0,0,0.3);
        }

        .cta-card-icon { font-size: 3.5rem; margin-bottom: 16px; display: block; }
        .cta-card-label {
            display: inline-block;
            background: rgba(0,180,216,0.15);
            border: 1px solid rgba(0,180,216,0.3);
            color: var(--accent);
            padding: 3px 12px;
            border-radius: 100px;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 1px;
            margin-bottom: 12px;
        }

        .cta-card h3 { font-size: 1.2rem; font-weight: 800; margin-bottom: 8px; }
        .cta-card p  { font-size: 0.84rem; color: rgba(255,255,255,0.6); line-height: 1.6; margin-bottom: 20px; }

        .cta-card-btn {
            display: inline-flex; align-items: center; gap: 6px;
            background: linear-gradient(135deg, var(--accent), var(--blue-mid));
            color: white;
            padding: 10px 22px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.88rem;
            transition: all 0.2s;
        }

        .cta-card:hover .cta-card-btn {
            box-shadow: 0 6px 20px rgba(0,180,216,0.4);
        }

        /* Signup note */
        .signup-note {
            margin-top: 28px;
            color: rgba(255,255,255,0.45);
            font-size: 0.85rem;
            position: relative; z-index: 1;
        }

        .signup-note a {
            color: var(--accent);
            font-weight: 600;
            text-decoration: none;
            transition: opacity 0.2s;
        }

        .signup-note a:hover { opacity: 0.8; }

        /* ══════════════════════════════════════
           FOOTER
        ══════════════════════════════════════ */
        .footer {
            background: #06091a;
            padding: 36px 5%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
        }

        .footer-brand {
            display: flex; align-items: center; gap: 10px;
            text-decoration: none;
        }

        .footer-brand .brand-icon { width: 32px; height: 32px; font-size: 1rem; }
        .footer-brand .brand-name { font-size: 0.95rem; color: white; }

        .footer-copy {
            font-size: 0.82rem;
            color: rgba(255,255,255,0.3);
        }

        .footer-tech {
            font-size: 0.78rem;
            color: rgba(255,255,255,0.25);
        }

        /* ══════════════════════════════════════
           SCROLL REVEAL
        ══════════════════════════════════════ */
        .reveal {
            opacity: 0;
            transform: translateY(28px);
            transition: opacity 0.65s ease, transform 0.65s ease;
        }

        .reveal.shown { opacity: 1; transform: translateY(0); }
        .reveal.d1 { transition-delay: 0.05s; }
        .reveal.d2 { transition-delay: 0.15s; }
        .reveal.d3 { transition-delay: 0.25s; }
        .reveal.d4 { transition-delay: 0.35s; }
        @keyframes fadeIn { from{opacity:0;transform:translateY(8px);} to{opacity:1;transform:translateY(0);} }
        @media(max-width:768px) { #feedback .max-w > div { grid-template-columns:1fr !important; } }

        /* ══════════════════════════════════════
           RESPONSIVE
        ══════════════════════════════════════ */
        @media (max-width: 900px) {
            .hero-inner    { grid-template-columns: 1fr; text-align: center; padding-top: 100px; }
            .hero-right    { order: -1; }
            .hero-h1       { font-size: 2.4rem; }
            .hero-cta      { justify-content: center; }
            .trust-row     { justify-content: center; }
            .phone-mockup  { width: 220px; }
            .hiw-grid      { grid-template-columns: repeat(2, 1fr); }
            .hiw-grid::before { display: none; }
            .feat-grid     { grid-template-columns: repeat(2, 1fr); }
            .stats-inner   { grid-template-columns: repeat(2, 1fr); }
            .fc-1, .fc-2, .fc-3 { display: none; }
        }

        @media (max-width: 600px) {
            .hero-h1    { font-size: 2rem; }
            .hiw-grid   { grid-template-columns: 1fr; }
            .feat-grid  { grid-template-columns: 1fr; }
            .stats-inner{ grid-template-columns: repeat(2, 1fr); }
            .section    { padding: 60px 5%; }
            .nav-menu a:not(.btn-nav) { display: none; }
        }
    </style>
</head>
<body>

<!-- ═══════════════════════════════════════
     NAVBAR
═══════════════════════════════════════ -->
<nav class="nav" id="nav">
    <a href="#" class="nav-brand">
        <div class="brand-icon"><img src="assets/img/logo.png" style="width:100%;height:100%;object-fit:cover;border-radius:8px;" alt="Logo"></div>
        <span class="brand-name">Attend<span>Ease</span></span>
    </a>
    <div class="nav-menu">
        <a href="#how-it-works">How It Works</a>
        <a href="#features">Features</a>
        <a href="student_signup.php">Sign Up</a>
        <a href="teacher_login.php" style="color:rgba(255,255,255,0.75);">👨‍🏫 Teacher</a>
        <a href="student_login.php" class="btn-nav">👨‍🎓 Student Login</a>
    </div>
</nav>


<!-- ═══════════════════════════════════════
     HERO
═══════════════════════════════════════ -->
<section class="hero">
    <div class="hero-grid"></div>
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>

    <div class="hero-inner">
        <!-- Left -->
        <div class="hero-left">
            <div class="hero-pill">
                <div class="dot"></div>
                Smart Attendance System &nbsp;·&nbsp; Philippines
            </div>

            <h1 class="hero-h1">
                Smart Attendance<br>
                <span class="line2">Powered by Your Face</span>
            </h1>

            <p class="hero-p">
                No more paper logs or manual sign-ins.
                AttendEase uses face recognition and GPS verification
                to record student attendance in seconds — accurately and securely.
            </p>

            <div class="hero-cta">
                <a href="student_login.php" class="cta-primary">
                    👨‍🎓 Student Login
                </a>
                <a href="student_signup.php" class="cta-secondary">
                    📝 Create Account
                </a>
            </div>

            <div class="trust-row">
                <div class="trust-item"><span class="ti">✓</span> Free to use</div>
               <!-- <div class="trust-item"><span class="ti">✓</span> Works offline</div> -->
                <div class="trust-item"><span class="ti">✓</span> GPS verified</div>
                <div class="trust-item"><span class="ti">✓</span> Instant records</div>
            </div>
        </div>

        <!-- Right — Phone mockup -->
        <div class="hero-right">
            <div style="position:relative; display:inline-block;">
                <div class="phone-mockup">
                    <div class="phone-notch"></div>
                    <div class="cam-screen">
                        <div class="scan-frame">
                            <div class="sf-corner tl"></div>
                            <div class="sf-corner tr"></div>
                            <div class="sf-corner bl"></div>
                            <div class="sf-corner br"></div>
                            <span class="scan-face">🧑‍💻</span>
                            <div class="scan-bar"></div>
                        </div>
                        <div class="scan-status">SCANNING...</div>
                        <div class="scan-dots">
                            <div class="scan-dot active"></div>
                            <div class="scan-dot"></div>
                            <div class="scan-dot"></div>
                        </div>
                    </div>
                </div>

                <!-- Floating info cards -->
                <div class="float-card fc-1">
                    <span class="fc-icon">✅</span>
                    <span class="fc-label">Face verified</span><br>
                    <span class="fc-value">Juan D. Cruz</span>
                </div>
                <div class="float-card fc-2">
                    <span class="fc-icon">📍</span>
                    <span class="fc-label">Location</span><br>
                    <span class="fc-value">Within campus</span>
                </div>
                <div class="float-card fc-3">
                    <span class="fc-icon">⏰</span>
                    <span class="fc-label">Time In</span><br>
                    <span class="fc-value">7:58 AM</span>
                </div>
            </div>
        </div>
    </div>
</section>


<!-- ═══════════════════════════════════════
     STATS BAR
═══════════════════════════════════════ -->
<div class="stats-bar">
    <div class="stats-inner">
        <div class="stat-item reveal d1">
            <div class="sv">⚡</div>
            <div class="sl">Fast Face Scan</div>
        </div>
        <div class="stat-item reveal d2">
            <div class="sv">📍</div>
            <div class="sl">GPS Verified</div>
        </div>
        <div class="stat-item reveal d3">
            <div class="sv">🔒</div>
            <div class="sl">Secure & Private</div>
        </div>
        <div class="stat-item reveal d4">
            <div class="sv">📊</div>
            <div class="sl">Real-Time Reports</div>
        </div>
    </div>
</div>


<!-- ═══════════════════════════════════════
     HOW IT WORKS
═══════════════════════════════════════ -->
<section class="section" id="how-it-works">
    <div class="max-w">
        <div class="section-head">
            <div class="s-tag">How It Works</div>
            <h2 class="s-title">Attendance in 4 Simple Steps</h2>
            <p class="s-sub">From registration to attendance record — the whole process takes less than a minute.</p>
        </div>

        <div class="hiw-grid">
            <div class="hiw-card reveal d1">
                <div class="hiw-num">1</div>
                <div class="hiw-icon">📝</div>
                <div class="hiw-title">Sign Up Once</div>
                <div class="hiw-desc">Verify your student ID, set a password, and register your face using your device camera. Done once, used forever.</div>
            </div>
            <div class="hiw-card reveal d2">
                <div class="hiw-num">2</div>
                <div class="hiw-icon">🔐</div>
                <div class="hiw-title">Login Securely</div>
                <div class="hiw-desc">Enter your Student ID and password. Your registered face is loaded from the system for verification.</div>
            </div>
            <div class="hiw-card reveal d3">
                <div class="hiw-num">3</div>
                <div class="hiw-icon">📍</div>
                <div class="hiw-title">Location Verified</div>
                <div class="hiw-desc">GPS confirms you are within the campus boundary set by your admin. Cannot be spoofed from outside.</div>
            </div>
            <div class="hiw-card reveal d4">
                <div class="hiw-num">4</div>
                <div class="hiw-icon">✅</div>
                <div class="hiw-title">Attendance Saved</div>
                <div class="hiw-desc">Face is matched and attendance is automatically recorded with your name, subject, time, and verified location.</div>
            </div>
        </div>
    </div>
</section>


<!-- ═══════════════════════════════════════
     FEATURES
═══════════════════════════════════════ -->
<section class="section section-bg" id="features">
    <div class="max-w">
        <div class="section-head">
            <div class="s-tag">Features</div>
            <h2 class="s-title">Built for Modern Schools</h2>
            <p class="s-sub">Everything needed for accurate, tamper-proof, and effortless attendance management.</p>
        </div>

        <div class="feat-grid">
            <div class="feat-card reveal d1">
                <div class="feat-icon-box" style="background:#e8f4fd;">🤖</div>
                <div class="feat-title">AI Face Recognition</div>
                <div class="feat-desc">Uses face-api.js TinyFaceDetector — fast, lightweight, and runs entirely in the browser. No cloud needed.</div>
                <span class="feat-tag">face-api.js</span>
            </div>
            <div class="feat-card reveal d2">
                <div class="feat-icon-box" style="background:#e8fdf5;">📍</div>
                <div class="feat-title">GPS Boundary Check</div>
                <div class="feat-desc">Admin sets the allowed GPS radius. Students outside campus are blocked — no proxy attendance possible.</div>
                <span class="feat-tag">Geolocation API</span>
            </div>
            <div class="feat-card reveal d3">
                <div class="feat-icon-box" style="background:#fdf4e8;">🌗</div>
                <div class="feat-title">Low-Light Adaptive</div>
                <div class="feat-desc">Auto-adjusts camera brightness and contrast so face detection works in dim environments too.</div>
                <span class="feat-tag">CSS Filter</span>
            </div>
            <div class="feat-card reveal d1">
                <div class="feat-icon-box" style="background:#f4e8fd;">🔒</div>
                <div class="feat-title">Secure Accounts</div>
                <div class="feat-desc">Passwords are hashed with bcrypt. Face descriptors are stored as encrypted vectors — not raw images.</div>
                <span class="feat-tag">PHP bcrypt</span>
            </div>
            <div class="feat-card reveal d2">
                <div class="feat-icon-box" style="background:#e8eafd;">📊</div>
                <div class="feat-title">Admin Dashboard</div>
                <div class="feat-desc">Full control panel for managing students, viewing daily attendance, configuring GPS zones, and printing reports.</div>
                <span class="feat-tag">PHP + MySQL</span>
            </div>
            <div class="feat-card reveal d3">
                <div class="feat-icon-box" style="background:#fde8e8;">📋</div>
                <div class="feat-title">Printable Reports</div>
                <div class="feat-desc">Filter attendance by date, subject, or status. Print formatted PDF-ready reports directly from the browser.</div>
                <span class="feat-tag">Print API</span>
            </div>
        </div>
    </div>
</section>


<!-- ═══════════════════════════════════════
     CTA — STUDENT ONLY (no admin link)
═══════════════════════════════════════ -->
<section class="cta-section">
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>

    <div class="s-tag" style="background:rgba(0,180,216,0.12); color:var(--accent);">
        Get Started
    </div>
    <h2 style="margin-top:12px;">
        Ready to Record<br><span>Your Attendance?</span>
    </h2>
    <p>Login with your face. It's that simple.</p>

    <div class="cta-cards">
        <a href="student_login.php" class="cta-card">
            <span class="cta-card-icon">👨‍🎓</span>
            <span class="cta-card-label">STUDENT PORTAL</span>
            <h3>Student Login</h3>
            <p>Already registered? Login with your Gmail, password, and face scan to record attendance.</p>
            <span class="cta-card-btn">Login Now →</span>
        </a>

        <a href="student_signup.php" class="cta-card">
            <span class="cta-card-icon">📝</span>
            <span class="cta-card-label">FIRST TIME?</span>
            <h3>Create Account</h3>
            <p>New student? Verify your student ID, set a password, and register your face to get started.</p>
            <span class="cta-card-btn">Sign Up Free →</span>
        </a>

        <a href="teacher_login.php" class="cta-card" style="border-color:rgba(165,214,167,0.3);">
            <span class="cta-card-icon">👨‍🏫</span>
            <span class="cta-card-label" style="background:rgba(46,125,50,0.2);border-color:rgba(165,214,167,0.4);color:#a5d6a7;">TEACHER PORTAL</span>
            <h3>Teacher Login</h3>
            <p>Manage your classes, take attendance, set location, and view reports for your subjects.</p>
            <span class="cta-card-btn" style="background:linear-gradient(135deg,#2e7d32,#43a047);">Go to Portal →</span>
        </a>
    </div>

    <div class="signup-note">
        Having trouble? Contact your class administrator for assistance.
    </div>
</section>


<!-- ═══════════════════════════════════════
     FEEDBACK SECTION
═══════════════════════════════════════ -->
<section class="section section-bg" id="feedback" style="padding:90px 5%;">
    <div class="max-w">
        <div class="section-head">
            <div class="s-tag">Feedback</div>
            <h2 class="s-title">What Our Users Say</h2>
            <p class="s-sub">Share your experience with AttendEase and help us improve the system.</p>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:40px;align-items:start;">

            <!-- Testimonials Carousel -->
            <div>
                <div style="font-size:0.78rem;font-weight:800;color:var(--blue);letter-spacing:1px;margin-bottom:18px;text-transform:uppercase;">⭐ User Testimonials</div>

                <?php
                require_once 'config/database.php';
                $conn2 = getConnection();
                $fbres = $conn2->query("SELECT * FROM feedbacks WHERE is_visible=1 ORDER BY created_at DESC LIMIT 6");
                $feedbacks = [];
                while ($fb = $fbres->fetch_assoc()) $feedbacks[] = $fb;
                $role_icons = ['student'=>'👨‍🎓','teacher'=>'👨‍🏫','admin'=>'🛡️','other'=>'👤'];
                ?>

                <div id="fb-slider" style="position:relative;overflow:hidden;min-height:180px;">
                    <?php foreach($feedbacks as $i => $fb): ?>
                    <div class="fb-slide" style="display:<?= $i===0?'block':'none' ?>;animation:fadeIn 0.4s ease;">
                        <div style="background:white;border-radius:18px;padding:26px;box-shadow:0 4px 20px rgba(0,0,0,0.08);border:1px solid #eef0f7;position:relative;">
                            <!-- Quote mark -->
                            <div style="font-size:4rem;color:#e8eaf6;position:absolute;top:10px;left:18px;line-height:1;font-family:Georgia,serif;">"</div>
                            <!-- Stars -->
                            <div style="margin-bottom:10px;padding-left:8px;">
                                <?php for($s=1;$s<=5;$s++): ?>
                                <span style="color:<?= $s<=$fb['rating']?'#ffa726':'#e0e0e0' ?>;font-size:1rem;">★</span>
                                <?php endfor; ?>
                            </div>
                            <p style="font-size:0.9rem;color:#444;line-height:1.7;margin-bottom:16px;position:relative;z-index:1;padding-left:8px;">
                                <?= htmlspecialchars($fb['message']) ?>
                            </p>
                            <div style="display:flex;align-items:center;gap:12px;padding-left:8px;">
                                <div style="width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--blue-mid));display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0;">
                                    <?= $role_icons[$fb['role']] ?? '👤' ?>
                                </div>
                                <div>
                                    <div style="font-weight:700;font-size:0.88rem;color:var(--navy);"><?= htmlspecialchars($fb['name']) ?></div>
                                    <div style="font-size:0.74rem;color:#aaa;text-transform:capitalize;"><?= htmlspecialchars($fb['role']) ?> &nbsp;·&nbsp; <?= date('M Y', strtotime($fb['created_at'])) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Dots navigation -->
                <?php if(count($feedbacks) > 1): ?>
                <div style="display:flex;justify-content:center;gap:8px;margin-top:16px;" id="fb-dots">
                    <?php foreach($feedbacks as $i => $fb): ?>
                    <button onclick="goSlide(<?= $i ?>)"
                            style="width:<?= $i===0?'22':'8' ?>px;height:8px;border-radius:4px;background:<?= $i===0?'var(--blue-mid)':'#ddd' ?>;border:none;cursor:pointer;transition:all 0.3s;padding:0;"
                            class="fb-dot" data-idx="<?= $i ?>"></button>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Feedback Form -->
            <div>
                <div style="font-size:0.78rem;font-weight:800;color:var(--blue);letter-spacing:1px;margin-bottom:18px;text-transform:uppercase;">📝 Leave Your Feedback</div>

                <div style="background:white;border-radius:18px;padding:28px;box-shadow:0 4px 20px rgba(0,0,0,0.08);border:1px solid #eef0f7;">

                    <div id="fb-success" style="display:none;background:#e8fdf5;border:1px solid #a5d6a7;border-radius:12px;padding:16px;text-align:center;margin-bottom:16px;">
                        <div style="font-size:2rem;margin-bottom:6px;">🎉</div>
                        <div style="font-weight:700;color:#2e7d32;">Thank you for your feedback!</div>
                        <div style="font-size:0.82rem;color:#555;margin-top:4px;">Your feedback has been submitted successfully.</div>
                    </div>
                    <div id="fb-error" style="display:none;background:#ffebee;border:1px solid #ffcdd2;border-radius:10px;padding:12px;font-size:0.84rem;color:#e53935;margin-bottom:14px;"></div>

                    <form id="fb-form">
                        <!-- Star Rating -->
                        <div style="margin-bottom:18px;">
                            <label style="display:block;font-size:0.82rem;font-weight:700;color:#333;margin-bottom:10px;">Your Rating *</label>
                            <div id="star-rating" style="display:flex;gap:6px;">
                                <?php for($s=1;$s<=5;$s++): ?>
                                <button type="button" onclick="setRating(<?= $s ?>)" data-star="<?= $s ?>"
                                        style="font-size:2rem;background:none;border:none;cursor:pointer;color:#e0e0e0;transition:all 0.15s;padding:0;line-height:1;">★</button>
                                <?php endfor; ?>
                            </div>
                            <input type="hidden" id="fb-rating" value="5">
                        </div>

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
                            <div>
                                <label style="display:block;font-size:0.82rem;font-weight:700;color:#333;margin-bottom:6px;">Gmail Address *</label>
                                <input type="email" id="fb-name" placeholder="your@gmail.com"
                                       style="width:100%;padding:10px 13px;border:1.5px solid #e0e0e0;border-radius:9px;font-size:0.9rem;outline:none;transition:border-color 0.2s;"
                                       onfocus="this.style.borderColor='var(--blue-mid)'" onblur="this.style.borderColor='#e0e0e0'" required>
                            </div>
                            <div>
                                <label style="display:block;font-size:0.82rem;font-weight:700;color:#333;margin-bottom:6px;">I am a</label>
                                <select id="fb-role"
                                        style="width:100%;padding:10px 13px;border:1.5px solid #e0e0e0;border-radius:9px;font-size:0.9rem;outline:none;background:white;transition:border-color 0.2s;"
                                        onfocus="this.style.borderColor='var(--blue-mid)'" onblur="this.style.borderColor='#e0e0e0'">
                                    <option value="student">👨‍🎓 Student</option>
                                    <option value="teacher">👨‍🏫 Teacher</option>
                                    <option value="admin">🛡️ Admin</option>
                                    <option value="other">👤 Other</option>
                                </select>
                            </div>
                        </div>

                        <div style="margin-bottom:18px;">
                            <label style="display:block;font-size:0.82rem;font-weight:700;color:#333;margin-bottom:6px;">Your Message *</label>
                            <textarea id="fb-message" rows="4" placeholder="Share your experience with AttendEase..."
                                      style="width:100%;padding:10px 13px;border:1.5px solid #e0e0e0;border-radius:9px;font-size:0.9rem;outline:none;resize:vertical;font-family:inherit;transition:border-color 0.2s;"
                                      onfocus="this.style.borderColor='var(--blue-mid)'" onblur="this.style.borderColor='#e0e0e0'" required></textarea>
                            <div style="text-align:right;font-size:0.72rem;color:#aaa;margin-top:3px;"><span id="char-count">0</span> / 500</div>
                        </div>

                        <button type="submit" id="fb-btn"
                                style="width:100%;padding:13px;background:linear-gradient(135deg,var(--accent),var(--blue-mid));color:white;border:none;border-radius:10px;font-size:0.96rem;font-weight:700;cursor:pointer;transition:all 0.2s;box-shadow:0 4px 15px rgba(0,180,216,0.3);">
                            📨 Submit Feedback
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>


<!-- ═══════════════════════════════════════
     FOOTER
═══════════════════════════════════════ -->
<footer class="footer">
    <a href="#" class="footer-brand">
        <div class="brand-icon"><img src="assets/img/logo.png" style="width:100%;height:100%;object-fit:cover;border-radius:6px;" alt="Logo"></div>
        <span class="brand-name">Attend<span style="color:var(--accent)">Ease</span></span>
    </a>
    <div class="footer-copy">
        Face Recognition-Based Student Attendance Monitoring System &nbsp;·&nbsp; <?= date('Y') ?>
    </div>
    <div class="footer-tech">PHP · MySQL · JavaScript · face-api.js</div>
</footer>


<!-- ═══════════════════════════════════════
     SCRIPTS
═══════════════════════════════════════ -->
<script>
// ── Navbar scroll effect ──
const nav = document.getElementById('nav');
window.addEventListener('scroll', () => {
    nav.classList.toggle('scrolled', window.scrollY > 40);
});

// ── Scroll reveal ──
const observer = new IntersectionObserver((entries) => {
    entries.forEach(e => {
        if (e.isIntersecting) {
            e.target.classList.add('shown');
            observer.unobserve(e.target);
        }
    });
}, { threshold: 0.12 });

document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

// ── Animated scan dots ──
const dots = document.querySelectorAll('.scan-dot');
let  idx   = 0;
setInterval(() => {
    dots.forEach(d => d.classList.remove('active'));
    idx = (idx + 1) % dots.length;
    dots[idx].classList.add('active');
}, 800);

// ── Secret admin access — triple-click footer brand ──
// Admin URL is hidden. Access via: /AttendanceSystem/admin/
let clickCount = 0;
document.querySelector('.footer-brand').addEventListener('click', (e) => {
    e.preventDefault();
    clickCount++;
    if (clickCount >= 5) {
        clickCount = 0;
        window.location.href = 'admin/index.php';
    }
    setTimeout(() => { clickCount = 0; }, 2000);
});

// ── Feedback: Star Rating ──
let currentRating = 5;
function setRating(val) {
    currentRating = val;
    document.getElementById('fb-rating').value = val;
    document.querySelectorAll('#star-rating button').forEach((btn, i) => {
        btn.style.color     = i < val ? '#ffa726' : '#e0e0e0';
        btn.style.transform = i < val ? 'scale(1.15)' : 'scale(1)';
    });
}
setRating(5);

document.querySelectorAll('#star-rating button').forEach((btn, i) => {
    btn.addEventListener('mouseenter', () => {
        document.querySelectorAll('#star-rating button').forEach((b, j) => {
            b.style.color = j <= i ? '#ffca28' : '#e0e0e0';
        });
    });
    btn.addEventListener('mouseleave', () => setRating(currentRating));
});

const fbMsg = document.getElementById('fb-message');
if (fbMsg) {
    fbMsg.addEventListener('input', () => {
        const len = Math.min(fbMsg.value.length, 500);
        document.getElementById('char-count').textContent = len;
        if (fbMsg.value.length > 500) fbMsg.value = fbMsg.value.slice(0, 500);
    });
}

const fbForm = document.getElementById('fb-form');
if (fbForm) {
    fbForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn    = document.getElementById('fb-btn');
        const name   = document.getElementById('fb-name').value.trim();
        const role   = document.getElementById('fb-role').value;
        const msg    = document.getElementById('fb-message').value.trim();
        const rat    = parseInt(document.getElementById('fb-rating').value);
        const errEl  = document.getElementById('fb-error');
        if (!name || !msg) { errEl.style.display='block'; errEl.textContent='Please fill in all required fields.'; return; }
        errEl.style.display = 'none';
        btn.disabled = true; btn.textContent = '⏳ Submitting...';
        try {
            const res  = await fetch('api/submit_feedback.php', {
                method:'POST', headers:{'Content-Type':'application/json'},
                body: JSON.stringify({ name, role, rating:rat, message:msg })
            });
            const data = await res.json();
            if (data.success) {
                fbForm.style.display = 'none';
                document.getElementById('fb-success').style.display = 'block';
            } else {
                errEl.style.display = 'block'; errEl.textContent = data.message || 'Failed.';
                btn.disabled = false; btn.textContent = '📨 Submit Feedback';
            }
        } catch(err) {
            errEl.style.display = 'block'; errEl.textContent = 'Network error. Please try again.';
            btn.disabled = false; btn.textContent = '📨 Submit Feedback';
        }
    });
}

// ── Testimonial Slider ──
let fbCurrent = 0;
const fbSlides = document.querySelectorAll('.fb-slide');
const fbDots   = document.querySelectorAll('.fb-dot');

function goSlide(idx) {
    if (!fbSlides.length) return;
    fbSlides[fbCurrent].style.display = 'none';
    if (fbDots[fbCurrent]) { fbDots[fbCurrent].style.width='8px'; fbDots[fbCurrent].style.background='#ddd'; }
    fbCurrent = idx;
    fbSlides[fbCurrent].style.display = 'block';
    if (fbDots[fbCurrent]) { fbDots[fbCurrent].style.width='22px'; fbDots[fbCurrent].style.background='var(--blue-mid)'; }
}

if (fbSlides.length > 1) {
    setInterval(() => goSlide((fbCurrent + 1) % fbSlides.length), 5000);
}
</script>

</body>
</html>
