<?php
// ============================================================
//  CODE ARENA — Instructor Page (Problem Manager)
// ============================================================
require_once 'includes/session.php';
requireInstructor();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor — Code Arena</title>
    <link rel="stylesheet" href="/code-arena/assets/css/style.css">
    <style>
        .form-grid  { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
        @media(max-width:700px) { .form-grid { grid-template-columns:1fr; } }
        .tc-list    { display:flex; flex-direction:column; gap:8px; margin-bottom:12px; }
        .tc-item    { display:grid; grid-template-columns:1fr 1fr auto; gap:8px; align-items:start; }
        .tc-item textarea { font-family:'JetBrains Mono',monospace; font-size:.82rem; resize:vertical; min-height:52px; }
        .btn-sm     { padding:6px 12px; font-size:.82rem; }
        .split-layout { display:grid; grid-template-columns:420px 1fr; gap:32px; }
        @media(max-width:900px) { .split-layout { grid-template-columns:1fr; } }
    </style>
</head>
<body>
<?php require_once 'includes/navbar.php'; ?>

<div class="page">
<div class="container">
    <div class="page-header fade-up">
        <h1>Problem Manager</h1>
        <p>Create and manage problems for the platform.</p>
    </div>

    <div class="split-layout">
        <!-- Form -->
        <div class="card fade-up fade-up-1">
            <h3 id="form-title" style="margin-bottom:20px">New Problem</h3>
            <input type="hidden" id="problem-id">

            <div class="form-group">
                <label class="form-label">Title *</label>
                <input type="text" id="p-title" class="form-input" placeholder="Two Sum">
            </div>
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Difficulty *</label>
                    <select id="p-difficulty" class="form-input">
                        <option>Easy</option><option>Medium</option><option>Hard</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Tags</label>
                    <input type="text" id="p-tags" class="form-input" placeholder="array,hash-table">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Description *</label>
                <textarea id="p-desc" class="form-input" rows="5" placeholder="Problem statement…"></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Examples</label>
                <textarea id="p-examples" class="form-input" rows="3"
                    placeholder='[{"input":"1 2","output":"3","explanation":"1+2=3"}]'></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Constraints</label>
                <textarea id="p-constraints" class="form-input" rows="2" placeholder="1 ≤ n ≤ 10^5"></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Roadmap Day (1-30, optional)</label>
                <input type="number" id="p-roadmap-day" class="form-input" placeholder="Leave blank if not on roadmap" min="1" max="30">
            </div>
            <div class="form-group">
                <label class="form-label">Hint Tier 1</label>
                <textarea id="p-hint1" class="form-input" rows="2"></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Hint Tier 2</label>
                <textarea id="p-hint2" class="form-input" rows="2"></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Hint Tier 3</label>
                <textarea id="p-hint3" class="form-input" rows="2"></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Test Cases</label>
                <div class="tc-list" id="tc-list"></div>
                <button class="btn-outline btn-sm" onclick="addTestCase()">+ Add Test Case</button>
            </div>

            <div style="display:flex; gap:12px; align-items:center; margin-top:12px">
                <button class="btn-primary" onclick="saveProblem()">Save Problem</button>
                <button class="btn-outline" onclick="resetForm()">Reset</button>
                <span id="form-msg" style="font-size:.88rem"></span>
            </div>
        </div>

        <!-- Problem List -->
        <div class="fade-up fade-up-2">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px">
                <h3>My Problems</h3>
                <button class="btn-outline btn-sm" onclick="loadProblems()">↺ Refresh</button>
            </div>
            <div class="table-wrap">
                <table>
                    <thead><tr>
                        <th>Title</th><th>Diff</th><th>Subs</th><th>Public</th><th>Actions</th>
                    </tr></thead>
                    <tbody id="problems-body">
                        <tr><td colspan="5" style="text-align:center;padding:30px;color:var(--text-muted)">Loading…</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
</div>

<script src="/code-arena/assets/js/main.js"></script>
<script>
let tcCount = 0;

function addTestCase(input = '', expected = '') {
    tcCount++;
    const div = document.createElement('div');
    div.className = 'tc-item';
    div.innerHTML = `
        <div>
            <div class="form-label" style="margin-bottom:4px">Input ${tcCount}</div>
            <textarea class="form-input tc-input" placeholder="stdin">${input}</textarea>
        </div>
        <div>
            <div class="form-label" style="margin-bottom:4px">Expected ${tcCount}</div>
            <textarea class="form-input tc-output" placeholder="stdout">${expected}</textarea>
        </div>
        <button class="btn-danger btn-sm" style="margin-top:22px" onclick="this.closest('.tc-item').remove()">×</button>`;
    document.getElementById('tc-list').appendChild(div);
}

function resetForm() {
    document.getElementById('problem-id').value = '';
    document.getElementById('form-title').textContent = 'New Problem';
    ['p-title','p-tags','p-roadmap-day','p-hint1','p-hint2','p-hint3',
     'p-examples','p-constraints','p-desc'].forEach(id => {
        document.getElementById(id).value = '';
    });
    document.getElementById('p-difficulty').value = 'Easy';
    document.getElementById('tc-list').innerHTML = '';
    tcCount = 0;
    addTestCase();
    document.getElementById('form-msg').textContent = '';
}

async function saveProblem() {
    const pid = document.getElementById('problem-id').value;
    const testCases = [];
    document.querySelectorAll('#tc-list .tc-item').forEach(item => {
        const inp = item.querySelector('.tc-input').value.trim();
        const out = item.querySelector('.tc-output').value.trim();
        if (inp !== '' || out !== '') testCases.push({ input: inp, expected_output: out });
    });

    const payload = {
        title:       document.getElementById('p-title').value.trim(),
        difficulty:  document.getElementById('p-difficulty').value,
        description: document.getElementById('p-desc').value.trim(),
        examples:    document.getElementById('p-examples').value.trim(),
        constraints: document.getElementById('p-constraints').value.trim(),
        tags:        document.getElementById('p-tags').value.trim(),
        roadmap_day: document.getElementById('p-roadmap-day').value || null,
        hint_tier1:  document.getElementById('p-hint1').value.trim() || null,
        hint_tier2:  document.getElementById('p-hint2').value.trim() || null,
        hint_tier3:  document.getElementById('p-hint3').value.trim() || null,
        test_cases:  testCases,
        is_public:   1,
    };
    if (pid) payload.id = parseInt(pid);

    const msg = document.getElementById('form-msg');
    msg.textContent = 'Saving…';
    msg.style.color = 'var(--text-muted)';

    const { ok, data } = await api('/code-arena/api/instructor/problems.php', {
        method: pid ? 'PUT' : 'POST',
        body: JSON.stringify(payload),
    });

    if (ok && data.success) {
        msg.textContent = data.message;
        msg.style.color = 'var(--accent)';
        toast(data.message, 'success');
        loadProblems();
        if (!pid) resetForm();
    } else {
        msg.textContent = data.message || 'Failed';
        msg.style.color = 'var(--red)';
        toast(data.message || 'Failed', 'error');
    }
}

async function loadProblems() {
    const { ok, data } = await api('/code-arena/api/instructor/problems.php');
    const tbody = document.getElementById('problems-body');
    if (!ok || !data.success) {
        tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;color:var(--red);padding:30px">${data.message}</td></tr>`;
        return;
    }
    const problems = data.data;
    if (!problems.length) {
        tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;color:var(--text-muted);padding:30px">No problems yet.</td></tr>`;
        return;
    }
    tbody.innerHTML = problems.map(p => `
        <tr>
            <td><a href="/code-arena/problem.php?slug=${p.slug}" target="_blank">${p.title}</a></td>
            <td>${difficultyBadge(p.difficulty)}</td>
            <td>${p.total_submissions}</td>
            <td>${p.is_public ? '<span style="color:var(--accent)">Yes</span>' : '<span style="color:var(--text-muted)">No</span>'}</td>
            <td>
                <button class="btn-outline btn-sm" onclick="editProblem(${p.id})">Edit</button>
                <a class="btn-outline btn-sm" href="/code-arena/editorial_manage.php?problem_id=${p.id}">Editorial</a>
                <button class="btn-danger btn-sm" onclick="deleteProblem(${p.id},'${p.title.replace(/'/g,'')}')">Del</button>
            </td>
        </tr>`).join('');
}

function editProblem(id) {
    toast('Edit: load full problem data from DB and prefill form. Use the problem ID to fetch via PUT.', 'info');
    document.getElementById('problem-id').value = id;
    document.getElementById('form-title').textContent = `Edit Problem #${id}`;
    document.getElementById('problem-form-wrap')?.scrollIntoView({ behavior:'smooth' });
}

async function deleteProblem(id, title) {
    if (!confirm(`Delete "${title}"?`)) return;
    const { ok, data } = await api('/code-arena/api/instructor/problems.php', {
        method: 'DELETE', body: JSON.stringify({ id }),
    });
    toast(data.message, ok ? 'success' : 'error');
    if (ok) loadProblems();
}

resetForm();
loadProblems();
</script>
</body>
</html>
