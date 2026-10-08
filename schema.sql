-- Import this file while the database if0_42700284_panaghiusa is selected in phpMyAdmin.
-- InfinityFree creates the database for you, so do not use CREATE DATABASE or USE here.

CREATE TABLE IF NOT EXISTS teams (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(40) NOT NULL,
  color CHAR(7) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  weight DECIMAL(5,2) NOT NULL DEFAULT 10.00,
  max_score DECIMAL(6,2) NOT NULL DEFAULT 100.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS scores (
  event_id INT NOT NULL,
  team_id INT NOT NULL,
  score DECIMAL(6,2) NOT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (event_id, team_id),
  CONSTRAINT fk_scores_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
  CONSTRAINT fk_scores_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO teams (name, color)
SELECT 'Group 1 Sky Blue', '#bfe3f0' WHERE NOT EXISTS (SELECT 1 FROM teams WHERE name='Group 1 Sky Blue');
INSERT INTO teams (name, color)
SELECT 'Group 2 Yellow', '#ffee00' WHERE NOT EXISTS (SELECT 1 FROM teams WHERE name='Group 2 Yellow');
INSERT INTO teams (name, color)
SELECT 'Group 3 Red', '#ffbdbd' WHERE NOT EXISTS (SELECT 1 FROM teams WHERE name='Group 3 Red');
INSERT INTO teams (name, color)
SELECT 'Group 4 Orange', '#ff8c42' WHERE NOT EXISTS (SELECT 1 FROM teams WHERE name='Group 4 Orange');
INSERT INTO teams (name, color)
SELECT 'Group 5 Peach', '#ffdcc8' WHERE NOT EXISTS (SELECT 1 FROM teams WHERE name='Group 5 Peach');
INSERT INTO teams (name, color)
SELECT 'Group 6 Violet', '#b9a2f5' WHERE NOT EXISTS (SELECT 1 FROM teams WHERE name='Group 6 Violet');
INSERT INTO teams (name, color)
SELECT 'Group 7 Black', '#dcdcdc' WHERE NOT EXISTS (SELECT 1 FROM teams WHERE name='Group 7 Black');
INSERT INTO teams (name, color)
SELECT 'Group 8 Army Green', '#9acd4f' WHERE NOT EXISTS (SELECT 1 FROM teams WHERE name='Group 8 Army Green');
