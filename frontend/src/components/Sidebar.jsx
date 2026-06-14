import { motion } from 'framer-motion';
import { buttonTap } from '../animations/motion.js';

const platforms = [
  ['codeforces', 'Codeforces', 'CF', 'text-red-300 bg-red-400/10'],
  ['codechef', 'CodeChef', 'CC', 'text-purple-300 bg-purple-400/10'],
  ['leetcode', 'LeetCode', 'LC', 'text-yellow-300 bg-yellow-400/10'],
  ['atcoder', 'AtCoder', 'AT', 'text-cyan-300 bg-cyan-400/10'],
  ['hackerrank', 'HackerRank', 'HR', 'text-green-300 bg-green-400/10'],
];

const statuses = [
  ['', 'All'],
  ['active', 'Live'],
  ['upcoming', 'Upcoming'],
  ['ended', 'Past'],
];

export default function Sidebar({
  selectedPlatforms,
  onPlatformToggle,
  status,
  onStatusChange,
  counts,
  savedOnly,
  onSavedToggle,
}) {
  return (
    <aside className="glass-card sticky top-28 overflow-hidden rounded-2xl">
      <section className="border-b border-gray-800 p-4">
        <h2 className="mb-4 text-xs font-black uppercase tracking-[0.16em] text-gray-300">Platforms</h2>
        <div className="space-y-2">
          {platforms.map(([key, label, short, tone]) => {
            const checked = selectedPlatforms.includes(key);
            return (
              <label
                key={key}
                className={`flex cursor-pointer items-center justify-between rounded-xl border p-3 transition hover:-translate-y-0.5 hover:bg-white/[0.05] ${
                  checked ? 'border-gray-700 bg-white/[0.04]' : 'border-transparent'
                }`}
              >
                <span className="flex items-center gap-3 text-sm font-semibold text-gray-200">
                  <span className={`grid h-8 w-8 place-items-center rounded-lg text-xs font-black ${tone}`}>{short}</span>
                  {label}
                </span>
                <span className={`relative h-5 w-9 rounded-full transition ${checked ? 'bg-gradient-to-r from-green-400 to-cyan-400' : 'bg-gray-700'}`}>
                  <input className="sr-only" type="checkbox" checked={checked} onChange={() => onPlatformToggle(key)} />
                  <span className={`absolute top-0.5 h-4 w-4 rounded-full bg-white transition ${checked ? 'left-[18px]' : 'left-0.5'}`} />
                </span>
              </label>
            );
          })}
        </div>
      </section>

      <section className="border-b border-gray-800 p-4">
        <h2 className="mb-4 text-xs font-black uppercase tracking-[0.16em] text-gray-300">Status</h2>
        <div className="space-y-2">
          {statuses.map(([key, label]) => (
            <motion.button
              {...buttonTap}
              key={key}
              onClick={() => onStatusChange(key)}
              className={`flex w-full items-center justify-between rounded-xl border px-3 py-2.5 text-left text-sm font-semibold transition ${
                status === key ? 'border-cyan-400/30 bg-cyan-400/10 text-white' : 'border-transparent text-gray-400 hover:bg-white/[0.05] hover:text-white'
              }`}
            >
              {label}
              <span className="text-xs text-gray-500">{counts[key || 'all'] || 0}</span>
            </motion.button>
          ))}
        </div>
      </section>

      <section className="p-4">
        <h2 className="mb-4 text-xs font-black uppercase tracking-[0.16em] text-gray-300">Saved</h2>
        <motion.button
          {...buttonTap}
          onClick={onSavedToggle}
          className={`flex w-full items-center justify-between rounded-xl border px-3 py-3 text-sm font-bold transition ${
            savedOnly ? 'border-yellow-400/30 bg-yellow-400/10 text-yellow-200' : 'border-gray-800 bg-white/[0.04] text-gray-200 hover:bg-white/[0.07]'
          }`}
        >
          Saved Contests
          <span className="text-xs text-gray-500">{counts.saved || 0}</span>
        </motion.button>
      </section>
    </aside>
  );
}
