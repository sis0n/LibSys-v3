ALTER TABLE users MODIFY COLUMN role ENUM('superadmin', 'admin', 'campus_admin', 'librarian', 'scanner', 'student', 'faculty', 'staff')
DEFAULT NULL;