# 🍪 Cookie Consent

## 🏃 Running the app locally

### 📥 First-time install

If you are on Mac, I prefer to use brew

```shell
brew install php mysql phpmyadmin
```

Launch mysql

```shell
brew services start mysql
```

### 🏞️ Environment variables

Could use `phpdotenv` for env vars, but opted for simple + manual reading. See `.env.example` for required environment variables. Ensure a `.env` file, no matter the environment.

### 🗃️ Setup DB

Set password for MySQL default user

```shell
# Option A: interactive
mysql_secure_installation

# Option B: manual
mysql -u root # ensure mysql is running
ALTER USER 'root'@'localhost' IDENTIFIED BY 'YourStrongPassword123!';
FLUSH PRIVILEGES;
EXIT;
# but instead of using `root`, you should create dev user
# run in mysql (or via phpMyAdmin ui)
CREATE USER 'dev'@'localhost' IDENTIFIED BY 'YourStrongPassword123!';
GRANT INSERT, SELECT ON cookie_consent.* TO 'dev'@'localhost';
FLUSH PRIVILEGES;

```

Apply db migration

```shell
bash scripts/migrate.sh
```

These following lines were required on my cloud build runner. MySQL uses a valid TLS connection, but not a chain signed by the container’s CA list.

On your local machine, these lines may throw errors, because of mismatching MySQL version. They are safe to remove.

```shell
# scripts/migrate.sh
--ssl=OFF \
--ssl-verify-server-cert=OFF \
```

### 🚀 Launching frontend

Optionally serve phpmyadmin (installed via brew)

```shell
cd /opt/homebrew/share/phpmyadmin
php -S localhost:8080
```

Serve frontend site

```shell
php -S localhost:8000 -t public
```

### 🧹 Cleanup

Cleanup expired cookie consent records

- we want to clear records: keeping indefinitely violates data minimization principle + bloats storage
- but deleting immediately is problematic
  - may break auditability — we need a brief retention window for compliance logs
  - may cause unnecessary writes + race conditions (especially on every client expire)
- solution: script; manually or cron job

```shell
php scripts/cleanup_expired_consents.php
```

Note: This project's effort was assisted with AI.
