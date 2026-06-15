<?php
require_once '../includes/session.php';
require_once '../config/db.php';
require_once '../includes/organization.php';
$organization = requireOrganizationPage($pdo);
$pageTitle = 'Participants';
$activeOrgPage = 'participants';
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Participants - Code Arena</title><link rel="stylesheet" href="/code-arena/assets/css/style.css?v=20260615-ui5"></head><body>
<?php require_once '../includes/organization_shell.php'; ?>
<div class="org-content">
    <div class="org-toolbar">
        <input id="contest_id" class="form-input" type="number" min="1" placeholder="Contest ID">
        <select id="status" class="form-input"><option value="">All statuses</option><option value="registered">Registered</option><option value="approved">Approved</option><option value="rejected">Rejected</option><option value="removed">Removed</option><option value="banned">Banned</option></select>
        <button class="btn-primary" onclick="loadParticipants()">Filter</button>
    </div>
    <div class="table-wrap org-card"><table><thead><tr><th>User</th><th>Contest</th><th>Status</th><th>Submissions</th><th>Accepted</th><th>Registered</th><th>Actions</th></tr></thead><tbody id="participant-body"></tbody></table></div>
</div>
<?php require_once '../includes/organization_shell_end.php'; ?>
<script src="/code-arena/assets/js/main.js?v=20260615-ui5"></script>
<script>
function esc(s){return String(s??'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));}
async function loadParticipants(){
 const qs=new URLSearchParams(); if(contest_id.value)qs.set('contest_id',contest_id.value); if(status.value)qs.set('status',status.value);
 const {ok,data}=await api('/code-arena/api/organization/participants.php?'+qs.toString());
 const body=document.getElementById('participant-body');
 if(!ok||!data.success){body.innerHTML=`<tr><td colspan="7">${esc(data.message||'Failed to load participants')}</td></tr>`;return;}
 body.innerHTML=(data.data.participants||[]).map(p=>`<tr>
 <td>${esc(p.name||p.username)}<br><span style="color:var(--text-muted)">${esc(p.email)}</span></td><td>${esc(p.contest_title)}</td><td>${esc(p.status)}</td>
 <td>${p.submission_count||0}</td><td>${p.accepted_count||0}</td><td>${esc(p.registered_at)}</td>
 <td><button class="btn-outline btn-sm" onclick="participantAction(${p.id},'approve')">Approve</button> <button class="btn-outline btn-sm" onclick="participantAction(${p.id},'reject')">Reject</button> <button class="btn-danger btn-sm" onclick="participantAction(${p.id},'ban')">Ban</button> <button class="btn-outline btn-sm" onclick="participantAction(${p.id},'restore')">Restore</button></td>
 </tr>`).join('')||'<tr><td colspan="7">No participants found.</td></tr>';
}
async function participantAction(id,action){const {ok,data}=await api('/code-arena/api/organization/participants.php',{method:'PUT',body:JSON.stringify({participant_id:id,action})}); toast(data.message||'Updated',ok?'success':'error'); if(ok)loadParticipants();}
loadParticipants();
</script></body></html>
