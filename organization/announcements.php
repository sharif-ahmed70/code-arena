<?php
require_once '../includes/session.php';
require_once '../config/db.php';
require_once '../includes/organization.php';
$organization = requireOrganizationPage($pdo);
$pageTitle = 'Announcements';
$activeOrgPage = 'announcements';
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Announcements - Code Arena</title><link rel="stylesheet" href="/code-arena/assets/css/style.css?v=20260615-ui2"></head><body>
<?php require_once '../includes/organization_shell.php'; ?>
<div class="org-content" style="display:grid;grid-template-columns:minmax(280px,420px) minmax(0,1fr);gap:18px">
    <section class="org-card">
        <h2 style="margin-bottom:16px">New Post</h2>
        <div class="form-group"><label class="form-label">Title</label><input id="title" class="form-input"></div>
        <div class="form-group"><label class="form-label">Contest ID</label><input id="contest_id" type="number" min="1" class="form-input" placeholder="Optional"></div>
        <div class="form-group"><label class="form-label">Type</label><select id="type" class="form-input"><option value="announcement">Announcement</option><option value="clarification">Clarification</option><option value="instruction">Instruction</option></select></div>
        <div class="form-group"><label class="form-label">Message</label><textarea id="message" class="form-input" rows="7"></textarea></div>
        <button class="btn-primary" onclick="createAnnouncement()">Publish</button>
    </section>
    <section class="org-card">
        <div class="org-toolbar"><input id="filter_contest" class="form-input" type="number" min="1" placeholder="Filter contest ID"><button class="btn-outline" onclick="loadAnnouncements()">Refresh</button></div>
        <div id="announcement-list" class="org-grid"></div>
    </section>
</div>
<?php require_once '../includes/organization_shell_end.php'; ?>
<script src="/code-arena/assets/js/main.js"></script>
<script>
function esc(s){return String(s??'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));}
async function loadAnnouncements(){const qs=new URLSearchParams(); if(filter_contest.value)qs.set('contest_id',filter_contest.value); const {ok,data}=await api('/code-arena/api/organization/announcements.php?'+qs.toString()); const list=document.getElementById('announcement-list'); if(!ok||!data.success){list.innerHTML=esc(data.message||'Failed');return;} list.innerHTML=(data.data.announcements||[]).map(a=>`<article class="org-card"><div style="display:flex;justify-content:space-between;gap:12px"><div><strong>${esc(a.title)}</strong><div style="color:var(--text-muted);font-size:.85rem">${esc(a.type)}${a.contest_title?' · '+esc(a.contest_title):''} · ${esc(a.created_at)}</div></div><button class="btn-danger btn-sm" onclick="deleteAnnouncement(${a.id})">Delete</button></div><p style="margin-top:12px;white-space:pre-wrap">${esc(a.message)}</p></article>`).join('')||'No announcements yet.';}
async function createAnnouncement(){const payload={title:title.value.trim(),message:message.value.trim(),type:type.value,contest_id:contest_id.value?Number(contest_id.value):null,is_published:true}; const {ok,data}=await api('/code-arena/api/organization/announcements.php',{method:'POST',body:JSON.stringify(payload)}); toast(data.message||'Saved',ok?'success':'error'); if(ok){title.value='';message.value='';contest_id.value='';loadAnnouncements();}}
async function deleteAnnouncement(id){if(!confirm('Delete this announcement?'))return; const {ok,data}=await api('/code-arena/api/organization/announcements.php',{method:'DELETE',body:JSON.stringify({id})}); toast(data.message||'Deleted',ok?'success':'error'); if(ok)loadAnnouncements();}
loadAnnouncements();
</script></body></html>
