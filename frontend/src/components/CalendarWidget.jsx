export default function CalendarWidget({ contests, nextContest, savedContests, onRemoveSaved }) {
  const now = new Date();
  const first = new Date(now.getFullYear(), now.getMonth(), 1);
  const daysInMonth = new Date(now.getFullYear(), now.getMonth() + 1, 0).getDate();
  const contestDays = new Set(
    contests
      .map((contest) => new Date(contest.start_time))
      .filter((date) => date.getFullYear() === now.getFullYear() && date.getMonth() === now.getMonth())
      .map((date) => date.getDate())
  );

  return (
    <aside className="glass-card sticky top-28 overflow-hidden rounded-2xl">
      <section className="border-b border-gray-800 p-4">
        <h2 className="mb-4 text-xs font-black uppercase tracking-[0.16em] text-gray-300">
          {now.toLocaleDateString('en-US', { month: 'long', year: 'numeric' })}
        </h2>
        <div className="grid grid-cols-7 gap-1">
          {['S', 'M', 'T', 'W', 'T', 'F', 'S'].map((day, index) => (
            <div key={`${day}-${index}`} className="grid h-6 place-items-center text-xs font-black text-gray-600">
              {day}
            </div>
          ))}
          {Array.from({ length: first.getDay() }).map((_, index) => (
            <div key={`blank-${index}`} />
          ))}
          {Array.from({ length: daysInMonth }).map((_, index) => {
            const day = index + 1;
            const isToday = day === now.getDate();
            const hasContest = contestDays.has(day);
            return (
              <div
                key={day}
                className={`grid aspect-square place-items-center rounded-lg border text-xs font-bold ${
                  isToday
                    ? 'border-green-400/40 bg-green-400/10 text-green-300'
                    : hasContest
                      ? 'border-cyan-400/30 bg-cyan-400/10 text-white'
                      : 'border-transparent bg-white/[0.04] text-gray-500'
                }`}
              >
                {day}
              </div>
            );
          })}
        </div>
      </section>

      <section className="border-b border-gray-800 p-4">
        <h2 className="mb-4 text-xs font-black uppercase tracking-[0.16em] text-gray-300">Next Contest</h2>
        <div className="rounded-xl border border-gray-800 bg-white/[0.04] p-4">
          {nextContest ? (
            <>
              <div className="text-sm font-bold leading-5 text-white">{nextContest.title}</div>
              <div className="mt-3 font-mono text-xl font-black text-green-300">
                {preciseCountdown((nextContest.status === 'active' ? new Date(nextContest.end_time) : new Date(nextContest.start_time)) - new Date())}
              </div>
              <div className="mt-2 text-xs text-gray-500">{formatDate(new Date(nextContest.start_time))}</div>
            </>
          ) : (
            <div className="text-sm text-gray-400">No scheduled contest.</div>
          )}
        </div>
      </section>

      <section className="p-4">
        <h2 className="mb-4 text-xs font-black uppercase tracking-[0.16em] text-gray-300">Saved Contests</h2>
        <div className="space-y-2">
          {savedContests.length ? (
            savedContests.slice(0, 5).map((contest) => (
              <div key={contest.id} className="grid grid-cols-[1fr_30px] items-center gap-2 rounded-xl border border-gray-800 bg-white/[0.04] p-3">
                <a href={`/code-arena/contest.php?id=${contest.id}`} className="min-w-0">
                  <div className="truncate text-sm font-bold text-white">{contest.title}</div>
                  <div className="mt-1 text-xs text-gray-500">{formatDate(new Date(contest.start_time))}</div>
                </a>
                <button
                  type="button"
                  onClick={() => onRemoveSaved(contest.id)}
                  className="h-8 w-8 rounded-lg border border-gray-800 text-sm text-gray-400 hover:border-red-400/30 hover:text-red-300"
                >
                  x
                </button>
              </div>
            ))
          ) : (
            <div className="rounded-xl border border-gray-800 bg-white/[0.04] p-3 text-sm text-gray-400">No saved contests yet.</div>
          )}
        </div>
      </section>
    </aside>
  );
}

function formatDate(date) {
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function preciseCountdown(ms) {
  if (ms <= 0) return '00:00:00';
  const seconds = Math.floor(ms / 1000);
  const h = Math.floor(seconds / 3600);
  const m = Math.floor((seconds % 3600) / 60);
  const s = seconds % 60;
  return [h, m, s].map((value) => String(value).padStart(2, '0')).join(':');
}
