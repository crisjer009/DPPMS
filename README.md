# DPPMS

Development Project & Performance Monitoring System, an internal PHP and jQuery application.

## Requirements

- PHP 8.1 or newer with PDO MySQL, mbstring, and MySQL/MariaDB support
- MySQL 8 or MariaDB 10.4+
- Apache (XAMPP is supported)

## Local setup (XAMPP)

1. Start Apache and MySQL in the XAMPP Control Panel.
2. In phpMyAdmin, create a database named `dppms` using `utf8mb4` collation.
3. Copy `.env.example` to `.env` and set the database username/password. Keep `.env` private and out of Git.
4. From PowerShell in the project directory, run `C:\xampp\php\php.exe scripts\migrate.php`.
5. Create the first System Administrator with `C:\xampp\php\php.exe scripts\create_admin.php`. The CLI prompts for username, full name, email, password, and confirmation. The script only runs from CLI, refuses to create a second bootstrap administrator, and stores the password using `password_hash()`.
6. Open [http://localhost/dppms/public/](http://localhost/dppms/public/) and sign in. For a clean URL, configure Apache's document root or an alias to `public/`.

The user migration is the only migration included in Phase 1. Future module migrations will be added with those implementation phases.

## Phase 2A: projects

After updating to Phase 2A, apply the additive project migrations from the project directory:

```powershell
C:\xampp\php\php.exe scripts\migrate.php
```

This creates `projects` and `project_members`. No sample projects are seeded. System Administrators and Development Officers can create and edit projects; all active users can view them.

## Phase 2B: tasks

Apply the task and task-history migrations from the project directory:

```powershell
C:\xampp\php\php.exe scripts\migrate.php
```

This creates `tasks` and `task_history`. Task codes are allocated while locking the project row, and task updates and their history entries commit together. No task records are seeded.

## Configuration

See `.env.example` for the local database connection and application URL. Set `SESSION_SECURE_COOKIE=true` only when serving DPPMS over HTTPS. Configure Apache to point its document root at `public/` for production deployments. The bundled XAMPP Apache currently uses PHP 7.2; use `C:\xampp\php\php.exe` for matching CLI behavior. PHP 8+ is recommended for deployment.
