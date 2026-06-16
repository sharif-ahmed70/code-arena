<?php
require_once '../includes/session.php';
require_once '../config/db.php';
require_once '../includes/organization.php';
$organization = requireOrganizationPage($pdo);
$pageTitle = 'Analytics';
$activeOrgPage = 'analytics';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organization Analytics - Code Arena</title>
    <link rel="stylesheet" href="/code-arena/assets/css/style.css?v=20260615-ui5">
    <style>
        .metric-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:16px;margin-bottom:18px}
        .metric-card strong{display:block;font-size:1.8rem;line-height:1}
        .metric-card span{display:block;color:var(--text-muted);font-size:.76rem;text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px}
        .analytics-layout{display:grid;grid-template-columns:repeat(2,minmax(320px,1fr));gap:18px}
        .analytics-layout.wide{grid-template-columns:1fr;margin-top:18px}
        .chart-panel{border:1px solid var(--border);border-radius:var(--radius);background:var(--bg-card);padding:18px;min-width:0}
        .chart-head{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;margin-bottom:12px}
        .chart-head h2{margin:0;font-size:1rem}
        .chart-head p{margin:4px 0 0;color:var(--text-muted);font-size:.78rem}
        .chart-box{min-height:285px;border:1px solid var(--border);border-radius:var(--radius-sm);background:rgba(255,255,255,.025);overflow:hidden}
        .chart-box svg{width:100%;height:260px;display:block}
        .chart-meta{display:flex;justify-content:space-between;gap:12px;color:var(--text-muted);font-size:.74rem;margin:0 0 10px}
        .chart-legend{display:flex;gap:12px;flex-wrap:wrap;color:var(--text-muted);font-size:.74rem;margin-bottom:4px}
        .legend-dot{width:8px;height:8px;border-radius:50%;display:inline-block;margin-right:6px}
        .rank-list{display:grid;gap:10px}
        .rank-row{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:12px;align-items:center;padding:12px;border:1px solid var(--border);border-radius:var(--radius-sm);background:var(--bg-card2)}
        .rank-row strong{display:block;color:var(--text)}
        .rank-row small{display:block;color:var(--text-muted);margin-top:3px}
        .pill{padding:5px 9px;border-radius:999px;background:rgba(124,58,237,.12);color:var(--purple);font-size:.74rem;font-weight:800;white-space:nowrap}
        .insight-grid{display:grid;grid-template-columns:repeat(3,minmax(180px,1fr));gap:14px;margin-bottom:18px}
        .insight-card{border:1px solid var(--border);border-radius:var(--radius);background:linear-gradient(180deg,rgba(255,255,255,.05),rgba(255,255,255,.025));padding:16px}
        .insight-card span{display:block;color:var(--text-muted);font-size:.74rem;text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px}
        .insight-card strong{display:block;font-size:1.05rem;color:var(--text)}
        .insight-card p{margin:8px 0 0;color:var(--text-dim);font-size:.83rem;line-height:1.55}
        @media(max-width:980px){.analytics-layout{grid-template-columns:1fr}}
        @media(max-width:760px){.insight-grid{grid-template-columns:1fr}}
    </style>
</head>
<body>
<?php require_once '../includes/organization_shell.php'; ?>
<div class="org-content">
    <div id="summary" class="metric-grid"></div>
    <div id="insights" class="insight-grid"></div>

    <div class="analytics-layout">
        <section class="chart-panel">
            <div class="chart-head"><div><h2>Daily Submission Trend</h2><p>X-axis: date, Y-axis: submissions and active users</p></div></div>
            <div id="engagement-chart" class="chart-box"></div>
        </section>
        <section class="chart-panel">
            <div class="chart-head"><div><h2>Difficulty vs Success Rate</h2><p>X-axis: difficulty, Y-axis: accepted percentage</p></div></div>
            <div id="difficulty-chart" class="chart-box"></div>
        </section>
        <section class="chart-panel">
            <div class="chart-head"><div><h2>Contest Participation</h2><p>X-axis: recent contests, Y-axis: registered participants</p></div></div>
            <div id="contest-chart" class="chart-box"></div>
        </section>
        <section class="chart-panel">
            <div class="chart-head"><div><h2>Verdict Distribution</h2><p>X-axis: verdict, Y-axis: submission count</p></div></div>
            <div id="verdict-chart" class="chart-box"></div>
        </section>
        <section class="chart-panel">
            <div class="chart-head"><div><h2>Peak Submission Hours</h2><p>X-axis: hour of day, Y-axis: submissions</p></div></div>
            <div id="hourly-chart" class="chart-box"></div>
        </section>
        <section class="chart-panel">
            <div class="chart-head"><div><h2>Problem Success Rate</h2><p>X-axis: problem, Y-axis: accepted percentage</p></div></div>
            <div id="problem-chart" class="chart-box"></div>
        </section>
        <section class="chart-panel">
            <div class="chart-head"><div><h2>Most Solved Problems</h2><p>Ranked by accepted submissions</p></div></div>
            <div id="solved-list" class="rank-list"></div>
        </section>
        <section class="chart-panel">
            <div class="chart-head"><div><h2>Top Performers</h2><p>Ranked by accepted submissions and accuracy</p></div></div>
            <div id="performers-list" class="rank-list"></div>
        </section>
        <section class="chart-panel">
            <div class="chart-head"><div><h2>Engagement Table</h2><p>Daily values used by the chart</p></div></div>
            <div id="engagement-table" class="rank-list"></div>
        </section>
    </div>
</div>
<?php require_once '../includes/organization_shell_end.php'; ?>
<script src="/code-arena/assets/js/main.js?v=20260615-ui5"></script>
<script>
function esc(s){return String(s??'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));}
function shortLabel(raw){const s=String(raw||'-');return s.length>12?s.slice(0,11)+'…':s;}
function metric(label,value){return `<div class="org-card metric-card"><span>${esc(label)}</span><strong>${esc(value ?? 0)}</strong></div>`}

function renderLineChart(id, rows, primary, secondary, yAxis, xAxis) {
    const el=document.getElementById(id), points=(rows||[]).slice(-30);
    if(!points.length){el.innerHTML='<div style="padding:18px;color:var(--text-muted)">No recent activity yet.</div>';return;}
    const keys=secondary?[primary,secondary]:[primary];
    const vals=points.flatMap(r=>keys.map(k=>Number(r[k]||0)));
    const min=Math.min(...vals), max=Math.max(...vals,min+1);
    const w=680,h=260,left=56,right=22,top=28,bottom=54,plotW=w-left-right,plotH=h-top-bottom;
    const xFor=i=>left+(i/Math.max(1,points.length-1))*plotW;
    const yFor=v=>top+plotH-((Number(v||0)-min)/(max-min))*plotH;
    const pathFor=k=>points.map((r,i)=>`${i?'L':'M'}${xFor(i).toFixed(1)},${yFor(r[k]).toFixed(1)}`).join(' ');
    const ticks=[0,.25,.5,.75,1].map(p=>Math.round(min+(max-min)*p));
    const grid=ticks.map(t=>{const y=yFor(t);return `<g><line x1="${left}" x2="${w-right}" y1="${y}" y2="${y}" stroke="rgba(255,255,255,.08)"/><text x="${left-10}" y="${y+4}" text-anchor="end" fill="#8f8faa" font-size="10">${t}</text></g>`}).join('');
    const indexes=[...new Set([0,Math.floor((points.length-1)/2),points.length-1])];
    const xLabels=indexes.map(i=>`<text x="${xFor(i).toFixed(1)}" y="${h-24}" text-anchor="middle" fill="#8f8faa" font-size="10">${shortLabel(points[i].day)}</text>`).join('');
    const latest=points[points.length-1];
    el.innerHTML=`<div style="padding:12px 12px 0"><div class="chart-meta"><span>${esc(points[0].day)} → ${esc(latest.day)}</span><span>Latest: ${esc(primary)} ${latest[primary]||0}${secondary?` / ${esc(secondary)} ${latest[secondary]||0}`:''}</span></div>
    <div class="chart-legend"><span><i class="legend-dot" style="background:#22c55e"></i>${esc(primary)}</span>${secondary?`<span><i class="legend-dot" style="background:#a78bfa"></i>${esc(secondary)}</span>`:''}</div></div>
    <svg viewBox="0 0 ${w} ${h}" role="img" aria-label="${esc(yAxis)} by ${esc(xAxis)}">
        <defs><linearGradient id="lineGrad-${id}" x1="0" x2="1"><stop offset="0" stop-color="#7c3aed"/><stop offset="1" stop-color="#22c55e"/></linearGradient></defs>
        ${grid}<line x1="${left}" x2="${left}" y1="${top}" y2="${top+plotH}" stroke="rgba(255,255,255,.18)"/><line x1="${left}" x2="${w-right}" y1="${top+plotH}" y2="${top+plotH}" stroke="rgba(255,255,255,.18)"/>
        <text x="16" y="${top+plotH/2}" transform="rotate(-90 16 ${top+plotH/2})" text-anchor="middle" fill="#c2c2da" font-size="11">${esc(yAxis)}</text>
        <text x="${left+plotW/2}" y="${h-6}" text-anchor="middle" fill="#c2c2da" font-size="11">${esc(xAxis)}</text>${xLabels}
        <path d="${pathFor(primary)}" fill="none" stroke="url(#lineGrad-${id})" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
        ${secondary?`<path d="${pathFor(secondary)}" fill="none" stroke="rgba(167,139,250,.62)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>`:''}
    </svg>`;
}

function renderBarChart(id, rows, valueKey, labelKey, yAxis, xAxis) {
    const el=document.getElementById(id), items=(rows||[]).slice(0,10);
    if(!items.length){el.innerHTML='<div style="padding:18px;color:var(--text-muted)">No difficulty data yet.</div>';return;}
    const max=Math.max(1,...items.map(r=>Number(r[valueKey]||0)));
    const w=680,h=260,left=56,right=22,top=28,bottom=58,plotW=w-left-right,plotH=h-top-bottom;
    const step=plotW/items.length,barW=Math.max(28,step*.52),yFor=v=>top+plotH-(Number(v||0)/max)*plotH;
    const ticks=[0,.25,.5,.75,1].map(p=>Math.round(max*p));
    const grid=ticks.map(t=>{const y=yFor(t);return `<g><line x1="${left}" x2="${w-right}" y1="${y}" y2="${y}" stroke="rgba(255,255,255,.08)"/><text x="${left-10}" y="${y+4}" text-anchor="end" fill="#8f8faa" font-size="10">${t}</text></g>`}).join('');
    const bars=items.map((r,i)=>{const v=Number(r[valueKey]||0),x=left+i*step+(step-barW)/2,y=yFor(v),label=String(r[labelKey]||'-');return `<g><title>${esc(label)}: ${v}</title><rect x="${x}" y="${y}" width="${barW}" height="${Math.max(2,top+plotH-y)}" rx="7" fill="url(#barGrad-${id})"/><text x="${x+barW/2}" y="${Math.max(14,y-7)}" text-anchor="middle" fill="#f0f0f6" font-size="11" font-weight="700">${v}</text><text x="${x+barW/2}" y="${h-24}" text-anchor="middle" fill="#8f8faa" font-size="10">${shortLabel(label)}</text></g>`}).join('');
    el.innerHTML=`<div style="padding:12px 12px 0"><div class="chart-meta"><span>Y-axis: ${esc(yAxis)}</span><span>X-axis: ${esc(xAxis)}</span></div></div>
    <svg viewBox="0 0 ${w} ${h}" role="img" aria-label="${esc(yAxis)} by ${esc(xAxis)}">
        <defs><linearGradient id="barGrad-${id}" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#22c55e"/><stop offset="1" stop-color="#7c3aed"/></linearGradient></defs>
        ${grid}<line x1="${left}" x2="${left}" y1="${top}" y2="${top+plotH}" stroke="rgba(255,255,255,.18)"/><line x1="${left}" x2="${w-right}" y1="${top+plotH}" y2="${top+plotH}" stroke="rgba(255,255,255,.18)"/>
        <text x="16" y="${top+plotH/2}" transform="rotate(-90 16 ${top+plotH/2})" text-anchor="middle" fill="#c2c2da" font-size="11">${esc(yAxis)}</text><text x="${left+plotW/2}" y="${h-6}" text-anchor="middle" fill="#c2c2da" font-size="11">${esc(xAxis)}</text>${bars}
    </svg>`;
}

function renderInsights(d, summary) {
    const hardest = d.hardest_problem || {};
    const verdicts = d.verdict_distribution || [];
    const topVerdict = verdicts[0] || {};
    const hourly = (d.hourly_activity || []).slice().sort((a,b)=>Number(b.submissions||0)-Number(a.submissions||0))[0] || {};
    const subs = Number(summary.submissions || 0);
    const accepted = Number(summary.accepted || 0);
    const success = subs ? Math.round(accepted * 100 / subs) : 0;
    document.getElementById('insights').innerHTML = [
        ['Hardest Problem', hardest.title || 'Not enough data', hardest.title ? `${hardest.success_rate || 0}% success across ${hardest.submissions || 0} submissions` : 'More contest submissions will reveal the hardest problem.'],
        ['Peak Activity', hourly.hour !== undefined ? `${String(hourly.hour).padStart(2,'0')}:00` : 'No activity yet', hourly.hour !== undefined ? `${hourly.submissions || 0} submissions from ${hourly.users || 0} users` : 'No hourly pattern available yet.'],
        ['Overall Health', `${success}% success`, topVerdict.verdict ? `Most common verdict: ${topVerdict.verdict} (${topVerdict.total})` : 'Verdict mix will appear after submissions.'],
    ].map(([label,value,copy])=>`<div class="insight-card"><span>${esc(label)}</span><strong>${esc(value)}</strong><p>${esc(copy)}</p></div>`).join('');
}

async function loadAnalytics(){
    const {ok,data}=await api('/code-arena/api/organization/analytics.php');
    if(!ok||!data.success){toast(data.message||'Failed','error');return;}
    const d=data.data,s=d.summary||{},accepted=Number(s.accepted||0),subs=Number(s.submissions||0);
    summary.innerHTML=metric('Contests',s.contests)+metric('Participants',s.participants)+metric('Submissions',subs)+metric('Accepted',accepted)+metric('Success Rate',subs?Math.round(accepted*100/subs)+'%':'0%');
    renderInsights(d,s);
    const difficulty=(d.difficulty||[]).map(x=>{const total=Number(x.submissions||0),acc=Number(x.accepted||0);return {...x,acceptance_rate:total?Math.round(acc*100/total):0};});
    renderLineChart('engagement-chart',d.engagement||[],'submissions','users','Submissions / users','Date');
    renderBarChart('difficulty-chart',difficulty,'acceptance_rate','difficulty','Acceptance %','Difficulty');
    renderBarChart('contest-chart',(d.contest_participation||[]).slice().reverse(),'participants','title','Participants','Contest');
    renderBarChart('verdict-chart',d.verdict_distribution||[],'total','verdict','Submissions','Verdict');
    renderBarChart('hourly-chart',(d.hourly_activity||[]).map(x=>({...x,hour_label:String(x.hour).padStart(2,'0')+':00'})),'submissions','hour_label','Submissions','Hour');
    renderBarChart('problem-chart',d.problem_performance||[],'success_rate','title','Acceptance %','Problem');
    document.getElementById('solved-list').innerHTML=(d.most_solved_problems||[]).map((x,i)=>`<div class="rank-row"><div><strong>#${i+1} ${esc(x.title)}</strong><small>${esc(x.difficulty)} difficulty</small></div><span class="pill">${x.accepted_count} AC</span></div>`).join('')||'<div class="rank-row">No accepted submissions yet.</div>';
    document.getElementById('performers-list').innerHTML=(d.top_performers||[]).map((x,i)=>`<div class="rank-row"><div><strong>#${i+1} ${esc(x.username)}</strong><small>${x.submissions} submissions / ${x.success_rate || 0}% success</small></div><span class="pill">${x.accepted || 0} AC</span></div>`).join('')||'<div class="rank-row">No performer data yet.</div>';
    document.getElementById('engagement-table').innerHTML=(d.engagement||[]).slice(-10).reverse().map(x=>`<div class="rank-row"><div><strong>${esc(x.day)}</strong><small>${x.users} active users</small></div><span class="pill">${x.submissions} submissions</span></div>`).join('')||'<div class="rank-row">No recent activity.</div>';
}
loadAnalytics();
</script>
</body>
</html>
