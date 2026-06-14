import { useEffect, useMemo, useState } from 'react';
import { motion } from 'framer-motion';
import Navbar from '../components/Navbar.jsx';
import Sidebar from '../components/Sidebar.jsx';
import ContestCard from '../components/ContestCard.jsx';
import HeroContest from '../components/HeroContest.jsx';
import CalendarWidget from '../components/CalendarWidget.jsx';
import { listMotion, pageMotion } from '../animations/motion.js';

const SAVE_KEY = 'code_arena_saved_contests_react';
const defaultPlatforms = ['codeforces', 'codechef', 'leetcode', 'atcoder', 'hackerrank'];

export default function ContestTracker() {
  const [contests, setContests] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [search, setSearch] = useState('');
  const [status, setStatus] = useState('');
  const [selectedPlatforms, setSelectedPlatforms] = useState(defaultPlatforms);
  const [savedOnly, setSavedOnly] = useState(false);
  const [savedIds, setSavedIds] = useState(() => readSavedIds());
  const [, setClock] = useState(Date.now());

  useEffect(() => {
    fetchContests(status);
  }, [status]);

  useEffect(() => {
    const timer = setInterval(() => setClock(Date.now()), 1000);
    return () => clearInterval(timer);
  }, []);

  async function fetchContests(nextStatus = '') {
    setLoading(true);
    setError('');
    try {
      const data = await fetchContestList(nextStatus);
      setContests(Array.isArray(data) ? data : []);
    } catch (err) {
      setError(err.message || 'Unable to load contests');
    } finally {
      setLoading(false);
    }
  }

  const decoratedContests = useMemo(
    () => contests.map((contest) => ({ ...contest, platform: inferPlatform(contest) })),
    [contests]
  );

  const filteredContests = useMemo(() => {
    const term = search.trim().toLowerCase();
    return decoratedContests.filter((contest) => {
      if (!selectedPlatforms.includes(contest.platform.key)) return false;
      if (savedOnly && !savedIds.includes(Number(contest.id))) return false;
      if (term) {
        const haystack = `${contest.title || ''} ${contest.author || ''} ${contest.status || ''} ${contest.platform.label}`.toLowerCase();
        if (!haystack.includes(term)) return false;
      }
      return true;
    });
  }, [decoratedContests, search, selectedPlatforms, savedIds, savedOnly]);

  const nextContest = useMemo(() => {
    return [...decoratedContests]
      .filter((contest) => contest.status !== 'ended')
      .sort((a, b) => {
        const aTime = a.status === 'active' ? new Date(a.end_time) : new Date(a.start_time);
        const bTime = b.status === 'active' ? new Date(b.end_time) : new Date(b.start_time);
        return aTime - bTime;
      })[0] || null;
  }, [decoratedContests]);

  const savedContests = useMemo(
    () => decoratedContests.filter((contest) => savedIds.includes(Number(contest.id))),
    [decoratedContests, savedIds]
  );

  const counts = useMemo(() => {
    const result = { all: decoratedContests.length, active: 0, upcoming: 0, ended: 0, saved: savedIds.length };
    decoratedContests.forEach((contest) => {
      if (result[contest.status] !== undefined) result[contest.status] += 1;
    });
    return result;
  }, [decoratedContests, savedIds.length]);

  function togglePlatform(platform) {
    setSelectedPlatforms((current) =>
      current.includes(platform) ? current.filter((item) => item !== platform) : [...current, platform]
    );
  }

  async function toggleSave(contestId) {
    const id = Number(contestId);
    const next = savedIds.includes(id) ? savedIds.filter((savedId) => savedId !== id) : [...savedIds, id];
    setSavedIds(next);
    localStorage.setItem(SAVE_KEY, JSON.stringify(next));

    try {
      await fetch('/api/contests/save.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ contest_id: id, saved: next.includes(id) }),
      });
    } catch (_) {
      // PHP save endpoint may not exist yet; localStorage keeps UX intact.
    }
  }

  return (
    <motion.div {...pageMotion} className="min-h-screen px-4 py-4 md:px-6">
      <Navbar search={search} onSearchChange={setSearch} />

      <div className="mx-auto mt-5 grid max-w-[1540px] grid-cols-1 gap-5 lg:grid-cols-[270px_minmax(0,1fr)] xl:grid-cols-[270px_minmax(0,1fr)_320px]">
        <Sidebar
          selectedPlatforms={selectedPlatforms}
          onPlatformToggle={togglePlatform}
          status={status}
          onStatusChange={setStatus}
          counts={counts}
          savedOnly={savedOnly}
          onSavedToggle={() => setSavedOnly((value) => !value)}
        />

        <main className="min-w-0">
          <HeroContest
            contest={nextContest}
            platform={nextContest?.platform}
            isSaved={nextContest ? savedIds.includes(Number(nextContest.id)) : false}
            onSave={toggleSave}
          />

          <div className="mt-6 flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
            <div>
              <h2 className="text-xl font-black tracking-tight text-white">
                {savedOnly ? 'Saved Contests' : statusTitle(status)}
              </h2>
              <p className="mt-1 text-sm text-gray-400">
                {loading ? 'Loading contest schedule' : `${filteredContests.length} visible from ${decoratedContests.length} contests`}
              </p>
            </div>
            <button
              type="button"
              onClick={() => fetchContests(status)}
              className="rounded-xl border border-gray-800 bg-white/[0.04] px-4 py-2 text-sm font-bold text-gray-200 transition hover:border-cyan-400/30 hover:bg-cyan-400/10"
            >
              Refresh
            </button>
          </div>

          {error ? (
            <div className="mt-4 rounded-2xl border border-red-400/30 bg-red-400/10 p-4 text-sm text-red-200">{error}</div>
          ) : null}

          {loading ? (
            <div className="mt-4 rounded-2xl border border-gray-800 bg-[#111827]/70 p-8 text-center text-gray-400">Loading contests...</div>
          ) : filteredContests.length ? (
            <motion.div variants={listMotion} initial="hidden" animate="show" className="mt-4 space-y-3">
              {filteredContests.map((contest) => (
                <ContestCard
                  key={contest.id}
                  contest={contest}
                  platform={contest.platform}
                  isSaved={savedIds.includes(Number(contest.id))}
                  onSave={toggleSave}
                />
              ))}
            </motion.div>
          ) : (
            <div className="mt-4 rounded-2xl border border-dashed border-gray-700 bg-white/[0.03] p-10 text-center">
              <div className="text-base font-bold text-white">No contests found</div>
              <div className="mt-2 text-sm text-gray-400">Try changing filters or search terms.</div>
            </div>
          )}
        </main>

        <div className="xl:block">
          <CalendarWidget
            contests={decoratedContests}
            nextContest={nextContest}
            savedContests={savedContests}
            onRemoveSaved={toggleSave}
          />
        </div>
      </div>
    </motion.div>
  );
}

async function fetchContestList(status) {
  const params = status ? `?status=${encodeURIComponent(status)}` : '';
  const endpoints = [`/api/contests/list.php${params}`, `/api/contests/index.php${params}`];

  let lastError = null;
  for (const endpoint of endpoints) {
    try {
      const response = await fetch(endpoint, { credentials: 'include' });
      if (!response.ok) {
        lastError = new Error(`Request failed: ${response.status}`);
        continue;
      }
      const json = await response.json();
      if (json.success) return json.data || [];
      lastError = new Error(json.message || 'Contest API failed');
    } catch (err) {
      lastError = err;
    }
  }
  throw lastError || new Error('Contest API failed');
}

function readSavedIds() {
  try {
    return JSON.parse(localStorage.getItem(SAVE_KEY) || '[]').map(Number);
  } catch (_) {
    return [];
  }
}

function inferPlatform(contest) {
  const title = String(contest.title || '').toLowerCase();
  if (title.includes('codechef') || title.includes('cook-off') || title.includes('lunchtime')) {
    return { key: 'codechef', label: 'CodeChef', short: 'CC', tone: 'bg-purple-400/10 text-purple-300' };
  }
  if (title.includes('leetcode') || title.includes('weekly contest') || title.includes('biweekly')) {
    return { key: 'leetcode', label: 'LeetCode', short: 'LC', tone: 'bg-yellow-400/10 text-yellow-300' };
  }
  if (title.includes('atcoder') || title.includes('abc') || title.includes('arc')) {
    return { key: 'atcoder', label: 'AtCoder', short: 'AT', tone: 'bg-cyan-400/10 text-cyan-300' };
  }
  if (title.includes('hackerrank')) {
    return { key: 'hackerrank', label: 'HackerRank', short: 'HR', tone: 'bg-green-400/10 text-green-300' };
  }
  return { key: 'codeforces', label: 'Codeforces', short: 'CF', tone: 'bg-red-400/10 text-red-300' };
}

function statusTitle(status) {
  return {
    '': 'All Contests',
    active: 'Live Contests',
    upcoming: 'Upcoming Contests',
    ended: 'Past Contests',
  }[status] || 'All Contests';
}
