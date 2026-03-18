CREATE TABLE advisor_context (
  id INT AUTO_INCREMENT PRIMARY KEY,
  section VARCHAR(50),
  field_key VARCHAR(100) UNIQUE,
  field_label VARCHAR(150),
  field_value TEXT,
  field_type VARCHAR(20),
  field_placeholder VARCHAR(255),
  sort_order INT DEFAULT 0,
  updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) UNIQUE,
  login_code VARCHAR(255),
  role VARCHAR(50),
  created_at DATETIME
);
