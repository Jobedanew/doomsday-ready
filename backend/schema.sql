/* SQL schema for the DoomsdayProject database inside MySQL */

DROP TABLE IF EXISTS cinema_bundles;
DROP TABLE IF EXISTS cinemas;
DROP TABLE IF EXISTS movie_prerequisites;
DROP TABLE IF EXISTS movies;

CREATE TABLE movies (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL UNIQUE,
  release_year INT NOT NULL,
  runtime INT NOT NULL,
  watched TINYINT(1) NOT NULL DEFAULT 0
);

CREATE TABLE movie_prerequisites (
  movie_id INT NOT NULL,
  prerequisite_id INT NOT NULL,
  PRIMARY KEY (movie_id, prerequisite_id),
  FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
  FOREIGN KEY (prerequisite_id) REFERENCES movies(id) ON DELETE CASCADE
);

CREATE TABLE cinemas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL UNIQUE
);

CREATE TABLE cinema_bundles (
  cinema_id INT NOT NULL,
  size INT NOT NULL,
  PRIMARY KEY (cinema_id, size),
  FOREIGN KEY (cinema_id) REFERENCES cinemas(id) ON DELETE CASCADE
);