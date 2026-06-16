<?php
require_once '../includes/session.php';
require_once '../config/db.php';
require_once '../includes/organization.php';

$organization = requireOrganizationPage($pdo);
$pageTitle = 'Create Contest';
$activeOrgPage = 'contests';
$contestId = (int)($_GET['id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Contest - Code Arena</title>
    <link rel="stylesheet" href="/code-arena/assets/css/style.css?v=20260615-ui5">
    <style>
        .problem-source-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
        .problem-source-head { display:flex; justify-content:space-between; gap:10px; align-items:center; margin-bottom:10px; }
        .problem-source-head h4 { margin:0; font-size:.98rem; }
        .problem-choice {
            display:flex; gap:10px; align-items:flex-start; padding:10px;
            border:1px solid var(--border); border-radius:var(--radius-sm); background:var(--bg-card);
        }
        .problem-choice input { margin-top:4px; }
        @media(max-width:900px) { .problem-source-grid { grid-template-columns:1fr; } }
    </style>
</head>
<body>
<?php require_once '../includes/organization_shell.php'; ?>

<div class="org-content">
    <div class="org-card" style="max-width:960px">
        <h2 style="margin-bottom:16px"><?= $contestId ? 'Edit Contest' : 'Create Contest' ?></h2>

        <h3 style="margin:0 0 12px;color:var(--text-muted);font-size:.82rem;text-transform:uppercase;letter-spacing:.06em">Step 1: Contest Info</h3>
        <div class="form-group"><label class="form-label">Title</label><input id="title" class="form-input"></div>
        <div class="form-group"><label class="form-label">Description</label><textarea id="description" class="form-input" rows="4"></textarea></div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
            <div class="form-group"><label class="form-label">Start Time</label><input id="start_time" type="datetime-local" class="form-input"></div>
            <div class="form-group"><label class="form-label">End Time</label><input id="end_time" type="datetime-local" class="form-input"></div>
        </div>

        <h3 style="margin:18px 0 12px;color:var(--text-muted);font-size:.82rem;text-transform:uppercase;letter-spacing:.06em">Step 2: Select Problems</h3>
        <div class="org-card" style="background:var(--bg-card2);padding:12px;margin-bottom:14px">
            <div class="problem-source-grid">
                <section>
                    <div class="problem-source-head">
                        <h4>Organization Problem Bank</h4>
                        <a href="/code-arena/organization/problem_create.php" class="btn-outline btn-sm">Add Problem</a>
                    </div>
                    <div id="problem-bank" class="org-grid"></div>
                </section>
                <section>
                    <div class="problem-source-head">
                        <h4>CodeArena Problem Set</h4>
                        <input id="platform-problem-search" class="form-input" placeholder="Search" style="max-width:180px" oninput="platformSearchChanged()">
                    </div>
                    <div id="selected-platform-problems" style="display:grid;gap:8px;margin-bottom:10px"></div>
                    <div id="platform-problem-bank" class="org-grid"></div>
                    <button id="platform-load-more" class="btn-outline btn-sm" style="display:none;margin-top:10px" onclick="loadMorePlatformProblems()">Load more</button>
                </section>
            </div>
        </div>

        <h3 style="margin:18px 0 12px;color:var(--text-muted);font-size:.82rem;text-transform:uppercase;letter-spacing:.06em">Step 3: Publish Settings</h3>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
            <div class="form-group">
                <label class="form-label">Lifecycle</label>
                <select id="org_status" class="form-input">
                    <option value="scheduled">Scheduled</option>
                    <option value="draft">Draft</option>
                    <option value="live">Live</option>
                    <option value="ended">Ended</option>
                    <option value="archived">Archived</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Published</label>
                <select id="is_published" class="form-input">
                    <option value="1">Published</option>
                    <option value="0">Unpublished</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Visibility</label>
            <select id="visibility" class="form-input">
                <option value="public">Public</option>
                <option value="org">Organization</option>
            </select>
        </div>
        <div style="display:flex;gap:10px;align-items:center">
            <button class="btn-primary" onclick="saveContest()">Save Contest</button>
            <a class="btn-outline" href="/code-arena/organization/contests.php">Back</a>
            <span id="msg" style="color:var(--text-muted)"></span>
        </div>
    </div>
</div>

<?php require_once '../includes/organization_shell_end.php'; ?>
<script src="/code-arena/assets/js/main.js?v=20260615-ui5"></script>
<script>
const CONTEST_ID = <?= $contestId ?>;
let selectedPlatformProblems = new Map();
let platformProblemPage = 1;
let platformProblemPages = 1;
let platformSearchTimer = null;

function toInput(dt) {
    return dt ? dt.replace(' ', 'T').slice(0, 16) : '';
}

function esc(value) {
    return String(value ?? '').replace(/[&<>"']/g, match => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    }[match]));
}

function problemLabel(problem, className, selectedSet) {
    const id = Number(problem.id);
    return `<label class="problem-choice">
        <input type="checkbox" class="${className}" value="${id}" ${selectedSet.has(id) ? 'checked' : ''} ${className === 'platform-problem-choice' ? 'onchange="togglePlatformProblem(this)"' : ''}>
        <span>
            <strong>${esc(problem.title)}</strong><br>
            <span style="color:var(--text-muted)">${esc(problem.difficulty)} - ${esc(problem.tags || 'No tags')}</span>
        </span>
    </label>`;
}

async function loadProblemBank(selected = []) {
    const { ok, data } = await api('/code-arena/api/organization/problems.php');
    const bank = document.getElementById('problem-bank');
    if (!ok || !data.success) {
        bank.innerHTML = '<p style="color:var(--red)">Could not load problem bank.</p>';
        return;
    }
    const selectedSet = new Set(selected.map(Number));
    bank.innerHTML = (data.data.problems || []).map(problem =>
        problemLabel(problem, 'org-problem-choice', selectedSet)
    ).join('') || '<p style="color:var(--text-muted)">No org problems yet. Add a problem or use CodeArena problems.</p>';
}

function renderSelectedPlatformProblems() {
    const box = document.getElementById('selected-platform-problems');
    const rows = [...selectedPlatformProblems.values()];
    box.innerHTML = rows.length ? `
        <div style="color:var(--text-muted);font-size:.78rem;font-weight:800;text-transform:uppercase;letter-spacing:.05em">Selected CodeArena Problems (${rows.length})</div>
        ${rows.map(problem => `<div style="display:flex;justify-content:space-between;gap:10px;align-items:center;padding:8px 10px;border:1px solid var(--border);border-radius:var(--radius-sm);background:rgba(124,58,237,.1)">
            <span><strong>${esc(problem.title)}</strong><br><span style="color:var(--text-muted);font-size:.78rem">${esc(problem.difficulty)}</span></span>
            <button class="btn-outline btn-sm" onclick="removePlatformProblem(${Number(problem.id)})">Remove</button>
        </div>`).join('')}
    ` : '';
}

function togglePlatformProblem(input) {
    const id = Number(input.value);
    const label = input.closest('label');
    const title = label?.querySelector('strong')?.textContent || `Problem #${id}`;
    const meta = label?.querySelector('span span')?.textContent || '';
    const difficulty = meta.split(' - ')[0] || '';
    if (input.checked) {
        selectedPlatformProblems.set(id, { id, title, difficulty });
    } else {
        selectedPlatformProblems.delete(id);
    }
    renderSelectedPlatformProblems();
}

function removePlatformProblem(id) {
    selectedPlatformProblems.delete(Number(id));
    document.querySelectorAll(`.platform-problem-choice[value="${Number(id)}"]`).forEach(input => input.checked = false);
    renderSelectedPlatformProblems();
}

function platformSearchChanged() {
    clearTimeout(platformSearchTimer);
    platformSearchTimer = setTimeout(() => loadPlatformProblems([], true), 350);
}

async function loadPlatformProblems(selected = [], reset = true) {
    const search = document.getElementById('platform-problem-search')?.value.trim() || '';
    if (reset) platformProblemPage = 1;
    const qs = new URLSearchParams({ per_page: '20', page: String(platformProblemPage), sort: 'id', order: 'DESC' });
    if (search) qs.set('search', search);

    const { ok, data } = await api('/code-arena/api/problems/index.php?' + qs.toString());
    const bank = document.getElementById('platform-problem-bank');
    if (!ok || !data.success) {
        bank.innerHTML = '<p style="color:var(--red)">Could not load CodeArena problems.</p>';
        return;
    }
    selected.forEach(problem => {
        if (typeof problem === 'object') {
            selectedPlatformProblems.set(Number(problem.id), {
                id: Number(problem.id),
                title: problem.title || `Problem #${problem.id}`,
                difficulty: problem.difficulty || '',
            });
        } else {
            selectedPlatformProblems.set(Number(problem), {
                id: Number(problem),
                title: `Problem #${problem}`,
                difficulty: '',
            });
        }
    });
    const selectedSet = new Set([...selectedPlatformProblems.keys()]);
    const html = (data.data.problems || []).map(problem =>
        problemLabel(problem, 'platform-problem-choice', selectedSet)
    ).join('');
    bank.innerHTML = reset
        ? (html || '<p style="color:var(--text-muted)">No CodeArena problems found.</p>')
        : bank.innerHTML + html;
    platformProblemPages = Number(data.data.pages || 1);
    document.getElementById('platform-load-more').style.display = platformProblemPage < platformProblemPages ? 'inline-flex' : 'none';
    renderSelectedPlatformProblems();
}

function loadMorePlatformProblems() {
    if (platformProblemPage >= platformProblemPages) return;
    platformProblemPage++;
    loadPlatformProblems([], false);
}

async function loadContest() {
    let selectedOrg = [];
    let selectedPlatform = [];

    if (CONTEST_ID) {
        const { ok, data } = await api(`/code-arena/api/organization/contests.php?contest_id=${CONTEST_ID}`);
        if (!ok || !data.success) {
            toast(data.message || 'Failed', 'error');
            return;
        }
        const contest = data.data.contest;
        title.value = contest.title || '';
        description.value = contest.description || '';
        start_time.value = toInput(contest.start_time);
        end_time.value = toInput(contest.end_time);
        org_status.value = contest.org_status || 'scheduled';
        is_published.value = Number(contest.is_published) ? '1' : '0';
        visibility.value = contest.visibility || 'public';

        selectedOrg = (data.data.problems || []).filter(problem => problem.org_problem_id).map(problem => problem.org_problem_id);
        selectedPlatform = (data.data.problems || []).filter(problem => !problem.org_problem_id).map(problem => ({
            id: problem.problem_id,
            title: problem.title,
            difficulty: problem.difficulty,
        }));
    }

    await Promise.all([
        loadProblemBank(selectedOrg),
        loadPlatformProblems(selectedPlatform),
    ]);
}

async function saveContest() {
    const selectedOrg = [...document.querySelectorAll('.org-problem-choice:checked')].map(el => Number(el.value)).filter(Boolean);
    const selectedPlatform = [...selectedPlatformProblems.keys()].map(Number).filter(Boolean);
    const payload = {
        title: title.value.trim(),
        description: description.value.trim(),
        start_time: start_time.value,
        end_time: end_time.value,
        org_status: org_status.value,
        is_published: is_published.value === '1',
        visibility: visibility.value,
        org_problem_ids: selectedOrg,
        platform_problem_ids: selectedPlatform,
    };
    if (CONTEST_ID) payload.contest_id = CONTEST_ID;

    const { ok, data } = await api('/code-arena/api/organization/contests.php', {
        method: CONTEST_ID ? 'PUT' : 'POST',
        body: JSON.stringify(payload),
    });
    msg.textContent = data.message || '';
    msg.style.color = ok ? 'var(--accent)' : 'var(--red)';
    if (ok) {
        toast(data.message, 'success');
        if (!CONTEST_ID) setTimeout(() => location.href = '/code-arena/organization/contests.php', 700);
    }
}

loadContest();
</script>
</body>
</html>
