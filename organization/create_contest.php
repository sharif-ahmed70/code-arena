<?php
require_once '../includes/session.php';
require_once '../config/db.php';
require_once '../includes/organization.php';
$organization = requireOrganizationPage($pdo);
$pageTitle = 'Create Contest';
$activeOrgPage = 'contests';
$contestId = (int)($_GET['id'] ?? 0);
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Create Contest - Code Arena</title><link rel="stylesheet" href="/code-arena/assets/css/style.css"></head><body>
<?php require_once '../includes/organization_shell.php'; ?>
<div class="org-content">
    <div class="org-card" style="max-width:860px">
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
            <div id="problem-bank" class="org-grid"></div>
            <a href="/code-arena/organization/problem_create.php" class="btn-outline btn-sm" style="margin-top:12px">Add Problem</a>
        </div>
        <h3 style="margin:18px 0 12px;color:var(--text-muted);font-size:.82rem;text-transform:uppercase;letter-spacing:.06em">Step 3: Publish Settings</h3>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
            <div class="form-group"><label class="form-label">Lifecycle</label><select id="org_status" class="form-input"><option value="scheduled">Scheduled</option><option value="draft">Draft</option><option value="live">Live</option><option value="ended">Ended</option><option value="archived">Archived</option></select></div>
            <div class="form-group"><label class="form-label">Published</label><select id="is_published" class="form-input"><option value="1">Published</option><option value="0">Unpublished</option></select></div>
        </div>
        <div class="form-group"><label class="form-label">Visibility</label><select id="visibility" class="form-input"><option value="public">Public</option><option value="org">Organization</option></select></div>
        <div style="display:flex;gap:10px;align-items:center"><button class="btn-primary" onclick="saveContest()">Save Contest</button><a class="btn-outline" href="/code-arena/organization/contests.php">Back</a><span id="msg" style="color:var(--text-muted)"></span></div>
    </div>
</div>
<?php require_once '../includes/organization_shell_end.php'; ?>
<script src="/code-arena/assets/js/main.js"></script>
<script>
const CONTEST_ID=<?= $contestId ?>;
function toInput(dt){return dt?dt.replace(' ','T').slice(0,16):''}
function esc(s){return String(s??'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));}
async function loadProblemBank(selected=[]){ const {ok,data}=await api('/code-arena/api/organization/problems.php'); const bank=document.getElementById('problem-bank'); if(!ok||!data.success){bank.innerHTML='<p style="color:var(--red)">Could not load problem bank.</p>';return;} const selectedSet=new Set(selected.map(Number)); bank.innerHTML=(data.data.problems||[]).map(p=>`<label style="display:flex;gap:10px;align-items:flex-start;padding:10px;border:1px solid var(--border);border-radius:var(--radius-sm);background:var(--bg-card)"><input type="checkbox" class="org-problem-choice" value="${p.id}" ${selectedSet.has(Number(p.id))?'checked':''}><span><strong>${esc(p.title)}</strong><br><span style="color:var(--text-muted)">${esc(p.difficulty)} · ${esc(p.tags||'No tags')}</span></span></label>`).join('')||'<p style="color:var(--text-muted)">No problems yet. Add a problem first.</p>'; }
async function loadContest(){ let selected=[]; if(CONTEST_ID){ const {ok,data}=await api(`/code-arena/api/organization/contests.php?contest_id=${CONTEST_ID}`); if(!ok||!data.success){toast(data.message||'Failed','error');return;} const c=data.data.contest; title.value=c.title||''; description.value=c.description||''; start_time.value=toInput(c.start_time); end_time.value=toInput(c.end_time); org_status.value=c.org_status||'scheduled'; is_published.value=Number(c.is_published)?'1':'0'; visibility.value=c.visibility||'public'; selected=(data.data.problems||[]).map(p=>p.org_problem_id).filter(Boolean); } await loadProblemBank(selected); }
async function saveContest(){ const selected=[...document.querySelectorAll('.org-problem-choice:checked')].map(el=>Number(el.value)).filter(Boolean); const payload={title:title.value.trim(),description:description.value.trim(),start_time:start_time.value,end_time:end_time.value,org_status:org_status.value,is_published:is_published.value==='1',visibility:visibility.value,org_problem_ids:selected}; if(CONTEST_ID)payload.contest_id=CONTEST_ID; const {ok,data}=await api('/code-arena/api/organization/contests.php',{method:CONTEST_ID?'PUT':'POST',body:JSON.stringify(payload)}); msg.textContent=data.message||''; msg.style.color=ok?'var(--accent)':'var(--red)'; if(ok){toast(data.message,'success'); if(!CONTEST_ID)setTimeout(()=>location.href='/code-arena/organization/contests.php',700);} }
loadContest();
</script></body></html>
