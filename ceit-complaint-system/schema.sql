-- ============================================================
-- CEIT Web-Based Complaint and Inquiry Management System
-- Database Schema for MySQL / MariaDB (XAMPP)
-- ============================================================

CREATE DATABASE IF NOT EXISTS ceit_complaints;
USE ceit_complaints;

-- ------------------------------------------------------------
-- USERS
-- role: student | admin | sysadmin | adviser
--
-- Students log in with their CvSU email (`email` column).
-- Admins / Advisers / System Admins log in with an Employee ID
-- (`employee_id` column) instead of an email address.
-- Exactly one of (email, employee_id) is expected to be set,
-- depending on role — enforced in application code, not by a
-- DB constraint, for compatibility with older MySQL/MariaDB.
-- ------------------------------------------------------------
CREATE TABLE users (
    user_id        INT AUTO_INCREMENT PRIMARY KEY,
    first_name     VARCHAR(100) NOT NULL,
    last_name      VARCHAR(100) NOT NULL,
    email          VARCHAR(150) NULL UNIQUE,           -- students: CvSU email
    employee_id    VARCHAR(50)  NULL UNIQUE,           -- staff: Employee ID
    department     VARCHAR(100) NULL,                  -- students: college/program
    year_level     VARCHAR(20)  NULL,                  -- students: 1st Year..4th Year
    avatar_path    VARCHAR(255) NULL,                  -- profile picture
    password_hash  VARCHAR(255) NOT NULL,
    role           ENUM('student','admin','sysadmin','adviser') NOT NULL DEFAULT 'student',
    status         ENUM('active','disabled') NOT NULL DEFAULT 'active',
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- CASES  (a case is either a Complaint or an Inquiry)
-- assigned_to          -> the admin/adviser currently handling it
-- suggested_adviser_id -> optionally picked by the student at submit time
-- ------------------------------------------------------------
CREATE TABLE cases (
    case_id             INT AUTO_INCREMENT PRIMARY KEY,
    case_code           VARCHAR(20) NOT NULL UNIQUE,        -- e.g. CMP-0001 / INQ-0001
    user_id             INT NOT NULL,                       -- submitter (student)
    type                ENUM('complaint','inquiry') NOT NULL,
    title               VARCHAR(200) NOT NULL,
    category            VARCHAR(100) NOT NULL,
    priority            ENUM('Low','Medium','High') NOT NULL DEFAULT 'Medium',
    description         TEXT NOT NULL,
    is_anonymous        TINYINT(1) NOT NULL DEFAULT 0,
    status              ENUM('Submitted','Under Review','In Progress','Resolved') NOT NULL DEFAULT 'Submitted',
    assigned_to         INT NULL,
    suggested_adviser_id INT NULL,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (suggested_adviser_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- ATTACHMENTS
-- ------------------------------------------------------------
CREATE TABLE attachments (
    attachment_id  INT AUTO_INCREMENT PRIMARY KEY,
    case_id        INT NOT NULL,
    file_name      VARCHAR(255) NOT NULL,
    file_path      VARCHAR(255) NOT NULL,
    uploaded_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (case_id) REFERENCES cases(case_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- STATUS HISTORY  (drives the progress stepper on the case page)
-- ------------------------------------------------------------
CREATE TABLE status_history (
    history_id     INT AUTO_INCREMENT PRIMARY KEY,
    case_id        INT NOT NULL,
    status         ENUM('Submitted','Under Review','In Progress','Resolved') NOT NULL,
    changed_by     INT NULL,
    remarks        VARCHAR(255) NULL,
    changed_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (case_id) REFERENCES cases(case_id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- MESSAGES  (group conversation thread: student + adviser + admin)
-- ------------------------------------------------------------
CREATE TABLE messages (
    message_id     INT AUTO_INCREMENT PRIMARY KEY,
    case_id        INT NOT NULL,
    sender_id      INT NOT NULL,
    message        TEXT NOT NULL,
    sent_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (case_id) REFERENCES cases(case_id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- NOTIFICATIONS
-- ------------------------------------------------------------
CREATE TABLE notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id          INT NOT NULL,
    case_id          INT NULL,
    message          VARCHAR(255) NOT NULL,
    is_read          TINYINT(1) NOT NULL DEFAULT 0,
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (case_id) REFERENCES cases(case_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- No seed accounts are inserted here on purpose: password hashes
-- must be generated by PHP's password_hash() function, not written
-- by hand into SQL. Just open the site and use "Create Account" —
-- pick "Admin" to create your first admin/staff account (Employee ID
-- login), or "Student" for a student account (CvSU email login).
-- Adviser accounts are created afterwards by an Admin from the
-- Team page — there is no public "Adviser" option on registration.
-- ------------------------------------------------------------

-- ------------------------------------------------------------
-- MIGRATION NOTE (if you already imported the old schema and don't
-- want to drop your database): run this instead of the CREATE TABLE
-- statements above to upgrade an existing installation in place.
-- ------------------------------------------------------------
-- ALTER TABLE users
--     MODIFY email VARCHAR(150) NULL,
--     ADD COLUMN employee_id VARCHAR(50) NULL UNIQUE AFTER email,
--     ADD COLUMN department VARCHAR(100) NULL AFTER employee_id,
--     ADD COLUMN year_level VARCHAR(20) NULL AFTER department,
--     ADD COLUMN avatar_path VARCHAR(255) NULL AFTER year_level,
--     MODIFY role ENUM('student','admin','sysadmin','adviser') NOT NULL DEFAULT 'student';
-- ALTER TABLE cases
--     ADD COLUMN suggested_adviser_id INT NULL AFTER assigned_to,
--     ADD CONSTRAINT fk_suggested_adviser FOREIGN KEY (suggested_adviser_id) REFERENCES users(user_id) ON DELETE SET NULL;
