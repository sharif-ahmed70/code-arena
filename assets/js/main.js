/* ============================================================
   CODE ARENA — Shared JS Utilities
   File: assets/js/main.js
   ============================================================ */

// ── Toast Notifications ──────────────────────────────────────
(function () {
    const container = document.createElement('div');
    container.id = 'toast-container';
    container.style.cssText =
        'position:fixed;bottom:24px;right:24px;z-index:9999;display:flex;flex-direction:column;gap:10px;';
    document.body.appendChild(container);

    window.toast = function (msg, type = 'info', duration = 3500) {
        const el = document.createElement('div');
        const colors = { success: '#00ff88', error: '#ff4f4f', info: '#4f7ef8', warn: '#ffd166' };
        const color = colors[type] || colors.info;
        el.style.cssText = `
            background:#111118;border:1px solid ${color}44;border-left:3px solid ${color};
            color:#e8e8f0;padding:12px 18px;border-radius:8px;
            font-family:'Inter',sans-serif;font-size:0.9rem;max-width:320px;
            box-shadow:0 4px 20px rgba(0,0,0,0.4);animation:toastIn .25s ease;
        `;
        el.textContent = msg;
        container.appendChild(el);
        setTimeout(() => {
            el.style.animation = 'toastOut .25s ease forwards';
            setTimeout(() => el.remove(), 250);
        }, duration);
    };

    const style = document.createElement('style');
    style.textContent = `
        @keyframes toastIn  { from{opacity:0;transform:translateX(20px)} to{opacity:1;transform:none} }
        @keyframes toastOut { from{opacity:1;transform:none} to{opacity:0;transform:translateX(20px)} }
    `;
    document.head.appendChild(style);
})();

// ── Fetch helper ─────────────────────────────────────────────
window.api = async function (url, options = {}) {
    try {
        const res = await fetch(url, {
            credentials: 'include',
            headers: { 'Content-Type': 'application/json', ...options.headers },
            ...options,
        });
        const data = await res.json();
        return { ok: res.ok, status: res.status, data };
    } catch (e) {
        return { ok: false, status: 0, data: { success: false, message: e.message } };
    }
};

// ── Relative time ─────────────────────────────────────────────
window.timeAgo = function (dateStr) {
    const d   = new Date(dateStr);
    const sec = Math.floor((Date.now() - d) / 1000);
    if (sec < 60)    return 'just now';
    if (sec < 3600)  return Math.floor(sec / 60) + 'm ago';
    if (sec < 86400) return Math.floor(sec / 3600) + 'h ago';
    if (sec < 604800) return Math.floor(sec / 86400) + 'd ago';
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
};

window.difficultyBadge = function (diff) {
    const cls = { Easy: 'badge-easy', Medium: 'badge-medium', Hard: 'badge-hard' };
    return `<span class="badge ${cls[diff] || ''}">${diff}</span>`;
};

window.statusBadge = function (status) {
    const cls = {
        'Accepted':              'badge-accepted',
        'Wrong Answer':          'badge-wrong',
        'Runtime Error':         'badge-error',
        'Time Limit Exceeded':   'badge-error',
        'Compilation Error':     'badge-error',
        'Pending':               'badge-pending',
    };
    return `<span class="badge ${cls[status] || 'badge-pending'}">${status}</span>`;
};

window.langName = function (lang) {
    const map = {
        javascript: 'JavaScript', python: 'Python', cpp: 'C++', c: 'C',
        java: 'Java', go: 'Go', rust: 'Rust', ruby: 'Ruby',
        php: 'PHP', typescript: 'TypeScript', kotlin: 'Kotlin', swift: 'Swift',
    };
    return map[lang] || lang;
};

// Keep browser extension temp-mail badges from covering CodeArena inputs.
(function removeTempMailOverlays() {
    const looksLikeTempMail = node => {
        if (!node || node.nodeType !== 1) return false;
        const value = [
            node.id,
            node.className,
            node.getAttribute?.('title'),
            node.getAttribute?.('aria-label'),
            node.getAttribute?.('alt'),
            node.getAttribute?.('src'),
            node.textContent,
        ].filter(Boolean).join(' ').toLowerCase();
        return value.includes('tempmail') || value.includes('temp-mail');
    };

    const clean = root => {
        if (!root?.querySelectorAll) return;
        root.querySelectorAll('[id],[class],[title],[aria-label],[alt],[src]').forEach(node => {
            if (looksLikeTempMail(node)) node.remove();
        });
    };

    clean(document);
    new MutationObserver(mutations => {
        mutations.forEach(mutation => {
            mutation.addedNodes.forEach(node => {
                if (looksLikeTempMail(node)) {
                    node.remove();
                } else {
                    clean(node);
                }
            });
        });
    }).observe(document.documentElement, { childList: true, subtree: true });
})();
