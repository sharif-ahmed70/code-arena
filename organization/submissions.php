<?php
require_once '../includes/session.php';
require_once '../config/db.php';
require_once '../includes/organization.php';
$organization = requireOrganizationPage($pdo);
$pageTitle = 'Contest Analytics';
$activeOrgPage = 'submissions';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contest Analytics - Code Arena</title>
    <link rel="stylesheet" href="/code-arena/assets/css/style.css?v=20260615-ui5">
    <style>
        .analytics-hero{display:flex;justify-content:space-between;gap:18px;align-items:flex-end;margin-bottom:20px}
        .analytics-hero p{color:var(--text-muted);margin-top:6px}
        .contest-card-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px}
        .contest-analytics-card{cursor:pointer;border:1px solid var(--border);border-radius:var(--radius);padding:18px;background:linear-gradient(180deg,rgba(255,255,255,.055),rgba(255,255,255,.028));transition:transform .3s,border-color .3s,box-shadow .3s}
        .contest-analytics-card:hover{transform:translateY(-4px);border-color:rgba(176,96,255,.6);box-shadow:var(--glow)}
        .contest-analytics-card h3{font-size:1rem;margin-bottom:10px}
        .status-pill{display:inline-flex;padding:4px 10px;border-radius:999px;font-size:.72rem;font-weight:800;background:var(--accent-dim);color:#c59bff}
        .status-pill.Live{background:var(--green-dim);color:var(--green)}
        .status-pill.Ended{background:rgba(255,255,255,.07);color:var(--text-muted)}
        .card-metrics{display:grid;grid-template-columns:repeat(2,1fr);gap:10px;margin-top:16px}
        .card-metric{padding:10px;border:1px solid var(--border);border-radius:var(--radius-sm);background:rgba(255,255,255,.035)}
        .card-metric strong{display:block;font-size:1.25rem;color:#c59bff}
        .card-metric span{color:var(--text-muted);font-size:.75rem}
        .analytics-grid{display:grid;grid-template-columns:minmax(0,1.35fr) minmax(300px,.65fr);gap:18px;align-items:start}
        .kpi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;margin-bottom:18px}
        .chart-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
        .chart-card{min-height:250px}
        .line-chart{height:190px;border:1px solid var(--border);border-radius:var(--radius-sm);background:rgba(255,255,255,.025);overflow:hidden}
        .line-chart svg{width:100%;height:100%}
        .bar-chart{height:190px;display:grid;grid-template-columns:repeat(12,1fr);gap:8px;align-items:end}
        .bar{min-height:6px;border-radius:8px 8px 3px 3px;background:linear-gradient(180deg,var(--accent),var(--green))}
        .analysis-list,.insight-list{display:grid;gap:10px}
        .analysis-item,.insight-item{padding:11px;border:1px solid var(--border);border-radius:var(--radius-sm);background:rgba(255,255,255,.035)}
        .analysis-item strong,.insight-item strong{display:block}
        .analysis-item span,.insight-item span{color:var(--text-muted);font-size:.8rem}
        .filter-panel{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px;margin-bottom:16px}
        .hidden{display:none!important}
        @media(max-width:980px){.analytics-grid,.chart-grid{grid-template-columns:1fr}.analytics-hero{align-items:flex-start;flex-direction:column}}
    </style>
</head>
<body>
<?php require_once '../includes/organization_shell.php'; ?>
<div class="org-content">
    <section id="contest-list-view">
        <div class="analytics-hero">
            <div>
                <h2>Contest Submission Analytics</h2>
                <p>Select a contest to inspect performance, submissions, participants, and insights.</p>
            </div>
            <button class="btn-outline ca-btn ca-btn-outline" onclick="loadContestCards()">Refresh</button>
        </div>
        <div id="contest-cards" class="contest-card-grid"></div>
    </section>

    <section id="contest-detail-view" class="hidden">
        <div class="analytics-hero">
            <div>
                <button class="btn-outline ca-btn ca-btn-outline" onclick="showContestList()">Back to contests</button>
                <h2 id="detail-title" style="margin-top:14px">Contest Analytics</h2>
                <p id="detail-subtitle"></p>
            </div>
            <span id="detail-status" class="status-pill"></span>
        </div>

        <div class="filter-panel org-card">
            <input id="filter_user" class="form-input" placeholder="User">
            <select id="filter_problem" class="form-input"><option value="">All problems</option></select>
            <select id="filter_status" class="form-input">
                <option value="">All verdicts</option>
                <option>Accepted</option>
                <option>Wrong Answer</option>
                <option>Runtime Error</option>
                <option>Time Limit Exceeded</option>
                <option>Compilation Error</option>
                <option>Pending</option>
            </select>
            <input id="filter_from" class="form-input" type="date">
            <input id="filter_to" class="form-input" type="date">
            <button class="btn-primary ca-btn ca-btn-primary" onclick="loadContestAnalytics()">Apply</button>
        </div>

        <div id="kpi-grid" class="kpi-grid"></div>
        <div class="analytics-grid">
            <main class="org-grid">
                <div class="chart-grid">
                    <section class="org-card chart-card"><h3>Submission Trend</h3><div id="submission-trend" class="line-chart"></div></section>
                    <section class="org-card chart-card"><h3>Verdict Distribution</h3><div id="verdict-chart" class="bar-chart"></div></section>
                    <section class="org-card chart-card"><h3>Difficulty vs Success</h3><div id="difficulty-chart" class="bar-chart"></div></section>
                    <section class="org-card chart-card"><h3>User Participation</h3><div id="participation-chart" class="bar-chart"></div></section>
                </div>
                <section class="org-card">
                    <h3 style="margin-bottom:14px">Filtered Submissions</h3>
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>ID</th><th>User</th><th>Problem</th><th>Verdict</th><th>Runtime</th><th>Hints</th><th>Submitted</th></tr></thead>
                            <tbody id="submission-body"></tbody>
                        </table>
                    </div>
                </section>
            </main>
            <aside class="org-grid">
                <section class="org-card"><h3>Top Performers</h3><div id="top-performers" class="analysis-list"></div></section>
                <section class="org-card"><h3>Most Active Users</h3><div id="most-active" class="analysis-list"></div></section>
                <section class="org-card"><h3>First AC Achievers</h3><div id="first-ac" class="analysis-list"></div></section>
                <section class="org-card"><h3>Highest Wrong Submissions</h3><div id="highest-wrong" class="analysis-list"></div></section>
                <section class="org-card"><h3>Smart Insights</h3><div id="smart-insights" class="insight-list"></div></section>
            </aside>
        </div>
    </section>
</div>
<?php require_once '../includes/organization_shell_end.php'; ?>
<script src="/code-arena/assets/js/main.js?v=20260615-ui5"></script>
<script>
let selectedContestId = null;
function esc(s){return String(s??'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));}
function pct(v){return `${Number(v||0).toFixed(1)}%`;}
async function loadContestCards(){
    const {ok,data}=await api('/code-arena/api/organization/submissions.php?mode=contests');
    const wrap=document.getElementById('contest-cards');
    if(!ok||!data.success){wrap.innerHTML=`<div class="org-card">${esc(data.message||'Failed to load contests')}</div>`;return;}
    wrap.innerHTML=(data.data.contests||[]).map(c=>`<article class="contest-analytics-card" onclick="openContest(${c.id})"><div style="display:flex;justify-content:space-between;gap:12px"><h3>${esc(c.title)}</h3><span class="status-pill ${esc(c.status_label)}">${esc(c.status_label)}</span></div><div style="color:var(--text-muted);font-size:.82rem">${esc(c.start_time)} to ${esc(c.end_time)}</div><div class="card-metrics"><div class="card-metric"><strong>${c.total_participants}</strong><span>Participants</span></div><div class="card-metric"><strong>${c.total_submissions}</strong><span>Submissions</span></div><div class="card-metric"><strong>${c.accepted_submissions}</strong><span>Accepted</span></div><div class="card-metric"><strong>${pct(c.acceptance_rate)}</strong><span>Acceptance</span></div></div></article>`).join('')||'<div class="org-card">No contests yet.</div>';
}
function showContestList(){
    selectedContestId=null;
    document.getElementById('contest-detail-view').classList.add('hidden');
    document.getElementById('contest-list-view').classList.remove('hidden');
}
async function openContest(id){
    selectedContestId=id;
    document.getElementById('contest-list-view').classList.add('hidden');
    document.getElementById('contest-detail-view').classList.remove('hidden');
    await loadContestAnalytics();
}
function query(){
    const qs=new URLSearchParams({mode:'analytics',contest_id:selectedContestId});
    if(filter_user.value)qs.set('user',filter_user.value);
    if(filter_problem.value)qs.set('problem_id',filter_problem.value);
    if(filter_status.value)qs.set('status',filter_status.value);
    if(filter_from.value)qs.set('date_from',filter_from.value);
    if(filter_to.value)qs.set('date_to',filter_to.value);
    return qs.toString();
}
async function loadContestAnalytics(){
    const {ok,data}=await api('/code-arena/api/organization/submissions.php?'+query());
    if(!ok||!data.success){toast(data.message||'Failed to load analytics','error');return;}
    const d=data.data;
    detailTitle(d.contest);
    renderKpis(d.overview);
    renderProblemFilter(d.problems);
    renderLine('submission-trend',d.charts.submission_trend||[],'submissions','accepted');
    renderBars('verdict-chart',d.charts.verdict_distribution||[],'count','status');
    renderBars('difficulty-chart',d.charts.difficulty_success||[],'success_rate','difficulty');
    renderBars('participation-chart',d.charts.participation_distribution||[],'submissions','username');
    renderAnalysis('top-performers',d.participants.top_performers,r=>[esc(r.username),`${r.solved} solved - ${pct(r.acceptance_rate)} acceptance`]);
    renderAnalysis('most-active',d.participants.most_active,r=>[esc(r.username),`${r.submissions} submissions - ${r.accepted||0} accepted`]);
    renderAnalysis('first-ac',d.participants.first_ac,r=>[esc(r.username),`${esc(r.problem_title)} - ${esc(r.first_ac_at)}`]);
    renderAnalysis('highest-wrong',d.participants.highest_wrong,r=>[esc(r.username),`${r.wrong_submissions} wrong submissions`]);
    renderInsights(d.insights);
    renderSubmissions(d.submissions||[]);
}
function detailTitle(c){
    document.getElementById('detail-title').textContent=c.title;
    document.getElementById('detail-subtitle').textContent=`${c.start_time} to ${c.end_time}`;
    const s=document.getElementById('detail-status');
    s.textContent=c.status_label;
    s.className=`status-pill ${c.status_label}`;
}
function renderKpis(o){
    document.getElementById('kpi-grid').innerHTML=[['Participants',o.total_participants],['Submissions',o.total_submissions],['Accepted',o.accepted_submissions],['Wrong',o.wrong_submissions],['Acceptance',pct(o.acceptance_rate)]].map(([l,v])=>`<div class="metric-card"><strong>${v}</strong><span>${l}</span></div>`).join('');
}
function renderProblemFilter(problems){
    const current=filter_problem.value;
    filter_problem.innerHTML='<option value="">All problems</option>'+(problems||[]).map(p=>`<option value="${p.id}">${esc(p.title)}</option>`).join('');
    filter_problem.value=current;
}
function renderBars(id,rows,key,label){
    const el=document.getElementById(id),max=Math.max(1,...(rows||[]).map(r=>Number(r[key]||0)));
    el.innerHTML=(rows||[]).length?(rows||[]).slice(0,12).map(r=>`<div class="bar" title="${esc(r[label])}: ${r[key]||0}" style="height:${Math.max(6,Math.round(Number(r[key]||0)/max*100))}%"></div>`).join(''):'<span style="color:var(--text-muted)">No data</span>';
}
function renderLine(id,rows,key,secondary){
    const el=document.getElementById(id),pts=(rows||[]).slice(-32);
    if(!pts.length){el.innerHTML='<div style="padding:18px;color:var(--text-muted)">No data</div>';return;}
    const keys=secondary?[key,secondary]:[key],vals=pts.flatMap(r=>keys.map(k=>Number(r[k]||0))),min=Math.min(...vals),max=Math.max(...vals,min+1),w=640,h=190,p=18;
    const path=k=>pts.map((r,i)=>{const x=p+i/Math.max(1,pts.length-1)*(w-p*2),y=h-p-((Number(r[k]||0)-min)/(max-min))*(h-p*2);return `${i?'L':'M'}${x.toFixed(1)},${y.toFixed(1)}`}).join(' ');
    el.innerHTML=`<svg viewBox="0 0 ${w} ${h}"><path d="${path(key)}" fill="none" stroke="#8b4dff" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>${secondary?`<path d="${path(secondary)}" fill="none" stroke="#22c55e" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>`:''}</svg>`;
}
function renderAnalysis(id,rows,fn){
    document.getElementById(id).innerHTML=(rows||[]).length?(rows||[]).map(r=>{const [title,meta]=fn(r);return `<div class="analysis-item"><strong>${title}</strong><span>${meta}</span></div>`;}).join(''):'<div class="analysis-item"><span>No data yet.</span></div>';
}
function renderInsights(i){
    const rows=[['Hardest Problem',i.hardest_problem?`${i.hardest_problem.title} - ${pct(i.hardest_problem.success_rate)} success`:'No accepted data yet'],['Most Failed Problem',i.most_failed_problem?`${i.most_failed_problem.title} - ${i.most_failed_problem.wrong} wrong`:'No failures yet'],['Peak Submission Time',i.peak_submission_time?`${i.peak_submission_time.hour_label} - ${i.peak_submission_time.submissions} submissions`:'No peak yet'],['Weak Topic Area',i.weak_topic_area?`${i.weak_topic_area.tag} - ${i.weak_topic_area.wrong_submissions} wrong`:'No topic signal yet']];
    document.getElementById('smart-insights').innerHTML=rows.map(([a,b])=>`<div class="insight-item"><strong>${esc(a)}</strong><span>${esc(b)}</span></div>`).join('');
}
function renderSubmissions(rows){
    document.getElementById('submission-body').innerHTML=rows.length?rows.map(s=>`<tr><td>${s.id}</td><td>${esc(s.username)}<br><span style="color:var(--text-muted)">${esc(s.email)}</span></td><td>${esc(s.problem_title)}</td><td>${esc(s.status)}</td><td>${s.runtime_ms||0} ms</td><td>${s.hints_used||0}</td><td>${esc(s.submitted_at)}</td></tr>`).join(''):'<tr><td colspan="7">No submissions match these filters.</td></tr>';
}
['filter_user','filter_problem','filter_status','filter_from','filter_to'].forEach(id=>{
    const el=document.getElementById(id);
    ['input','change'].forEach(eventName=>el.addEventListener(eventName,()=>{if(selectedContestId)loadContestAnalytics();}));
});
loadContestCards();
</script>
</body>
</html>
