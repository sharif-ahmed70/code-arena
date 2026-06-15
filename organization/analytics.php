<?php
require_once '../includes/session.php';
require_once '../config/db.php';
require_once '../includes/organization.php';
$organization = requireOrganizationPage($pdo);
$pageTitle = 'Analytics';
$activeOrgPage = 'analytics';
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Analytics - Code Arena</title><link rel="stylesheet" href="/code-arena/assets/css/style.css?v=20260615-ui5"><style>.metric-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:16px;margin-bottom:18px}.metric-card strong{display:block;font-size:1.8rem}.bar{height:10px;background:rgba(255,255,255,.08);border-radius:999px;overflow:hidden}.bar span{display:block;height:100%;background:var(--accent)}</style></head><body>
<?php require_once '../includes/organization_shell.php'; ?>
<div class="org-content">
    <div id="summary" class="metric-grid"></div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:18px">
        <section class="org-card"><h2>Difficulty Analysis</h2><div id="difficulty" class="org-grid" style="margin-top:14px"></div></section>
        <section class="org-card"><h2>Most Solved Problems</h2><div id="solved" class="org-grid" style="margin-top:14px"></div></section>
        <section class="org-card"><h2>Engagement</h2><div id="engagement" class="org-grid" style="margin-top:14px"></div></section>
    </div>
</div>
<?php require_once '../includes/organization_shell_end.php'; ?>
<script src="/code-arena/assets/js/main.js?v=20260615-ui5"></script>
<script>
function esc(s){return String(s??'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));}
function metric(label,value){return `<div class="org-card metric-card"><span style="color:var(--text-muted)">${label}</span><strong>${value||0}</strong></div>`}
async function loadAnalytics(){const {ok,data}=await api('/code-arena/api/organization/analytics.php'); if(!ok||!data.success){toast(data.message||'Failed','error');return;} const d=data.data,s=d.summary||{},accepted=Number(s.accepted||0),subs=Number(s.submissions||0); summary.innerHTML=metric('Contests',s.contests)+metric('Participants',s.participants)+metric('Submissions',subs)+metric('Accepted',accepted)+metric('Success Rate',subs?Math.round(accepted*100/subs)+'%':'0%');
difficulty.innerHTML=(d.difficulty||[]).map(x=>{const total=Number(x.submissions||0), acc=Number(x.accepted||0), pct=total?Math.round(acc*100/total):0;return `<div><div style="display:flex;justify-content:space-between"><span>${esc(x.difficulty)}</span><span>${pct}% accepted</span></div><div class="bar"><span style="width:${pct}%"></span></div></div>`}).join('')||'No difficulty data yet.';
solved.innerHTML=(d.most_solved_problems||[]).map(x=>`<div style="display:flex;justify-content:space-between;gap:12px"><span>${esc(x.title)} <small style="color:var(--text-muted)">${esc(x.difficulty)}</small></span><strong>${x.accepted_count}</strong></div>`).join('')||'No accepted submissions yet.';
engagement.innerHTML=(d.engagement||[]).map(x=>`<div style="display:flex;justify-content:space-between;gap:12px"><span>${esc(x.day)}</span><span>${x.submissions} submissions · ${x.users} users</span></div>`).join('')||'No recent activity.';}
loadAnalytics();
</script></body></html>
