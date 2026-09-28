# Student Management System (SMS)

A comprehensive, production-ready Student Management System built with PHP, MySQL, Bootstrap 5, and JavaScript.

## 🚀 Features

### Student Management
- Add, Edit, Delete, View Students
- Upload student photos
- Search and filter students
- Pagination support

### Teacher Management
- Complete teacher CRUD operations
- Subject assignment
- Contact management

### Attendance System
- Daily attendance marking
- Multiple status options (Present, Absent, Late, Half-Day)
- Class-wise attendance
- Date-wise filtering

### Results Management
- Add exam results
- Calculate grades automatically
- Subject-wise marks entry
- Exam type categorization

### Fee Management
- Fee record creation
- Payment tracking
- Multiple payment methods
- Pending/Paid/Partial status
- Due date management

### Assignments
- Create and manage assignments
- File attachments support
- Class-wise assignment distribution
- Due date tracking

## 📋 Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache Server (XAMPP/WAMP/LAMP)
- Modern web browser

## 🔧 Installation

### Step 1: Download and Extract
Extract all files to your web server directory (e.g., `htdocs` for XAMPP)

### Step 2: Database Setup
1. Open phpMyAdmin
2. Create a new database named `student_management_system`
3. Import the provided SQL file OR run the database creation script from STEP 4

### Step 3: Configuration
1. Open `config/database.php`
2. Update database credentials if needed:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'student_management_system');