-- init.sql
-- Create database and users table for simple login flow.

CREATE DATABASE IF NOT EXISTS amd_login CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE amd_login;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
