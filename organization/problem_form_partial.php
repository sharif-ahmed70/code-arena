<section class="org-card" style="max-width:980px">
    <h2 style="margin-bottom:16px"><?= ($problemFormMode ?? 'create') === 'edit' ? 'Edit Problem' : 'Add Problem' ?></h2>
    <div style="display:grid;grid-template-columns:2fr 1fr;gap:14px">
        <div class="form-group"><label class="form-label">Title</label><input id="title" class="form-input"></div>
        <div class="form-group"><label class="form-label">Difficulty</label><select id="difficulty" class="form-input"><option>Easy</option><option>Medium</option><option>Hard</option></select></div>
    </div>
    <div class="form-group"><label class="form-label">Tags</label><input id="tags" class="form-input" placeholder="dp, graphs, implementation"></div>
    <div class="form-group"><label class="form-label">Description</label><textarea id="description" class="form-input" rows="7"></textarea></div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
        <div class="form-group"><label class="form-label">Input Format</label><textarea id="input_format" class="form-input" rows="4"></textarea></div>
        <div class="form-group"><label class="form-label">Output Format</label><textarea id="output_format" class="form-input" rows="4"></textarea></div>
    </div>
    <div class="form-group"><label class="form-label">Constraints</label><textarea id="constraints" class="form-input" rows="3"></textarea></div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
        <div class="form-group"><label class="form-label">Sample Input</label><textarea id="sample_input" class="form-input" rows="4"></textarea></div>
        <div class="form-group"><label class="form-label">Sample Output</label><textarea id="sample_output" class="form-input" rows="4"></textarea></div>
    </div>
    <div class="form-group"><label class="form-label">Test Cases JSON</label><textarea id="test_cases" class="form-input" rows="7" spellcheck="false">[]</textarea></div>
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px">
        <div class="form-group"><label class="form-label">Hint 1</label><textarea id="hint_tier1" class="form-input" rows="3"></textarea></div>
        <div class="form-group"><label class="form-label">Hint 2</label><textarea id="hint_tier2" class="form-input" rows="3"></textarea></div>
        <div class="form-group"><label class="form-label">Hint 3</label><textarea id="hint_tier3" class="form-input" rows="3"></textarea></div>
    </div>
    <div class="form-group"><label class="form-label">Time Limit (ms)</label><input id="time_limit_ms" class="form-input" type="number" min="500" max="10000" value="2000"></div>
    <div style="display:flex;gap:10px;align-items:center"><button class="btn-primary" onclick="saveProblem()">Save Problem</button><a class="btn-outline" href="/code-arena/organization/problems.php">Back</a><span id="msg" style="color:var(--text-muted)"></span></div>
</section>
<script src="/code-arena/assets/js/main.js?v=20260615-ui5"></script>
<script>
const PROBLEM_ID=<?= (int)($problemId ?? 0) ?>;
const MODE='<?= htmlspecialchars($problemFormMode ?? 'create') ?>';
const fields=['title','difficulty','tags','description','input_format','output_format','constraints','sample_input','sample_output','test_cases','hint_tier1','hint_tier2','hint_tier3','time_limit_ms'];
async function loadProblem(){if(MODE!=='edit')return; const {ok,data}=await api(`/code-arena/api/organization/problems.php?id=${PROBLEM_ID}`); if(!ok||!data.success){toast(data.message||'Failed','error');return;} const p=data.data.problem; fields.forEach(id=>{const el=document.getElementById(id); if(el)el.value=p[id]??(id==='test_cases'?'[]':'');});}
async function saveProblem(){
 let payload={}; fields.forEach(id=>payload[id]=document.getElementById(id).value);
 if(MODE==='edit')payload.id=PROBLEM_ID;
 try{JSON.parse(payload.test_cases||'[]');}catch(e){toast('Test cases must be valid JSON','error');return;}
 const {ok,data}=await api('/code-arena/api/organization/problems.php',{method:MODE==='edit'?'PUT':'POST',body:JSON.stringify(payload)});
 msg.textContent=data.message||''; msg.style.color=ok?'var(--accent)':'var(--red)'; toast(data.message||'Saved',ok?'success':'error');
 if(ok&&MODE!=='edit')setTimeout(()=>location.href='/code-arena/organization/problems.php',700);
}
loadProblem();
</script>
