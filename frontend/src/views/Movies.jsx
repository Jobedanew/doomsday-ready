import { useEffect, useState} from 'react';
import { getMovies, createMovie, updateMovie } from '../api';

function PrereqPicker({ movies, selfId, selected, onChange, readOnly = false }) {
  const toggle = (id) =>
    onChange(selected.includes(id) ? selected.filter((x) => x !== id) : [...selected, id]);

  return (
    <div className="picker">
      {movies
        .filter((m) => m.id !== selfId)
        .map((m) => (
          <label key={m.id}>
            {readOnly ? (
              <span className="dot">●</span>
            ) : (
              <input type="checkbox" checked={selected.includes(m.id)} onChange={() => toggle(m.id)} />
            )}
            {m.title}
          </label>
        ))}
    </div>
  );
}


export default function Movies() {
  const [movies, setMovies] = useState([]);
  const [error, setError] = useState('');
  const [editError, setEditError] = useState(''); 
  const [addError, setAddError] = useState('');
  const [form, setForm] = useState({ title: '', releaseYear: '', runtime: '', prerequisites: [] });
  const [editingId, setEditingId] = useState(null);
  const [editPrereqs, setEditPrereqs] = useState([]);

  const load = () => getMovies().then(setMovies).catch((e) => setError(e.message));
  useEffect(() => { load(); }, []);

  const handleAdd = async (e) => {
    e.preventDefault();
    setAddError('');
    try {
        await createMovie({
        title: form.title,
        releaseYear: Number(form.releaseYear),
        runtime: Number(form.runtime),
        prerequisites: form.prerequisites,
        });
        setForm({ title: '', releaseYear: '', runtime: '', prerequisites: [] });
        load();
    } catch (err) {
        setAddError(err.message);     
    }
  };

  const toggleWatched = async (m) => {
    setError('');
    try {
      await updateMovie(m.id, { watched: !m.watched });
      load();
    } catch (err) {
      setError(err.message);
    }
  };

  const startEdit = (m) => {
  setEditingId(m.id);
  setEditPrereqs(m.prerequisites);
  setEditError('');                  
};

const saveEdit = async (id) => {
  setEditError('');
  try {
    await updateMovie(id, { prerequisites: editPrereqs });
    setEditingId(null);
    load();
  } catch (err) {
    setEditError(err.message);       
  }
};

const cancelEdit = () => {
  setEditingId(null);
  setEditError('');
};

  const titleOf = (id) => movies.find((m) => m.id === id)?.title ?? id;

  return (
    <div>
      {error && <p className="error">{error}</p>}

      <h2>Add a movie</h2>
        <form onSubmit={handleAdd} className="card">
        <input placeholder="Title" value={form.title}
            onChange={(e) => setForm({ ...form, title: e.target.value })} required />
        <input type="number" placeholder="Release year" value={form.releaseYear}
            onChange={(e) => setForm({ ...form, releaseYear: e.target.value })} required />
        <input type="number" placeholder="Runtime (minutes)" value={form.runtime}
            onChange={(e) => setForm({ ...form, runtime: e.target.value })} required />
        <p>Watch these first (optional)!</p>
        <PrereqPicker movies={movies} readOnly />
        {addError && <p className="error">{addError}</p>}
        <button type="submit">Add movie</button>
        </form>

      <h2>Movies</h2>
      {movies.map((m) => (
        <div key={m.id} className="card">
          <label>
            <input type="checkbox" checked={m.watched} onChange={() => toggleWatched(m)} />
            <strong>{m.title}</strong> ({m.releaseYear}, {m.runtime} min)
          </label>
          <p>
            Prerequisites:{' '}
            {m.prerequisites.length ? m.prerequisites.map(titleOf).join(', ') : 'none'}
          </p>
          {editingId === m.id ? (
            <>
                <PrereqPicker movies={movies} selfId={m.id} selected={editPrereqs} onChange={setEditPrereqs} />
                {editError && <p className="error">{editError}</p>}
                <button onClick={() => saveEdit(m.id)}>Save</button>{' '}
                <button onClick={cancelEdit}>Cancel</button>
            </>
            ) : (
            <button onClick={() => startEdit(m)}>Edit prerequisites</button>
            )}
        </div>
      ))}
    </div>
  );
}