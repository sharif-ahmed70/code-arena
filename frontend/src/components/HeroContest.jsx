import { motion } from 'framer-motion';
import { buttonTap } from '../animations/motion.js';

export default function HeroContest({ contest, platform, isSaved, onSave }) {
  if (!contest) {
    return (
      <section className="glass-card rounded-2xl p-6">
        <div className="text-sm font-black uppercase tracking-[0.18em] text-green-300">Next Contest</div>
        <h1 className="mt-4 max-w-3xl text-3xl font-black tracking-tight text-white md:text-5xl">
          No upcoming contests scheduled
        </h1>
        <p className="mt-4 max-w-2xl text-sm leading-6 text-gray-400">
          Keep practicing problems while the next round is prepared.
        </p>
        <motion.a
          {...buttonTap}
          href="/code-arena/problems.php"
          className="mt-6 inline-flex rounded-xl border border-green-400/30 bg-green-400/10 px-4 py-3 text-sm font-black text-green-300"
        >
          Practice Problems
        </motion.a>
      </section>
    );
  }

  const target = contest.status === 'active' ? new Date(contest.end_time) : new Date(contest.start_time);

  return (
    <section className="glass-card relative overflow-hidden rounded-2xl p-6 md:p-8">
      <div className="absolute inset-0 bg-[radial-gradient(circle_at_85%_18%,rgba(74,222,128,0.18),transparent_32%),linear-gradient(135deg,rgba(34,211,238,0.10),rgba(168,85,247,0.10))]" />
      <div className="relative grid items-center gap-6 lg:grid-cols-[minmax(0,1fr)_260px]">
        <div>
          <div className="flex items-center gap-3 text-xs font-black uppercase tracking-[0.18em] text-green-300">
            <span className="h-2 w-2 rounded-full bg-green-400 shadow-[0_0_0_6px_rgba(74,222,128,0.12)]" />
            Next Contest
          </div>
          <div className="mt-4 flex items-center gap-3">
            <span className={`grid h-11 w-11 place-items-center rounded-xl text-xs font-black ${platform.tone}`}>{platform.short}</span>
            <span className="text-sm font-semibold text-gray-300">{platform.label}</span>
          </div>
          <h1 className="mt-4 max-w-4xl text-3xl font-black leading-tight tracking-tight text-white md:text-5xl">
            {contest.title}
          </h1>
          <div className="mt-4 flex flex-wrap gap-3 text-sm text-gray-400">
            <span>{formatDate(new Date(contest.start_time))}</span>
            <span>{Number(contest.participant_count || 0)} participants</span>
            {Number(contest.is_rated) ? <span className="text-green-300">Rated</span> : null}
          </div>
          <div className="mt-6 flex flex-wrap gap-3">
            <motion.a
              {...buttonTap}
              href={`/code-arena/contest.php?id=${contest.id}`}
              className="rounded-xl border border-green-400/40 bg-gradient-to-r from-green-400 to-cyan-400 px-5 py-3 text-sm font-black text-[#06100d] shadow-greenGlow"
            >
              {contest.status === 'active' ? 'Enter Contest' : 'Register'}
            </motion.a>
            <motion.button
              {...buttonTap}
              type="button"
              onClick={() => onSave(contest.id)}
              className={`rounded-xl border px-5 py-3 text-sm font-black ${
                isSaved ? 'border-yellow-400/30 bg-yellow-400/10 text-yellow-300' : 'border-gray-700 bg-white/[0.05] text-white'
              }`}
            >
              {isSaved ? 'Saved' : 'Save Contest'}
            </motion.button>
          </div>
        </div>

        <div className="rounded-2xl border border-white/10 bg-black/20 p-5 text-center">
          <div className="text-xs font-black uppercase tracking-[0.18em] text-gray-400">
            {contest.status === 'active' ? 'Ends In' : 'Starts In'}
          </div>
          <div className="mt-3 font-mono text-3xl font-black text-green-300">
            {preciseCountdown(target - new Date())}
          </div>
          <div className="mt-3 text-xs text-gray-400">{formatDate(new Date(contest.start_time))}</div>
        </div>
      </div>
    </section>
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
