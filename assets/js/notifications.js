/**
 * Real-time Notification System
 * - Polls server every 10 seconds
 * - Shows toast popup on new notifications
 * - Plays notification sound
 * - Updates badge count in navbar/sidebar
 * - Requests browser notification permission
 */
(function () {
    'use strict';

    const POLL_INTERVAL = 3000; // 3 seconds — real-time feel
    const API_URL = (function () {
        const p    = window.location.pathname;
        const base = window.location.origin;
        // XAMPP subfolder
        if (p.includes('/AttendanceSystem/')) {
            return base + '/AttendanceSystem/api/check_notifications.php';
        }
        // php -S localhost:8000 — depth-based
        const depth = (p.replace(/\/+$/, '').split('/').filter(Boolean)).length;
        let prefix  = '';
        for (let i = 0; i < depth; i++) prefix += '../';
        return prefix + 'api/check_notifications.php';
    })();

    let lastCount  = -1;  // -1 = first load, don't show popup
    let toastQueue = [];
    let isShowingToast = false;

    // ── Notification Sound (Web Audio API — no file needed) ──
    function playSound() {
        try {
            const ctx  = new (window.AudioContext || window.webkitAudioContext)();
            // Play 3-note chime
            [[880, 0], [1100, 0.15], [1320, 0.3]].forEach(([freq, when]) => {
                const osc  = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.frequency.setValueAtTime(freq, ctx.currentTime + when);
                gain.gain.setValueAtTime(0.35, ctx.currentTime + when);
                gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + when + 0.4);
                osc.start(ctx.currentTime + when);
                osc.stop(ctx.currentTime + when + 0.4);
            });
        } catch (e) {}
    }

    // ── Toast Notification ──
    function createToastContainer() {
        if (document.getElementById('notif-toast-container')) return;
        const container = document.createElement('div');
        container.id = 'notif-toast-container';
        container.style.cssText = `
            position: fixed;
            top: 76px;
            right: 16px;
            z-index: 99999;
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-width: 380px;
            width: calc(100% - 32px);
            pointer-events: none;
        `;
        document.body.appendChild(container);
    }

    const typeIcons = {
        attendance: '📋',
        schedule:   '🗓️',
        system:     '⚙️',
        manual:     '✏️',
        default:    '🔔'
    };

    const typeColors = {
        attendance: '#1565c0',
        schedule:   '#e65100',
        system:     '#6a1b9a',
        manual:     '#2e7d32',
        default:    '#1a237e'
    };

    function showToast(notif) {
        createToastContainer();
        const container = document.getElementById('notif-toast-container');
        const icon      = typeIcons[notif.type]   || typeIcons.default;
        const color     = typeColors[notif.type]  || typeColors.default;

        const toast = document.createElement('div');
        toast.style.cssText = `
            background: white;
            border-radius: 14px;
            padding: 14px 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.18), 0 2px 8px rgba(0,0,0,0.12);
            border-left: 4px solid ${color};
            pointer-events: all;
            cursor: pointer;
            transform: translateX(120%);
            transition: transform 0.35s cubic-bezier(.175,.885,.32,1.275);
            display: flex;
            gap: 12px;
            align-items: flex-start;
            min-width: 300px;
        `;

        toast.innerHTML = `
            <div style="font-size:1.8rem;flex-shrink:0;margin-top:2px;">${icon}</div>
            <div style="flex:1;">
                <div style="font-weight:700;font-size:0.92rem;color:#1a1a2e;margin-bottom:4px;">${escHtml(notif.title)}</div>
                <div style="font-size:0.82rem;color:#555;line-height:1.5;">${escHtml(notif.message.length > 90 ? notif.message.slice(0, 90) + '...' : notif.message)}</div>
                <div style="font-size:0.7rem;color:#aaa;margin-top:5px;">🕐 Just now · Click to view</div>
            </div>
            <button style="background:none;border:none;cursor:pointer;color:#bbb;font-size:1.1rem;padding:0 0 0 8px;flex-shrink:0;align-self:flex-start;" onclick="this.parentElement.remove()">✕</button>
        `;

        const toastId = 'toast-' + Date.now();
        toast.id = toastId;
        container.appendChild(toast);

        // Animate in
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                toast.style.transform = 'translateX(0)';
            });
        });

        // Click to go to notifications page
        toast.addEventListener('click', () => {
            const p = window.location.pathname;
            if (p.includes('/teacher/'))     window.location.href = 'notifications.php';
            else if (p.includes('/student/')) window.location.href = 'notifications.php';
            else if (p.includes('/pages/'))   window.location.href = 'notifications.php';
        });

        // Auto-dismiss after 6 seconds
        setTimeout(() => {
            toast.style.transform = 'translateX(120%)';
            toast.style.opacity   = '0';
            setTimeout(() => toast.remove(), 400);
        }, 6000);
    }

    function escHtml(str) {
        const d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    // ── Update badge counts in UI ──
    function updateBadges(count) {
        // Admin/Teacher sidebar badge
        const badges = document.querySelectorAll('.notif-badge, [data-notif-badge]');
        badges.forEach(b => {
            b.textContent = count > 9 ? '9+' : count;
            b.style.display = count > 0 ? 'inline-flex' : 'none';
        });

        // Student tab badge (different selector)
        const tabBadges = document.querySelectorAll('.notif-tab-badge');
        tabBadges.forEach(b => {
            b.textContent = count > 9 ? '9+' : count;
            b.style.display = count > 0 ? 'inline-block' : 'none';
        });

        // Browser tab title
        if (count > 0) {
            document.title = `(${count}) ${document.title.replace(/^\(\d+\)\s/, '')}`;
        } else {
            document.title = document.title.replace(/^\(\d+\)\s/, '');
        }
    }

    // ── Browser Push Notification ──
    function sendBrowserNotif(notif) {
        if (!('Notification' in window) || Notification.permission !== 'granted') return;
        const icon = '/AttendanceSystem/assets/img/logo.png';
        try {
            const n = new Notification(notif.title, {
                body: notif.message,
                icon: icon,
                badge: icon,
                tag:   'attendance-notif-' + notif.id,
                requireInteraction: false
            });
            n.onclick = () => { window.focus(); n.close(); };
            setTimeout(() => n.close(), 6000);
        } catch(e) {}
    }

    // ── Request browser notification permission ──
    function requestPermission() {
        if (!('Notification' in window)) return;
        if (Notification.permission === 'default') {
            Notification.requestPermission();
        }
    }

    // ── Poll server ──
    let seenIds = new Set();

    async function poll() {
        try {
            const res  = await fetch(API_URL + '?t=' + Date.now());
            const data = await res.json();

            const count   = data.count || 0;
            const notifs  = data.notifications || [];

            updateBadges(count);

            // First load — show toasts for all existing unread notifications
            if (lastCount === -1) {
                notifs.forEach((n, i) => {
                    seenIds.add(n.id);
                    // Stagger the toasts so they don't all appear at once
                    setTimeout(() => {
                        showToast(n);
                        if (i === 0) playSound();
                    }, i * 700);
                });
                lastCount = count;
                return;
            }

            // Show toasts for NEW notifications only
            notifs.forEach(n => {
                if (!seenIds.has(n.id)) {
                    seenIds.add(n.id);
                    showToast(n);
                    sendBrowserNotif(n);
                    playSound();
                }
            });

            lastCount = count;

        } catch(e) {
            // Server not available — ignore silently
        }
    }

    // ── Init ──
    document.addEventListener('DOMContentLoaded', () => {
        requestPermission();
        poll(); // immediate first check
        setInterval(poll, POLL_INTERVAL);
    });

})();
