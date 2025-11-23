# 🤝 Collaboration Setup Guide

Welcome! This guide will help you get the project running on your machine quickly.

## 📋 Prerequisites

Before you start, make sure you have:
- **XAMPP** (or similar: Apache + MySQL + PHP 8.0+)
- **Composer** (PHP dependency manager)
- **Node.js & npm** (for frontend assets - optional but recommended)
- **Git** (for version control)
- **A code editor** (VS Code, PhpStorm, etc.)

## 🚀 Quick Setup (10 minutes)

### Step 1: Clone the Repository

```bash
# Clone the repository
git clone https://github.com/YOUR_USERNAME/student-monitoring-system.git
cd student-monitoring-system
```

### Step 2: Install PHP Dependencies

```bash
# Install Composer dependencies
composer install
```

### Step 3: Configure Your Local Environment

```bash
# Copy the configuration template
# Windows (PowerShell):
Copy-Item config\config.example.php config\config.php

# Mac/Linux:
cp config/config.example.php config/config.php
```

**Edit `config/config.php`** with your local database settings:
- Update `database` name if different
- Update `username` and `password` for your MySQL
- Update `base_url` if your project is in a subfolder (e.g., `/student-monitoring/`)

### Step 4: Set Up Database

1. **Start XAMPP** (Apache + MySQL)
2. **Open phpMyAdmin**: http://localhost/phpmyadmin
3. **Create Database**: 
   ```sql
   CREATE DATABASE student_monitoring CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
4. **Run Database Setup**:
   ```bash
   php database/update_schema.php
   ```
5. **Create Admin User**:
   ```bash
   php database/init_admin.php
   ```

### Step 5: Install Frontend Dependencies (Optional)

```bash
# Install Node.js dependencies
npm install

# Build frontend assets (if needed)
npm run build
```

### Step 6: Access the Application

- **URL**: `http://localhost/student-monitoring-system/public/`
  - Or adjust based on your folder name and `base_url` in config
- **Admin Login**: 
  - Email: `admin@school.edu`
  - Password: `Admin!is-me04`

## 🔧 Configuration Details

### Database Configuration

Your `config/config.php` should look like this:

```php
'database' => [
    'host' => '127.0.0.1',        // Your MySQL host
    'port' => 3306,                // Your MySQL port
    'database' => 'student_monitoring', // Your database name
    'username' => 'root',          // Your MySQL username
    'password' => '',              // Your MySQL password
],
```

### Base URL Configuration

If your project is in a subfolder, update the `base_url`:

```php
'base_url' => '/student-monitoring-system/', // Match your folder name
```

## 🛠️ Development Workflow

### Daily Workflow

1. **Start your day**:
   ```bash
   git checkout main
   git pull origin main
   ```

2. **Create a feature branch**:
   ```bash
   git checkout -b feature/your-feature-name
   ```

3. **Make your changes** and commit frequently:
   ```bash
   git add .
   git commit -m "feat: description of your changes"
   ```

4. **Push your work**:
   ```bash
   git push origin feature/your-feature-name
   ```

5. **Create a Pull Request** on GitHub for review

### Branch Naming Convention

- `feature/description` - New features
- `fix/description` - Bug fixes
- `docs/description` - Documentation updates
- `refactor/description` - Code refactoring

## 📚 Important Files to Know

### Configuration Files
- `config/config.php` - **Your local config** (gitignored, won't be committed)
- `config/config.example.php` - Template (committed to repo)

### Database Files
- `database/update_schema.php` - Sets up database tables
- `database/init_admin.php` - Creates initial admin user
- `student_monitoring.sql` - Full database dump (if needed)

### Key Directories
- `app/` - Backend PHP code (Controllers, Models, Helpers)
- `resources/views/` - Frontend PHP templates
- `public/assets/` - Compiled CSS/JS files
- `src/` - Source SCSS/JS files (before compilation)
- `api/` - API endpoints

## 🚨 Common Issues & Solutions

### Issue: Database Connection Error

**Solution:**
1. Check MySQL is running in XAMPP
2. Verify database name matches `config/config.php`
3. Check username/password in `config/config.php`
4. Ensure database exists: `CREATE DATABASE student_monitoring;`

### Issue: 404 Errors

**Solution:**
1. Access via: `http://localhost/student-monitoring-system/public/`
2. Check `.htaccess` file exists in `public/` directory
3. Ensure Apache `mod_rewrite` is enabled
4. Verify `base_url` in `config/config.php` matches your setup

### Issue: CSRF Token Errors

**Solution:**
1. Clear browser cache and cookies
2. Use incognito/private window
3. Check session configuration in `config/config.php`

### Issue: Composer/Node Errors

**Solution:**
1. Make sure Composer is installed: `composer --version`
2. Make sure Node.js is installed: `node --version`
3. Delete `vendor/` and `node_modules/` folders, then reinstall:
   ```bash
   composer install
   npm install
   ```

## 🔐 Security Notes

### What's Gitignored (Not Committed)

- `config/config.php` - Your local database credentials
- `vendor/` - Composer dependencies
- `node_modules/` - Node.js dependencies
- `logs/*.log` - Log files
- `.env` files - Environment variables
- Profile pictures and uploaded files

### What's Committed

- `config/config.example.php` - Template (no real credentials)
- Source code files
- Documentation
- Database schema files

## 📖 Additional Resources

- **Main README**: [README.md](README.md)
- **Setup Guide**: [docs/SETUP_GUIDE.md](docs/SETUP_GUIDE.md)
- **Git Collaboration**: [docs/GIT_COLLABORATION_GUIDE.md](docs/GIT_COLLABORATION_GUIDE.md)
- **Frontend Guide**: [docs/FRONTEND_UI.md](docs/FRONTEND_UI.md)

## 🤝 Getting Help

If you encounter issues:
1. Check the troubleshooting section above
2. Review the documentation in `docs/` folder
3. Check GitHub Issues (if any)
4. Communicate with your partner about blockers

## ✅ Setup Checklist

- [ ] Repository cloned
- [ ] Composer dependencies installed (`composer install`)
- [ ] Configuration file created (`config/config.php`)
- [ ] Database created and schema imported
- [ ] Admin user created
- [ ] Application accessible in browser
- [ ] Can log in as admin
- [ ] Git workflow understood

---

**Welcome to the team! Happy coding! 🎉**

