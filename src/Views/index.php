ALTER TABLE users MODIFY COLUMN role ENUM('superadmin', 'admin', 'librarian', 'scanner', 'student', 'faculty', 'staff')
DEFAULT NULL;