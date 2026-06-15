<?php
require_once '../includes/session.php';
require_once '../config/db.php';
require_once '../includes/organization.php';
$organization = requireOrganizationPage($pdo);
$pageTitle = 'Contests';
$activeOrgPage = 'contests';
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Organization Contests - Code Arena</title><link rel="stylesheet" href="/code-arena/assets/css/style.css?v=20260615-ui2"></head><body>
<?php require_once '../includes/organization_shell.php'; ?>
<div class="org-content">
    <div class="org-toolbar">
        <a class="btn-primary" href="/code-arena/organization/create_contest.php">Create Contest</a>
        <button class="btn-outline" onclick="loadContests()">Refresh</button>
    </div>
    <div class="table-wrap org-card"><table><thead><tr><th>ID</th><th>Contest</th><th>Lifecycle</th><th>Published</th><th>Visibility</th><th>Participants</th><th>Submissions</th><th>Window</th><th>Actions</th></tr></thead><tbody id="contest-body"></tbody></table></div>
</div>
<?php require_once '../includes/organization_shell_end.php'; ?>
<script src="/code-arena/assets/js/main.js"></script>
<script>
async function loadContests(){
 const {ok,data}=await api('/code-arena/api/organization/contests.php');
 const body=document.getElementById('contest-body');
 if(!ok||!data.success){body.innerHTML=`<tr><td colspan="9">${data.message||'Failed'}</td></tr>`;return;}
 body.innerHTML=(data.data.contests||[]).map(c=>`<tr>
 <td>${c.id}</td><td><a href="/code-arena/contest.php?id=${c.id}">${esc(c.title)}</a></td><td>${c.org_status}</td><td>${Number(c.is_published)?'Yes':'No'}</td>
 <td>${esc(c.visibility||'public')}</td><td>${c.participant_count||0}</td><td>${c.submission_count||0}</td><td>${c.start_time}<br><span style="color:var(--text-muted)">${c.end_time}</span></td>
 <td><a class="btn-outline btn-sm" href="/code-arena/organization/create_contest.php?id=${c.id}">Edit</a> ${c.org_status==='draft'?`<button class="btn-danger btn-sm" onclick="deleteDraft(${c.id})">Delete</button>`:''}</td>
 </tr>`).join('')||'<tr><td colspan="9">No contests yet.</td></tr>';
}
async function deleteDraft(id){ if(!confirm('Delete this draft contest?'))return; const {ok,data}=await api('/code-arena/api/organization/contests.php',{method:'DELETE',body:JSON.stringify({contest_id:id})}); toast(data.message||'Updated',ok?'success':'error'); if(ok)loadContests(); }
function esc(s){return String(s??'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));}
loadContests();
</script></body></html>
