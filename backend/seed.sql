
/* SQL seed data for the DoomsdayProject database inside MySQL */

INSERT INTO movies (title, release_year, runtime) VALUES
 ('Iron Man', 2008, 126),
 ('Captain America: The First Avenger', 2011, 124),
 ('The Avengers', 2012, 143),
 ('Captain America: Civil War', 2016, 147),
 ('Thor: Ragnarok', 2017, 130),
 ('Avengers: Infinity War', 2018, 149),
 ('Avengers: Endgame', 2019, 181),
 ('Avengers: Doomsday', 2026, 150);


INSERT INTO movie_prerequisites (movie_id, prerequisite_id) VALUES
 (3,1),(3,2),   -- Avengers needs Iron Man,First Avenger
 (4,3),         -- Civil War needs Avengers
 (6,4),(6,5),   -- Infinity War needs Civil War,Ragnarok
 (7,6),         -- Endgame needs Infinity War
 (8,7);         -- Doomsday needs Endgame

INSERT INTO cinemas (name) VALUES ('Cinema A'),('Cinema B'),('Cinema C'),('Cinema D');
INSERT INTO cinema_bundles (cinema_id, size) VALUES
 (1,1),(1,3),(1,4),
 (2,2),(2,5),
 (3,6),(3,9),(3,20),
 (4,4),(4,6);