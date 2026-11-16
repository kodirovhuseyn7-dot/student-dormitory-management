-- Student Dormitory Management System (SDMS)
-- MySQL 8.0+
-- Database schema for the initial project version.

CREATE DATABASE IF NOT EXISTS student_dormitory
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE student_dormitory;

-- 1. User accounts and roles
CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name VARCHAR(150) NOT NULL,
    username VARCHAR(80) NOT NULL,
    email VARCHAR(150) NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'manager', 'student') NOT NULL DEFAULT 'student',
    phone VARCHAR(25) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_username (username),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB;

-- 2. Student profiles
CREATE TABLE IF NOT EXISTS students (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    student_number VARCHAR(40) NOT NULL,
    faculty VARCHAR(120) NOT NULL,
    study_group VARCHAR(50) NOT NULL,
    course TINYINT UNSIGNED NOT NULL,
    birth_date DATE NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_students_user_id (user_id),
    UNIQUE KEY uq_students_student_number (student_number),
    CONSTRAINT fk_students_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT chk_students_course CHECK (course BETWEEN 1 AND 6)
) ENGINE=InnoDB;

-- 3. Dormitory buildings
CREATE TABLE IF NOT EXISTS dormitories (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(120) NOT NULL,
    address VARCHAR(255) NOT NULL,
    total_floors TINYINT UNSIGNED NOT NULL,
    description TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB;

-- 4. Rooms in dormitories
CREATE TABLE IF NOT EXISTS rooms (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    dormitory_id BIGINT UNSIGNED NOT NULL,
    room_number VARCHAR(20) NOT NULL,
    floor TINYINT UNSIGNED NOT NULL,
    capacity TINYINT UNSIGNED NOT NULL,
    status ENUM('available', 'full', 'maintenance') NOT NULL DEFAULT 'available',
    PRIMARY KEY (id),
    UNIQUE KEY uq_rooms_dormitory_room (dormitory_id, room_number),
    KEY idx_rooms_dormitory_status (dormitory_id, status),
    CONSTRAINT fk_rooms_dormitory
        FOREIGN KEY (dormitory_id) REFERENCES dormitories(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_rooms_capacity CHECK (capacity > 0)
) ENGINE=InnoDB;

-- 5. Student applications for a dormitory place
CREATE TABLE IF NOT EXISTS applications (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id BIGINT UNSIGNED NOT NULL,
    dormitory_id BIGINT UNSIGNED NOT NULL,
    submitted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    comment TEXT NULL,
    reviewed_by BIGINT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_applications_student_status (student_id, status),
    KEY idx_applications_dormitory_status (dormitory_id, status),
    CONSTRAINT fk_applications_student
        FOREIGN KEY (student_id) REFERENCES students(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_applications_dormitory
        FOREIGN KEY (dormitory_id) REFERENCES dormitories(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_applications_reviewer
        FOREIGN KEY (reviewed_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

-- 6. Actual room assignments
CREATE TABLE IF NOT EXISTS allocations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id BIGINT UNSIGNED NOT NULL,
    room_id BIGINT UNSIGNED NOT NULL,
    application_id BIGINT UNSIGNED NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NULL,
    status ENUM('active', 'completed', 'cancelled') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_allocations_application (application_id),
    KEY idx_allocations_student_status (student_id, status),
    KEY idx_allocations_room_status (room_id, status),
    CONSTRAINT fk_allocations_student
        FOREIGN KEY (student_id) REFERENCES students(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_allocations_room
        FOREIGN KEY (room_id) REFERENCES rooms(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_allocations_application
        FOREIGN KEY (application_id) REFERENCES applications(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_allocations_dates CHECK (end_date IS NULL OR end_date >= start_date)
) ENGINE=InnoDB;

-- 7. Payments
CREATE TABLE IF NOT EXISTS payments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id BIGINT UNSIGNED NOT NULL,
    allocation_id BIGINT UNSIGNED NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    payment_period VARCHAR(30) NULL,
    status ENUM('pending', 'paid', 'overdue') NOT NULL DEFAULT 'pending',
    payment_method VARCHAR(40) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_payments_student_status (student_id, status),
    KEY idx_payments_allocation (allocation_id),
    CONSTRAINT fk_payments_student
        FOREIGN KEY (student_id) REFERENCES students(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_payments_allocation
        FOREIGN KEY (allocation_id) REFERENCES allocations(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT chk_payments_amount CHECK (amount > 0)
) ENGINE=InnoDB;

-- Notes:
-- 1. Store only password hashes, never plain-text passwords.
-- 2. Before creating an allocation, the Go backend must verify that the room
--    has available capacity. Perform the check and insert in a transaction.
-- 3. The backend should ensure that an application belongs to the same student
--    as the allocation being created and that the application is approved.
-- 4. This is the initial schema; update it through versioned migrations.
