import { useEffect, useState } from 'react';
import { getMovies, getRewatchOrder } from '../api';

function formatMinutes(total) {
  const h = Math.floor(total / 60);
  const m = total % 60;
  return `${h}h ${m}m`;
}

export default function RewatchPlan() {
  const [movies, setMovies] = useState([]);
  const [targetId, setTargetId] = useState('');
  const [plan, setPlan] = useState(null);
  const [error, setError] = useState('');

  useEffect(() => {
    getMovies().then(setMovies).catch((e) => setError(e.message));
  }, []);

  useEffect(() => {
    if (!targetId) { setPlan(null); return; }
    setError('');
    getRewatchOrder(targetId).then(setPlan).catch((e) => setError(e.message));
  }, [targetId]);

  const unwatched = plan?.order.filter((m) => !m.watched) ?? [];
  const watched = plan?.order.filter((m) => m.watched) ?? [];

  return (
    <div>
      <h2>Rewatch plan</h2>
      {error && <p className="error">{error}</p>}

      <div className="card">
        <select value={targetId} onChange={(e) => setTargetId(e.target.value)}>
            <option value="">Pick a target movie…</option>
            {movies.map((m) => <option key={m.id} value={m.id}>{m.title}</option>)}
        </select>
       </div>

      {plan && (
        <div className="card">
          {plan.totalCount === 0 ? (
            <p>Nothing to watch first. You are ready!</p>
          ) : (
            <>
              <p>{plan.watchedCount} of {plan.totalCount} watched</p>
              <progress value={plan.watchedCount} max={plan.totalCount} />
              <p>Remaining watch time: {formatMinutes(plan.remainingMinutes)}</p>

                <h3 className="plan-title unwatched-title">Up Next (Unwatched)</h3>
                {unwatched.length === 0 ? (
                <p className="plan-empty">All prerequisite movies watched!</p>
                ) : (
                <ol className="plan-list unwatched-list">
                    {unwatched.map((m) => (
                    <li key={m.id}>
                        {m.title} ({m.runtime} min)
                    </li>
                    ))}
                </ol>
                )}

                <h3 className="plan-title watched-title">Watched</h3>
                {watched.length === 0 ? (
                <p className="plan-empty">None watched yet.</p>
                ) : (
                <ol className="plan-list watched-list">
                    {watched.map((m) => (
                    <li key={m.id}>
                        {m.title} ({m.runtime} min)
                    </li>
                    ))}
                </ol>
                )}
            </>
          )}
        </div>
      )}
    </div>
  );
}