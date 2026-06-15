<?php
require_once '../includes/session.php';
require_once '../config/db.php';
require_once '../includes/organization.php';
$organization = requireOrganizationPage($pdo);
$pageTitle = 'Settings';
$activeOrgPage = 'settings';
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Organization Settings - Code Arena</title><link rel="stylesheet" href="/code-arena/assets/css/style.css?v=20260615-ui2"></head><body>
<?php require_once '../includes/organization_shell.php'; ?>
<div class="org-content">
    <section class="org-card" style="max-width:760px">
        <h2 style="margin-bottom:16px">Organization Profile</h2>
        <div class="form-group"><label class="form-label">Name</label><input id="name" class="form-input"></div>
        <div class="form-group"><label class="form-label">Description</label><textarea id="description" class="form-input" rows="5"></textarea></div>
        <div class="form-group"><label class="form-label">Logo URL</label><input id="logo" class="form-input"></div>
        <div style="display:flex;gap:10px;align-items:center"><button class="btn-primary" onclick="saveSettings()">Save Settings</button><span id="msg" style="color:var(--text-muted)"></span></div>
    </section>
</div>
<?php require_once '../includes/organization_shell_end.php'; ?>
<script src="/code-arena/assets/js/main.js"></script>
<script>
async function loadSettings(){const {ok,data}=await api('/code-arena/api/organization/settings.php'); if(!ok||!data.success){toast(data.message||'Failed','error');return;} const org=data.data.organization||{}; name.value=org.name||''; description.value=org.description||''; logo.value=org.logo||'';}
async function saveSettings(){const payload={name:name.value.trim(),description:description.value.trim(),logo:logo.value.trim()}; const {ok,data}=await api('/code-arena/api/organization/settings.php',{method:'PUT',body:JSON.stringify(payload)}); msg.textContent=data.message||''; msg.style.color=ok?'var(--accent)':'var(--red)'; toast(data.message||'Saved',ok?'success':'error');}
loadSettings();
</script></body></html>
