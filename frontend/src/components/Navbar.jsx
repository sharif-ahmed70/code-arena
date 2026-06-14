import { motion } from 'framer-motion';
import { buttonTap } from '../animations/motion.js';

export default function Navbar({ search, onSearchChange }) {
  return (
    <header className="sticky top-4 z-40 mx-auto grid max-w-[1540px] grid-cols-1 gap-3 rounded-2xl border border-gray-800/80 bg-[#0B0F17]/75 p-3 shadow-2xl backdrop-blur-xl lg:grid-cols-[260px_1fr_420px]">
      <a href="/code-arena/index.php" className="flex min-w-0 items-center gap-3">
        <div className="grid h-11 w-11 place-items-center rounded-xl border border-white/10 bg-gradient-to-br from-green-400/20 to-cyan-400/20 font-black text-white">
          CA
        </div>
        <div className="min-w-0">
          <div className="truncate text-base font-black text-white">
            Code <span className="text-green-400">Arena</span>
          </div>
          <div className="truncate text-xs font-medium text-gray-400">Contest Tracker</div>
        </div>
      </a>

      <nav className="flex items-center gap-1 overflow-x-auto lg:justify-center">
        {[
          ['Dashboard', '/code-arena/dashboard.php'],
          ['Problems', '/code-arena/problems.php'],
          ['Contests', '/code-arena/contests.php'],
          ['Leaderboard', '/code-arena/leaderboard.php'],
          ['Submissions', '/code-arena/review.php'],
        ].map(([label, href]) => (
          <a
            key={label}
            href={href}
            className={`rounded-xl px-3 py-2 text-sm font-semibold transition hover:bg-white/10 hover:text-white ${
              label === 'Contests' ? 'bg-white/10 text-white' : 'text-gray-400'
            }`}
          >
            {label}
          </a>
        ))}
      </nav>

      <div className="grid grid-cols-[1fr_42px_auto] items-center gap-2">
        <label className="flex h-11 min-w-0 items-center gap-3 rounded-xl border border-gray-800 bg-white/[0.04] px-3">
          <span className="relative h-3.5 w-3.5 rounded-full border-2 border-gray-500 after:absolute after:-bottom-1 after:-right-1 after:h-1.5 after:w-2 after:rotate-45 after:rounded after:bg-gray-500" />
          <input
            value={search}
            onChange={(event) => onSearchChange(event.target.value)}
            className="min-w-0 flex-1 bg-transparent text-sm text-white outline-none placeholder:text-gray-500"
            placeholder="Search contests"
          />
        </label>

        <motion.button
          {...buttonTap}
          type="button"
          className="grid h-11 w-11 place-items-center rounded-xl border border-gray-800 bg-white/[0.04] text-gray-300 transition hover:border-cyan-400/40 hover:bg-cyan-400/10 hover:text-white"
          aria-label="Notifications"
        >
          <span className="relative h-4 w-3.5 rounded-t-full border-2 border-current border-b-0 before:absolute before:-top-1 before:left-1/2 before:h-1 before:w-1 before:-translate-x-1/2 before:rounded-full before:bg-current after:absolute after:-bottom-1 after:left-0 after:right-0 after:mx-auto after:h-0.5 after:w-2 after:rounded after:bg-current" />
        </motion.button>

        <div className="group relative">
          <motion.button
            {...buttonTap}
            type="button"
            className="flex h-11 items-center gap-2 rounded-xl border border-gray-800 bg-white/[0.04] px-2 text-sm font-semibold text-white transition hover:bg-white/10"
          >
            <span className="grid h-7 w-7 place-items-center rounded-lg bg-green-400/15 text-xs font-black text-green-400">U</span>
            <span className="hidden sm:inline">Profile</span>
          </motion.button>
          <div className="invisible absolute right-0 mt-2 w-44 rounded-xl border border-gray-800 bg-[#111827] p-2 opacity-0 shadow-2xl transition group-hover:visible group-hover:opacity-100">
            <a className="block rounded-lg px-3 py-2 text-sm text-gray-300 hover:bg-white/10 hover:text-white" href="/code-arena/profile.php">
              Profile
            </a>
            <a className="block rounded-lg px-3 py-2 text-sm text-gray-300 hover:bg-white/10 hover:text-white" href="/code-arena/dashboard.php">
              Dashboard
            </a>
          </div>
        </div>
      </div>
    </header>
  );
}
