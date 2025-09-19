CREATE DATABASE IF NOT EXISTS lizaapp_bd;
USE lizaapp_bd;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone VARCHAR(15) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    contact_id INT NOT NULL,
    status ENUM('pending', 'accepted') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (contact_id) REFERENCES users(id),
    UNIQUE KEY unique_contact (user_id, contact_id)
);

CREATE TABLE calls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    caller_id INT NOT NULL,
    receiver_id INT NOT NULL,
    status ENUM('ringing', 'answered', 'ended', 'missed') DEFAULT 'ringing',
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ended_at TIMESTAMP NULL,
    FOREIGN KEY (caller_id) REFERENCES users(id),
    FOREIGN KEY (receiver_id) REFERENCES users(id)
);
