<?php
// ============================================================
//  CODE ARENA - Contest Manager
// ============================================================
require_once 'includes/session.php';
requireInstructor();

$contestId = (int)($_GET['id'] ?? 0);
if (!$contestId) { header('Location: /code-arena/contests.php'); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Contest - Code Arena</title>
    <link rel="stylesheet" href="/code-arena/assets/css/style.css">
    <style>
        .manager-grid { display:grid; grid-template-columns: 380px 1fr; gap:24px; align-items:start; }
        @media(max-width:900px){ .manager-grid { grid-template-columns:1fr; } }
        .form-row { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
        @media(max-width:600px){ .form-row { grid-template-columns:1fr; } }
        .muted-help { color:var(--text-muted); font-size:.78rem; line-height:1.55; margin-top:6px; }
        .inline-actions { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
        .mini-input { width:82px; padding:6px 8px; background:var(--bg); border:1px solid var(--border);
                      border-radius:var(--radius-sm); color:var(--text); }
        .danger-link { color:var(--red); border-color:rgba(255,96,96,.35); }
    </style>
</head>
<body>
<?php require_once 'includes/navbar.php'; ?>

<div class="page">
<div class="container">
    <div class="page-header fade-up" style="display:flex;justify-content:space-between;align-items:flex-end;gap:16px">
        <div>
            <h1>Contest Manager</h1>
            <p>Edit contest setup, problems, points, order, and participants.</p>
        </div>
        <a class="btn-outline" href="/code-arena/contest.php?id=<?= $contestId ?>">View Contest</a>
    </div>

    <div class="manager-grid">
        <div>
            <div class="card fade-up fade-up-1">
                <h3 style="margin-bottom:18px">Contest Details</h3>
                <div class="form-group">
                    <label class="form-label">Title</label>
                    <input id="ct-title" class="form-input">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Start</label>
                        <input id="ct-start" type="datetime-local" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">End</label>
                        <input id="ct-end" type="datetime-local" class="form-input">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea id="ct-desc" class="form-input" rows="4"></textarea>
                </div>
                <label style="display:flex;gap:8px;align-items:center;margin-bottom:16px;color:var(--text-dim);font-size:.88rem">
                    <input id="ct-rated" type="checkbox" checked style="accent-color:var(--accent)">
                    Rated contest
                </label>
                <div class="inline-actions">
                    <button class="btn-primary" onclick="saveContest()">Save Details</button>
                    <span id="details-msg" style="font-size:.84rem;color:var(--text-muted)"></span>
                </div>
            </div>

            <div class="card fade-up fade-up-2" style="margin-top:18px">
                <h3 style="margin-bottom:18px">Add Problem</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Problem ID</label>
                        <input id="add-problem-id" type="number" class="form-input" placeholder="1">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Points</label>
                        <input id="add-points" type="number" class="form-input" value="100" min="1">
                    </div>
                </div>
                <button class="btn-outline" onclick="addProblem()">Add Problem</button>
                <p class="muted-help">Tip: open Problems list to copy IDs. Duplicate problems are rejected.</p>
            </div>
        </div>

        <div>
            <div class="card fade-up fade-up-1" style="padding:0;overflow:hidden">
                <div style="padding:18px 20px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center">
                    <h3>Contest Problems</h3>
                    <button class="btn-outline" onclick="loadManager()">Refresh</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Order</th><th>ID</th><th>Problem</th><th>Points</th><th>Action</th></tr></thead>
                        <tbody id="problem-body">
                            <tr><td colspan="5" style="text-align:center;padding:30px;color:var(--text-muted)">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card fade-up fade-up-2" style="padding:0;overflow:hidden;margin-top:18px">
                <div style="padding:18px 20px;border-bottom:1px solid var(--border)">
                    <h3>Participants</h3>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>User</th><th>Email</th><th>Registered</th><th>Action</th></tr></thead>
                        <tbody id="participant-body">
                            <tr><td colspan="4" style="text-align:center;padding:30px;color:var(--text-muted)">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<script src="/code-arena/assets/js/main.js"></script>
<script>
const CONTEST_ID = <?= $contestId ?>;

function toLocalInput(dt) {
    return dt ? dt.replace(' ', 'T').slice(0, 16) : '';
}

async function loadManager() {
    const { ok, data } = await api(`/code-arena/api/contests/manage.php?contest_id=${CONTEST_ID}`);
    if (!ok || !data.success) {
        toast(data.message || 'Failed to load contest manager', 'error');
        return;
    }

    const { contest, problems, participants } = data.data;
    document.getElementById('ct-title').value = contest.title || '';
    document.getElementById('ct-start').value = toLocalInput(contest.start_time);
    document.getElementById('ct-end').value = toLocalInput(contest.end_time);
    document.getElementById('ct-desc').value = contest.description || '';
    document.getElementById('ct-rated').checked = Number(contest.is_rated) === 1;

    renderProblems(problems);
    renderParticipants(participants);
}

function renderProblems(problems) {
    const body = document.getElementById('problem-body');
    if (!problems.length) {
        body.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:30px;color:var(--text-muted)">No problems added yet.</td></tr>';
        return;
    }

    body.innerHTML = problems.map(p => `
        <tr>
            <td><input class="mini-input" id="order-${p.contest_problem_id}" type="number" min="1" value="${p.order_index}"></td>
            <td>${p.problem_id}</td>
            <td>
                <a href="/code-arena/problem.php?slug=${p.slug}" style="color:var(--text);font-weight:600">${escHtml(p.title)}</a>
                <div style="font-size:.78rem;color:var(--text-muted)">${p.difficulty}</div>
            </td>
            <td><input class="mini-input" id="points-${p.contest_problem_id}" type="number" min="1" value="${p.points}"></td>
            <td>
                <div class="inline-actions">
                    <button class="btn-outline" onclick="updateProblem(${p.contest_problem_id})">Save</button>
                    <button class="btn-outline danger-link" onclick="removeProblem(${p.contest_problem_id})">Remove</button>
                </div>
            </td>
        </tr>
    `).join('');
}

function renderParticipants(participants) {
    const body = document.getElementById('participant-body');
    if (!participants.length) {
        body.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:30px;color:var(--text-muted)">No participants yet.</td></tr>';
        return;
    }

    body.innerHTML = participants.map(p => `
        <tr>
            <td><a href="/code-arena/profile.php?id=${p.user_id}" style="color:var(--text);font-weight:600">${escHtml(p.username)}</a></td>
            <td>${escHtml(p.email)}</td>
            <td>${p.registered_at}</td>
            <td><button class="btn-outline danger-link" onclick="removeParticipant(${p.participant_id})">Remove</button></td>
        </tr>
    `).join('');
}

async function saveContest() {
    const payload = {
        action: 'update_contest',
        contest_id: CONTEST_ID,
        title: document.getElementById('ct-title').value.trim(),
        start_time: document.getElementById('ct-start').value,
        end_time: document.getElementById('ct-end').value,
        description: document.getElementById('ct-desc').value.trim(),
        is_rated: document.getElementById('ct-rated').checked ? 1 : 0,
    };
    const { ok, data } = await postManage(payload);
    document.getElementById('details-msg').textContent = ok && data.success ? 'Saved' : (data.message || 'Failed');
    if (ok && data.success) toast('Contest details saved', 'success');
}

async function addProblem() {
    const payload = {
        action: 'add_problem',
        contest_id: CONTEST_ID,
        problem_id: Number(document.getElementById('add-problem-id').value),
        points: Number(document.getElementById('add-points').value || 100),
    };
    const { ok, data } = await postManage(payload);
    if (ok && data.success) {
        toast('Problem added', 'success');
        document.getElementById('add-problem-id').value = '';
        loadManager();
    } else toast(data.message || 'Failed to add problem', 'error');
}

async function updateProblem(contestProblemId) {
    const payload = {
        action: 'update_problem',
        contest_id: CONTEST_ID,
        contest_problem_id: contestProblemId,
        points: Number(document.getElementById(`points-${contestProblemId}`).value),
        order_index: Number(document.getElementById(`order-${contestProblemId}`).value),
    };
    const { ok, data } = await postManage(payload);
    if (ok && data.success) {
        toast('Problem updated', 'success');
        loadManager();
    } else toast(data.message || 'Failed to update problem', 'error');
}

async function removeProblem(contestProblemId) {
    if (!confirm('Remove this problem from the contest?')) return;
    const { ok, data } = await postManage({ action:'remove_problem', contest_id:CONTEST_ID, contest_problem_id:contestProblemId });
    if (ok && data.success) {
        toast('Problem removed', 'success');
        loadManager();
    } else toast(data.message || 'Failed to remove problem', 'error');
}

async function removeParticipant(participantId) {
    if (!confirm('Remove this participant?')) return;
    const { ok, data } = await postManage({ action:'remove_participant', contest_id:CONTEST_ID, participant_id:participantId });
    if (ok && data.success) {
        toast('Participant removed', 'success');
        loadManager();
    } else toast(data.message || 'Failed to remove participant', 'error');
}

function postManage(payload) {
    return api('/code-arena/api/contests/manage.php', {
        method: 'POST',
        body: JSON.stringify(payload),
    });
}

function escHtml(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}

loadManager();
</script>
</body>
</html>
