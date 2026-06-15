<?php
require_once '../includes/session.php';
require_once '../config/db.php';
require_once '../includes/organization.php';
$organization = requireOrganizationPage($pdo);
$pageTitle = 'Problem Bank';
$activeOrgPage = 'problems';
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Problem Bank - Code Arena</title><link rel="stylesheet" href="/code-arena/assets/css/style.css"><style>.difficulty-badge{padding:4px 9px;border-radius:999px;font-size:.75rem}.difficulty-Easy{background:rgba(0,232,122,.1);color:var(--accent)}.difficulty-Medium{background:rgba(255,209,102,.1);color:var(--yellow)}.difficulty-Hard{background:rgba(255,79,79,.1);color:var(--red)}</style></head><body>
<?php require_once '../includes/organization_shell.php'; ?>
<div class="org-content">
    <div class="org-toolbar">
        <a class="btn-primary" href="/code-arena/organization/problem_create.php">Add Problem</a>
        <select id="difficulty" class="form-input"><option value="">All difficulties</option><option>Easy</option><option>Medium</option><option>Hard</option></select>
        <input id="tag" class="form-input" placeholder="Filter by tag">
        <button class="btn-outline" onclick="loadProblems()">Filter</button>
    </div>
    <div class="table-wrap org-card"><table><thead><tr><th>ID</th><th>Problem</th><th>Difficulty</th><th>Tags</th><th>Used In</th><th>Created</th><th>Actions</th></tr></thead><tbody id="problem-body"></tbody></table></div>
</div>
<?php require_once '../includes/organization_shell_end.php'; ?>
<script src="/code-arena/assets/js/main.js"></script>
<script>
function esc(s){return String(s??'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));}
async function loadProblems(){
 const qs=new URLSearchParams(); if(difficulty.value)qs.set('difficulty',difficulty.value); if(tag.value.trim())qs.set('tag',tag.value.trim());
 const {ok,data}=await api('/code-arena/api/organization/problems.php?'+qs.toString());
 const body=document.getElementById('problem-body');
 if(!ok||!data.success){body.innerHTML=`<tr><td colspan="7">${esc(data.message||'Failed')}</td></tr>`;return;}
 body.innerHTML=(data.data.problems||[]).map(p=>`<tr>
 <td>${p.id}</td><td><strong>${esc(p.title)}</strong><br><span style="color:var(--text-muted)">Platform #${p.platform_problem_id||'-'}</span></td>
 <td><span class="difficulty-badge difficulty-${esc(p.difficulty)}">${esc(p.difficulty)}</span></td><td>${esc(p.tags||'-')}</td><td>${p.contest_count||0} contests</td><td>${esc(p.created_at)}</td>
 <td><a class="btn-outline btn-sm" href="/code-arena/organization/problem_edit.php?id=${p.id}">Edit</a> <button class="btn-danger btn-sm" onclick="deleteProblem(${p.id})">Delete</button></td>
 </tr>`).join('')||'<tr><td colspan="7">No organization problems yet.</td></tr>';
}
async function deleteProblem(id){if(!confirm('Delete this problem? Draft-only contest links can be recreated later.'))return; const {ok,data}=await api('/code-arena/api/organization/problems.php',{method:'DELETE',body:JSON.stringify({id})}); toast(data.message||'Deleted',ok?'success':'error'); if(ok)loadProblems();}
loadProblems();
</script></body></html>
