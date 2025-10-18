# Cookie Consent

## Running the app locally

### First-time install

If you are on Mac, prefer to use brew

```shell
brew install php mysql phpmyadmi
```

Launch mysql

```shell
brew services start mysql
```

### Setup DB

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
CREATE USER 'dev'@'localhost' IDENTIFIED BY 'devpass';
GRANT INSERT, SELECT ON myprojectdb.* TO 'dev'@'localhost';
FLUSH PRIVILEGES;

```

Apply db migration

```shell
mysql -u root -p < migrations/001_create_consent_table.sql
```

### Launching frontend

Serve phpmyadmin (installed via brew)

```shell
cd /opt/homebrew/share/phpmyadmin
php -S localhost:8080
```

Serve frontend site

```shell
php -S localhost:8000 -t public
```
