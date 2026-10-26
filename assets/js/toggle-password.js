/**
 * Password Toggle Visibility
 * Auto-initializes on all password inputs with data-toggle="password"
 * or call initPasswordToggles() manually after DOM ready
 */
(function () {
    'use strict';

    const EYE_OPEN   = `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>`;
    const EYE_CLOSED = `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>`;

    function wrapAndInject(input) {
        // Skip if already wrapped
        if (input.parentElement && input.parentElement.classList.contains('pw-wrap')) return;

        // Wrap the input
        const wrapper = document.createElement('div');
        wrapper.className = 'pw-wrap';
        wrapper.style.cssText = 'position:relative;display:block;';
        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);

        // Add right padding to input so text doesn't overlap icon
        input.style.paddingRight = '44px';

        // Create toggle button
        const btn = document.createElement('button');
        btn.type        = 'button';
        btn.className   = 'pw-toggle-btn';
        btn.innerHTML   = EYE_OPEN;
        btn.title       = 'Show/Hide password';
        btn.setAttribute('aria-label', 'Toggle password visibility');
        btn.style.cssText = `
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #aaa;
            padding: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            transition: color 0.2s;
            z-index: 5;
        `;

        btn.addEventListener('mouseenter', () => { btn.style.color = '#555'; });
        btn.addEventListener('mouseleave', () => { btn.style.color = input.type === 'text' ? '#1565c0' : '#aaa'; });

        btn.addEventListener('click', function (e) {
            e.preventDefault();
            if (input.type === 'password') {
                input.type      = 'text';
                btn.innerHTML   = EYE_CLOSED;
                btn.style.color = '#1565c0';
                btn.title       = 'Hide password';
            } else {
                input.type      = 'password';
                btn.innerHTML   = EYE_OPEN;
                btn.style.color = '#aaa';
                btn.title       = 'Show password';
            }
            input.focus();
        });

        wrapper.appendChild(btn);
    }

    function initPasswordToggles() {
        // Target all password inputs on page
        document.querySelectorAll('input[type="password"]').forEach(wrapAndInject);
    }

    // Auto-init when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPasswordToggles);
    } else {
        initPasswordToggles();
    }

    // Expose globally for manual calls
    window.initPasswordToggles = initPasswordToggles;
})();
