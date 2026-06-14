import { motion } from 'framer-motion';
import { buttonTap, cardMotion } from '../animations/motion.js';

export default function ContestCard({ contest, platform, isSaved, onSave }) {
  const start = new Date(contest.start_time);
  const end = new Date(contest.end_time);
  const target = contest.status === 'active' ? end : start;
  const statusClasses = {
    active: 'border-green-400/30 bg-green-400/10 text-green-300',
    upcoming: 'border-cyan-400/30 bg-cyan-400/10 text-cyan-300',
    ended: 'border-gray-700 bg-white/[0.04] text-gray-400',
  };

  return (
    <motion.article
      variants={cardMotion}
      whileHover="hover"
      className="grid grid-cols-1 gap-4 rounded-2xl border border-gray-800 bg-[#111827]/75 p-4 shadow-xl backdrop-blur-xl md:grid-cols-[44px_minmax(0,1fr)_190px_auto]"
    >
      <div className={`grid h-11 w-11 place-items-center rounded-xl text-xs font-black ${platform.tone}`}>{platform.short}</div>

      <div className="min-w-0">
        <div className="flex min-w-0 items-center gap-2">
          <h3 className="truncate text-base font-bold text-white">
            <a href={`/code-arena/contest.php?id=${contest.id}`} className="hover:text-green-300">
              {contest.title}
            </a>
          </h3>
          <span className={`shrink-0 rounded-full border px-2 py-1 text-[11px] font-black uppercase ${statusClasses[contest.status] || statusClasses.ended}`}>
            {contest.status === 'ended' ? 'Past' : contest.status}
          </span>
        </div>
        <div className="mt-2 flex flex-wrap gap-3 text-xs font-medium text-gray-400">
          <span>{platform.label}</span>
          <span>{formatDate(start)}</span>
          <span>{duration(start, end)}</span>
          <span>{Number(contest.participant_count || 0)} participants</span>
          {Number(contest.is_rated) ? <span className="text-green-300">Rated</span> : null}
        </div>
      </div>

      <div className="text-left md:text-right">
        <div className="font-mono text-sm font-black text-white">
          {contest.status === 'ended' ? 'Completed' : preciseCountdown(target - new Date())}
        </div>
        <div className="mt-1 text-xs text-gray-500">
          {contest.status === 'active' ? 'ends' : contest.status === 'upcoming' ? 'starts' : 'started'} {relativeTime(target - new Date())}
        </div>
      </div>

      <div className="flex flex-wrap items-center gap-2 md:justify-end">
        <motion.a
          {...buttonTap}
          href={`/code-arena/contest.php?id=${contest.id}`}
          className={`rounded-xl border px-3 py-2 text-xs font-black transition ${
            contest.status === 'ended'
              ? 'border-gray-800 bg-white/[0.04] text-gray-200 hover:bg-white/[0.08]'
              : 'border-green-400/30 bg-green-400/10 text-green-300 hover:bg-green-400/15'
          }`}
        >
          {contest.status === 'active' ? 'Enter' : contest.status === 'ended' ? 'Results' : 'Register'}
        </motion.a>
        <motion.button
          {...buttonTap}
          type="button"
          onClick={() => onSave(contest.id)}
          className={`rounded-xl border px-3 py-2 text-xs font-black transition ${
            isSaved ? 'border-yellow-400/30 bg-yellow-400/10 text-yellow-300' : 'border-gray-800 bg-white/[0.04] text-gray-300 hover:bg-white/[0.08]'
          }`}
        >
          {isSaved ? 'Saved' : 'Save'}
        </motion.button>
      </div>
    </motion.article>
  );
}

function formatDate(date) {
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function duration(start, end) {
  const minutes = Math.max(0, Math.round((end - start) / 60000));
  return minutes >= 60 ? `${Math.round(minutes / 60)}h` : `${minutes}m`;
}

function preciseCountdown(ms) {
  if (ms <= 0) return '00:00:00';
  const seconds = Math.floor(ms / 1000);
  const h = Math.floor(seconds / 3600);
  const m = Math.floor((seconds % 3600) / 60);
  const s = seconds % 60;
  return [h, m, s].map((value) => String(value).padStart(2, '0')).join(':');
}

function relativeTime(ms) {
  const abs = Math.abs(ms);
  const minutes = Math.ceil(abs / 60000);
  const days = Math.floor(minutes / 1440);
  const hours = Math.floor((minutes % 1440) / 60);
  const mins = minutes % 60;
  const prefix = ms >= 0 ? 'in ' : '';
  const suffix = ms < 0 ? ' ago' : '';
  if (days > 0) return `${prefix}${days}d ${hours}h${suffix}`;
  if (hours > 0) return `${prefix}${hours}h ${mins}m${suffix}`;
  return `${prefix}${mins}m${suffix}`;
}
