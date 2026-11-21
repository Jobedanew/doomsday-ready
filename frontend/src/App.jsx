import { useState } from 'react';
import Movies from './views/Movies';
import RewatchPlan from './views/RewatchPlan';
import Booking from './views/Booking';
import './App.css';

const TABS = ['Movies', 'Rewatch Plan', 'Booking'];

export default function App() {
  const [tab, setTab] = useState('Movies');

  return (
    <div className="app">
      <h1>Doomsday Ready</h1>
      <nav>
        {TABS.map((t) => (
          <button key={t} className={t === tab ? 'active' : ''} onClick={() => setTab(t)}>
            {t}
          </button>
        ))}
      </nav>
      {tab === 'Movies' && <Movies />}
      {tab === 'Rewatch Plan' && <RewatchPlan />}
      {tab === 'Booking' && <Booking />}
    </div>
  );
}