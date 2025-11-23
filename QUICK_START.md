# 🚀 Quick Start Guide for Collaborators

Follow these steps to get the project running on your machine.

## Step 1: Clone the Repository

```bash
git clone https://github.com/Shin-da/student-monitoring-system.git
cd student-monitoring-system
```

## Step 2: Install PHP Dependencies

```bash
composer install
```

## Step 3: Configure Your Local Settings

**Windows (PowerShell):**
```powershell
Copy-Item config\config.example.php config\config.php
```

**Mac/Linux:**
```bash
cp config/config.example.php config/config.php
```

**Then edit `config/config.php`** and update:
- `database` name (if different)
- `username` and `password` for your MySQL
- `base_url` if your project is in a subfolder

## Step 4: Set Up Database

1. **Start XAMPP** (Apache + MySQL)
2. **Open phpMyAdmin**: http://localhost/phpmyadmin
3. **Create Database**:
   ```sql
   CREATE DATABASE student_monitoring CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
4. **Run Setup Scripts**:
   ```bash
   php database/update_schema.php
   php database/init_admin.php
   ```

## Step 5: Install Frontend Dependencies (Optional)

```bash
npm install
```

## Step 6: Access the Application

- **URL**: `http://localhost/student-monitoring-system/public/`
  - (Adjust based on your folder name)
- **Admin Login**:
  - Email: `admin@school.edu`
  - Password: `Admin!is-me04`

## ✅ You're Ready!

Start working on feature branches:
```bash
git checkout -b feature/your-feature-name
```

---

**Need more details?** See [COLLABORATION_SETUP.md](COLLABORATION_SETUP.md)

