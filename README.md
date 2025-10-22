# 🍪 Cookie Consent

A lightweight and minimal PHP app that manages user cookie consent.

## 📦 Stack

- ✅ PHP — web scripting language.
- 🗄️ MySQL — persistent storage for consent records.
- 🧩 Javascript — minimal, for fetching consent actions and updating UI
- 🎨 CSS + [Open Props](https://open-props.style/) — minimal, for visual UI styling
- 🐳 Docker — Deployable anywhere; works locally, or via container platforms.

### 🧰 Requirements

- PHP ≥ 8.1 (with `pdo_mysql` extension)
- MySQL ≥ 8.0
- Bash (for migrations)
- Optional: phpMyAdmin (for inspection)

## 🏃 Running locally

### 📥 First-time install (MacOS)

If you are on MacOS, I prefer to use install with Homebrew:

```sh
brew install php mysql phpmyadmin
```

Launch mysql service

```sh
brew services start mysql
```

### 🏞️ Environment variables

This project uses a simple .env file (manually loaded in PHP).
You can copy from the example template:

```sh
cp .env.example .env
```

Then edit .env with your database credentials.

> 💡 We intentionally avoid `phpdotenv` for simplicity. The Config class reads environment variables directly.

### 🗃️ Database setup

Set up a secure MySQL user

```sql
-- Option 1: quick dev setup
ALTER USER 'root'@'localhost' IDENTIFIED BY 'YourStrongPassword123!';
FLUSH PRIVILEGES;

-- Option 2: principle of least privilege, to not use `root` user
CREATE USER 'dev'@'localhost' IDENTIFIED BY 'YourStrongPassword123!';
CREATE DATABASE cookie_consent CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON cookie_consent.* TO 'dev'@'localhost';
FLUSH PRIVILEGES;
```

### ✈️ Apply migrations

Run all migrations (in order):

```sh
bash scripts/migrate.sh
```

> ⚠️ On cloud runners, you might need to disable SSL in scripts/migrate.sh if the default certs mismatch:
>
> ```text
> --ssl=OFF \
> --ssl-verify-server-cert=OFF \
> ```

These flags are only for containerized or CI/CD environments with self-signed database certs.

Apply db migration

```sh
bash scripts/migrate.sh
```

These following lines were required on my cloud build runner. MySQL uses a valid TLS connection, but not a chain signed by the container’s CA list.

On your local machine, these lines may throw errors, because of mismatching MySQL version. They are safe to remove.

```sh
# scripts/migrate.sh
--ssl=OFF \
--ssl-verify-server-cert=OFF \
```

## 🚀 Launch the frontend

Optionally serve phpMyAdmin

````sh
cd /opt/homebrew/share/phpmyadmin
php -S localhost:8080
````

Serve the PHP app

```sh
php -S localhost:8000 -t public
```

Visit `http://localhost:8000`.

## 🧹 Maintenance

Cleanup expired cookie consent records. We keep a short retention period to maintain compliance and avoid unnecessary database growth.

Run manually or via cron:

```sh
php scripts/cleanup_expired_consents.php
```

<details>
<summary>Rationale</summary>
<ul>
  <li>we want to clear records: keeping indefinitely violates data minimization principle + bloats storage</li>
  <li>but deleting immediately is problematic
    <ul>
      <li>may break auditability — we need a brief retention window for compliance logs</li>
      <li>may cause unnecessary writes + race conditions (especially on every client expire)</li>
    </ul>
  </li>
  <li>solution: script; manually or cron job</li>
</ul>

</details>

## 🏗️ Project Structure

```filetree
.
├── public/                   # Web root (served via PHP built-in or Apache)
│   ├── index.php             # Home page
│   ├── partials/             # Shared header/footer components
│   ├── assets/               # Web assets, served as-is
│   └── [...]                 # Page and endpoint routes
│
├── includes/                 # Backend helpers
│   ├── Config.php            # Static config class
│   ├── db.php                # PDO connection
│   ├── utils.php             # Generic utils
│   └── cookie/[...].php      # Cookie consent verification logic
│
├── migrations/               # SQL schema migrations
│   └── [migration_name].sql
│
├── scripts/                  # Admin scripts
│
├── .env.example
├── Dockerfile
└── README.md
```

## 🤖 Acknowledgements

- This project's human effort was assisted with AI tooling.
- [favicon.io](https://favicon.io) for emoji favicon.

## 📄 License

MIT © 2025 — Open for educational and practical reuse.
