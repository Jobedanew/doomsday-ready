async function request(path, options = {}) {
  const res = await fetch('/api' + path, {
    headers: { 'Content-Type': 'application/json' },
    ...options,
  });
  const data = await res.json().catch(() => null);
  if (!res.ok) {
    throw new Error(data?.message || data?.error || 'Request failed');
  }
  return data;
}

export const getMovies = () => request('/movies');
export const createMovie = (body) =>
  request('/movies', { method: 'POST', body: JSON.stringify(body) });
export const updateMovie = (id, body) =>
  request(`/movies/${id}`, { method: 'PATCH', body: JSON.stringify(body) });
export const getRewatchOrder = (id) => request(`/movies/${id}/rewatch-order`);

export const getCinemas = () => request('/cinemas');
export const getBooking = (id, groupSize) =>
  request(`/cinemas/${id}/booking?groupSize=${groupSize}`);