-- Student Dormitory Management System (SDMS)
-- Useful SQL queries for MySQL 8.0+
-- Database: student_dormitory

USE student_dormitory;
SET NAMES utf8mb4;

-- =========================================================
-- 1. BASIC SELECT QUERIES
-- =========================================================

-- Q1. Show all users (do not expose password hashes in application screens).
SELECT id, full_name, username, email, role, phone, created_at
FROM users
ORDER BY id;

-- Q2. Show all students with their account information.
SELECT s.id AS student_id, s.student_number, u.full_name,
       s.faculty, s.study_group, s.course
FROM students AS s
JOIN users AS u ON u.id = s.user_id
ORDER BY s.id;

-- Q3. Show all dormitories.
SELECT id, name, address, total_floors, description
FROM dormitories
ORDER BY name;

-- Q4. Show rooms with their dormitory names.
SELECT r.id AS room_id, d.name AS dormitory,
       r.room_number, r.floor, r.capacity, r.status
FROM rooms AS r
JOIN dormitories AS d ON d.id = r.dormitory_id
ORDER BY d.id, r.floor, r.room_number;

-- Q5. Find rooms that are marked as available.
SELECT d.name AS dormitory, r.room_number, r.floor, r.capacity
FROM rooms AS r
JOIN dormitories AS d ON d.id = r.dormitory_id
WHERE r.status = 'available'
ORDER BY d.name, r.floor, r.room_number;

-- =========================================================
-- 2. SEARCH AND FILTER
-- =========================================================

-- Q6. Search for a student by name or student number.
SELECT s.id, s.student_number, u.full_name,
       s.faculty, s.study_group, s.course
FROM students AS s
JOIN users AS u ON u.id = s.user_id
WHERE u.full_name LIKE '%Ahmad%'
   OR s.student_number = 'ST-2026-001';

-- Q7. Find students from a particular faculty.
SELECT s.student_number, u.full_name, s.faculty, s.study_group, s.course
FROM students AS s
JOIN users AS u ON u.id = s.user_id
WHERE s.faculty = 'Faculty of Information Technology'
ORDER BY u.full_name;

-- Q8. Show applications by status.
SELECT a.id, u.full_name, d.name AS dormitory,
       a.submitted_at, a.status, a.comment
FROM applications AS a
JOIN students AS s ON s.id = a.student_id
JOIN users AS u ON u.id = s.user_id
JOIN dormitories AS d ON d.id = a.dormitory_id
WHERE a.status = 'pending'
ORDER BY a.submitted_at;

-- =========================================================
-- 3. JOIN QUERIES
-- =========================================================

-- Q9. Show current and past room allocations with student and room details.
SELECT al.id AS allocation_id, u.full_name, s.student_number,
       d.name AS dormitory, r.room_number,
       al.start_date, al.end_date, al.status
FROM allocations AS al
JOIN students AS s ON s.id = al.student_id
JOIN users AS u ON u.id = s.user_id
JOIN rooms AS r ON r.id = al.room_id
JOIN dormitories AS d ON d.id = r.dormitory_id
ORDER BY al.start_date DESC;

-- Q10. Show payments with student names and allocation information.
SELECT p.id AS payment_id, u.full_name, p.amount,
       p.payment_date, p.payment_period, p.status,
       p.payment_method, al.room_id
FROM payments AS p
JOIN students AS s ON s.id = p.student_id
JOIN users AS u ON u.id = s.user_id
LEFT JOIN allocations AS al ON al.id = p.allocation_id
ORDER BY p.payment_date DESC;

-- Q11. Show each application and the manager who reviewed it.
SELECT a.id, student_user.full_name AS student,
       d.name AS dormitory, a.status,
       reviewer.full_name AS reviewed_by, a.reviewed_at
FROM applications AS a
JOIN students AS s ON s.id = a.student_id
JOIN users AS student_user ON student_user.id = s.user_id
JOIN dormitories AS d ON d.id = a.dormitory_id
LEFT JOIN users AS reviewer ON reviewer.id = a.reviewed_by
ORDER BY a.id;

-- =========================================================
-- 4. AGGREGATION AND GROUP BY
-- =========================================================

-- Q12. Count students by faculty.
SELECT faculty, COUNT(*) AS student_count
FROM students
GROUP BY faculty
ORDER BY student_count DESC;

-- Q13. Count rooms in each dormitory.
SELECT d.id, d.name, COUNT(r.id) AS room_count
FROM dormitories AS d
LEFT JOIN rooms AS r ON r.dormitory_id = d.id
GROUP BY d.id, d.name
ORDER BY d.id;

-- Q14. Count applications by status.
SELECT status, COUNT(*) AS application_count
FROM applications
GROUP BY status
ORDER BY application_count DESC;

-- Q15. Calculate total payments by status.
SELECT status, SUM(amount) AS total_amount, COUNT(*) AS payment_count
FROM payments
GROUP BY status
ORDER BY status;

-- Q16. Calculate total paid amount for each student.
SELECT s.student_number, u.full_name,
       COALESCE(SUM(CASE WHEN p.status = 'paid' THEN p.amount ELSE 0 END), 0)
           AS total_paid
FROM students AS s
JOIN users AS u ON u.id = s.user_id
LEFT JOIN payments AS p ON p.student_id = s.id
GROUP BY s.id, s.student_number, u.full_name
ORDER BY total_paid DESC;

-- Q17. Find rooms whose active allocations reach their capacity.
-- This query compares active allocations with the room's capacity.
SELECT r.id, d.name AS dormitory, r.room_number,
       r.capacity, COUNT(al.id) AS active_allocations
FROM rooms AS r
JOIN dormitories AS d ON d.id = r.dormitory_id
LEFT JOIN allocations AS al
       ON al.room_id = r.id AND al.status = 'active'
GROUP BY r.id, d.name, r.room_number, r.capacity
HAVING COUNT(al.id) >= r.capacity
ORDER BY d.name, r.room_number;

-- =========================================================
-- 5. SUBQUERIES
-- =========================================================

-- Q18. Students who have at least one active allocation.
SELECT s.id, s.student_number, u.full_name
FROM students AS s
JOIN users AS u ON u.id = s.user_id
WHERE EXISTS (
    SELECT 1
    FROM allocations AS al
    WHERE al.student_id = s.id
      AND al.status = 'active'
);

-- Q19. Dormitories that have no rooms.
SELECT d.id, d.name
FROM dormitories AS d
WHERE NOT EXISTS (
    SELECT 1
    FROM rooms AS r
    WHERE r.dormitory_id = d.id
);

-- =========================================================
-- 6. DATA MODIFICATION EXAMPLES
-- Run UPDATE/DELETE examples only when you intend to change data.
-- =========================================================

-- Q20. Approve a pending application (replace 3 with the target ID).
-- UPDATE applications
-- SET status = 'approved',
--     reviewed_by = 2,
--     reviewed_at = NOW()
-- WHERE id = 3 AND status = 'pending';

-- Q21. Change a room's status (replace 1 with the target room ID).
-- UPDATE rooms
-- SET status = 'maintenance'
-- WHERE id = 1;

-- Q22. Mark a payment as paid (replace 4 with the target payment ID).
-- UPDATE payments
-- SET status = 'paid', payment_date = CURDATE()
-- WHERE id = 4 AND status IN ('pending', 'overdue');

-- Q23. Delete a pending application (replace 3 with the target ID).
-- This will fail if an allocation references the application.
-- DELETE FROM applications
-- WHERE id = 3 AND status = 'pending';

-- =========================================================
-- 7. TRANSACTION EXAMPLE
-- Illustrative only. Backend must also check room capacity,
-- application ownership, and approved status before allocation.
-- =========================================================

-- START TRANSACTION;
-- SELECT id, capacity
-- FROM rooms
-- WHERE id = 1 AND status = 'available'
-- FOR UPDATE;
--
-- INSERT INTO allocations
--     (student_id, room_id, application_id, start_date, status)
-- VALUES
--     (1, 1, 1, CURDATE(), 'active');
--
-- COMMIT;
-- If any step fails, use ROLLBACK instead of COMMIT.
