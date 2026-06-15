<?php
require_once '../includes/session.php';
require_once '../config/db.php';
require_once '../includes/organization.php';
$organization = requireOrganizationPage($pdo);
$pageTitle = 'Submissions';
$activeOrgPage = 'submissions';
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Submissions - Code Arena</title><link rel="stylesheet" href="/code-arena/assets/css/style.css"></head><body>
<?php require_once '../includes/organization_shell.php'; ?>
<div class="org-content">
    <div id="suspicious" class="org-card" style="display:none;margin-bottom:16px"></div>
    <div class="org-toolbar">
        <input id="contest_id" class="form-input" type="number" min="1" placeholder="Contest ID">
        <input id="user" class="form-input" placeholder="User">
        <input id="problem" class="form-input" placeholder="Problem">
        <select id="status" class="form-input"><option value="">All verdicts</option><option>Accepted</option><option>Wrong Answer</option><option>Runtime Error</option><option>Time Limit Exceeded</option><option>Compilation Error</option><option>Pending</option></select>
        <button class="btn-primary" onclick="loadSubmissions()">Filter</button>
    </div>
    <div class="table-wrap org-card"><table><thead><tr><th>ID</th><th>User</th><th>Problem</th><th>Contest</th><th>Verdict</th><th>Score</th><th>Runtime</th><th>Hints</th><th>Submitted</th></tr></thead><tbody id="submission-body"></tbody></table></div>
</div>
<?php require_once '../includes/organization_shell_end.php'; ?>
<script src="/code-arena/assets/js/main.js"></script>
<script>
function esc(s){return String(s??'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));}
async function loadSubmissions(){
 const qs=new URLSearchParams(); ['contest_id','user','problem','status'].forEach(id=>{const el=document.getElementById(id); if(el.value)qs.set(id,el.value);});
 const {ok,data}=await api('/code-arena/api/organization/submissions.php?'+qs.toString());
 const body=document.getElementById('submission-body'), warn=document.getElementById('suspicious');
 if(!ok||!data.success){body.innerHTML=`<tr><td colspan="9">${esc(data.message||'Failed to load submissions')}</td></tr>`;return;}
 const suspicious=data.data.suspicious_activity||[];
 warn.style.display=suspicious.length?'block':'none';
 warn.innerHTML=suspicious.length?`<strong>Suspicious activity</strong><p style="margin-top:8px;color:var(--text-muted)">${suspicious.map(x=>`User #${x.user_id} submitted ${x.submission_count} times in contest #${x.contest_id}`).join('<br>')}</p>`:'';
 body.innerHTML=(data.data.submissions||[]).map(s=>`<tr><td>${s.id}</td><td>${esc(s.username)}<br><span style="color:var(--text-muted)">${esc(s.email)}</span></td><td>${esc(s.problem_title)}</td><td>${esc(s.contest_title)}</td><td>${esc(s.status)}</td><td>${s.score||0}</td><td>${s.runtime_ms||0} ms</td><td>${s.hints_used||0}</td><td>${esc(s.submitted_at)}</td></tr>`).join('')||'<tr><td colspan="9">No submissions found.</td></tr>';
}
loadSubmissions();
</script></body></html>
