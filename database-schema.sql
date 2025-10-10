CREATE TABLE djs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(64) UNIQUE NOT NULL,
  name VARCHAR(120) NOT NULL,
  genre VARCHAR(200) NOT NULL,
  location_city VARCHAR(120),
  location_province VARCHAR(120),
  bio TEXT,
  technical_rider TEXT,
  press_kit_url VARCHAR(255),
  contact_email VARCHAR(255),
  sc_url VARCHAR(255),
  yt_url VARCHAR(255),
  ig_url VARCHAR(255),
  sc_embed TEXT,
  yt_embed TEXT,
  image_path VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_name ON djs(name);
CREATE INDEX idx_genre ON djs(genre);
CREATE INDEX idx_location ON djs(location_province, location_city);
