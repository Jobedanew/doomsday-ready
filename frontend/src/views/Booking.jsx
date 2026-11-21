import { useEffect, useState } from 'react';
import { getCinemas, getBooking } from '../api';

export default function Booking() {
  const [cinemas, setCinemas] = useState([]);
  const [cinemaId, setCinemaId] = useState('');
  const [groupSize, setGroupSize] = useState('');
  const [result, setResult] = useState(null);
  const [error, setError] = useState('');

  useEffect(() => {
    getCinemas().then(setCinemas).catch((e) => setError(e.message));
  }, []);

  const handleQuote = async (e) => {
    e.preventDefault();
    setError('');
    setResult(null);
    try {
      setResult(await getBooking(cinemaId, groupSize));
    } catch (err) {
      setError(err.message);
    }
  };

  return (
    <div>
      <h2>Watch-party booking</h2>
      {error && <p className="error">{error}</p>}

        <form onSubmit={handleQuote} className="card booking-form">
        <select value={cinemaId} onChange={(e) => setCinemaId(e.target.value)} required>
            <option value="">Pick a cinema…</option>
            {cinemas.map((c) => (
            <option key={c.id} value={c.id}>
                {c.name} (bundles: {c.bundleSizes.join(', ')})
            </option>
            ))}
        </select>
        <input type="number" min="1" max="10000" placeholder="Group size"
            value={groupSize} onChange={(e) => setGroupSize(e.target.value)} required />
        <button type="submit">Get quote</button>
        </form>

      {result && (
        <div className="card">
          {result.possible ? (
            <>
              <p><strong>{result.totalBundles} bundles</strong> for {result.groupSize} people:</p>
              <ul>
                {result.bundles.map((b) => (
                  <li key={b.size}>{b.size} seats × {b.count}</li>
                ))}
              </ul>
            </>
          ) : (
            <p>Not possible: no exact combination for {result.groupSize} seats.</p>
          )}
        </div>
      )}
    </div>
  );
}