# SRC Enterprise Local Windows Deployment

This is the install runbook for deploying the CodeIgniter 4 app on a client Windows PC without external hosting.

For command-line installation of Git, Composer, npm, Apache, MySQL/MariaDB, and PHP, start with `deployment/INSTALLATION.md`.

Recommended first install: use Git if you can spare the setup time. It makes future updates much easier: `git pull`, `composer install`, `php spark migrate`, then restart Apache. If the client needs to use it immediately and internet is unreliable, use a release zip that already includes `vendor/` and built `public/assets`.

## Target Setup

- App URL: `http://src-enterprise.local/`
- Repository folder: `C:\src-enterprise\accounts_recievable`
- App folder: `C:\src-enterprise\accounts_recievable\ci4-app`
- Apache folder: `C:\Apache24`
- PHP folder: `C:\php`
- Database: MySQL or MariaDB on port `3306`
- Database name: `src_enterprise`
- Database user: `src_app`
- Apache document root: `C:\src-enterprise\accounts_recievable\ci4-app\public`

Important: Apache must point to the `public` folder, not the project root.

The Apache `<VirtualHost>` and `<Directory>` blocks live in Apache config, not in `.htaccess`. The vhost tells Apache where the app is and grants access to that folder. The `public\.htaccess` file stays inside the app and handles CodeIgniter's URL rewrite rules.

## What To Bring On USB

For fastest deployment, prepare these before going to the client:

- The app release zip, or the Git repository URL if cloning.
- PHP 8.2 or 8.3 x64 Thread Safe zip for Windows.
- Apache 2.4 x64 for Windows.
- MySQL Server or MariaDB MSI installer.
- Microsoft Visual C++ Redistributable x64 if the PC does not already have it.
- Composer installer if using Git or if `vendor/` is not included in the zip.
- Optional: HeidiSQL for visual database browsing and SQL exports.
- Optional: Git installer.
- Optional: Node.js installer, only needed if you will rebuild frontend assets.

This app already has built assets in `public/assets`. Node/npm is not needed on the client unless you change `resources/css/app.css`, upgrade Alpine/Tailwind, or need to run `npm run build`.

## Option A: Git Install

Use this if the client PC has internet and you want easy updates later.

Run PowerShell as Administrator:

```powershell
mkdir C:\src-enterprise
cd C:\src-enterprise
git clone YOUR_REPOSITORY_URL accounts_recievable
cd C:\src-enterprise\accounts_recievable\ci4-app
composer install --no-dev --optimize-autoloader
```

If frontend assets need rebuilding:

```powershell
npm ci
npm run build
```

## Option B: Zip Install

Use this if deployment must be quick or offline.

On your development machine before zipping:

```powershell
cd C:\laragon\www\accounts_recievable\ci4-app
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

Zip the whole `ci4-app` folder, including:

- `vendor/`
- `public/assets/`
- `writable/`
- `composer.lock`
- `package-lock.json`

Do not include your development `.env` unless you intentionally changed it for production. On the client, unzip to:

```text
C:\src-enterprise\accounts_recievable\ci4-app
```

If you zip the whole repository instead of only `ci4-app`, unzip it so the final app path is still:

```text
C:\src-enterprise\accounts_recievable\ci4-app
```

## Install PHP

Extract PHP to:

```text
C:\php
```

Create `C:\php\php.ini` from `php.ini-production`, then enable these extensions by removing the leading semicolon:

```ini
extension_dir = "ext"
extension=curl
extension=fileinfo
extension=gd
extension=intl
extension=mbstring
extension=mysqli
extension=openssl
extension=pdo_mysql
extension=zip
```

Also set:

```ini
date.timezone = Asia/Manila
memory_limit = 256M
upload_max_filesize = 20M
post_max_size = 25M
```

Add PHP to the system PATH:

```powershell
setx /M PATH "$env:PATH;C:\php"
```

Open a new PowerShell window, then verify:

```powershell
php -v
php -m | findstr /I "mysqli intl mbstring curl fileinfo"
```

Note: MySQLi is the PHP extension. MySQL or MariaDB is the database server.

## Install Apache

Extract Apache to:

```text
C:\Apache24
```

Copy these files from this deployment folder:

- `deployment\apache\php-module.conf` to `C:\Apache24\conf\extra\php-module.conf`
- `deployment\apache\src-enterprise.local.conf` to `C:\Apache24\conf\extra\src-enterprise.local.conf`

Edit `C:\Apache24\conf\httpd.conf` and make sure these lines exist and are uncommented:

```apache
ServerName localhost:80
LoadModule rewrite_module modules/mod_rewrite.so
LoadModule headers_module modules/mod_headers.so
Include conf/extra/php-module.conf
Include conf/extra/src-enterprise.local.conf
```

Install and start Apache as a Windows service:

```powershell
cd C:\Apache24\bin
.\httpd.exe -t
.\httpd.exe -k install -n "Apache24"
Set-Service -Name Apache24 -StartupType Automatic
Start-Service Apache24
```

`Set-Service -StartupType Automatic` makes Apache start when Windows starts. This is the Windows equivalent of enabling a service at boot on Linux.

If Apache will not start, check:

```powershell
Get-Content C:\Apache24\logs\error.log -Tail 80
```

## Install MySQL Or MariaDB

Install MySQL Server or MariaDB from the MSI installer and configure it as a Windows service. Choose a strong root password and keep it in your private notes.

After install, verify the service:

```powershell
Get-Service *mysql*,*maria*
Set-Service -Name "MariaDB" -StartupType Automatic
Start-Service "MariaDB"
```

If the service name is `MySQL80` instead:

```powershell
Set-Service -Name "MySQL80" -StartupType Automatic
Start-Service "MySQL80"
```

`Set-Service -StartupType Automatic` makes MySQL/MariaDB start when Windows starts. After this, you should not need to start the database manually after every reboot.

## Create Database And User

Edit `deployment\sql\create-database-and-user.sql` and replace:

- `CHANGE_APP_PASSWORD` with the app database password you will put in `.env`.
- `CHANGE_BACKUP_PASSWORD` with a separate password for HeidiSQL backup/admin browsing.

Run one of these, depending on the database client command available:

```powershell
mysql -u root -p < C:\src-enterprise\accounts_recievable\ci4-app\deployment\sql\create-database-and-user.sql
```

If PowerShell blocks input redirection, use:

```powershell
cmd /c "mysql -u root -p < C:\src-enterprise\accounts_recievable\ci4-app\deployment\sql\create-database-and-user.sql"
```

The app should use `src_app` in `.env`. For HeidiSQL backup/browsing work, use `src_backup` where possible so you do not casually edit production data using the same account the app uses. Use `root` only when you need full admin privileges.

## Configure The App Env

Copy the production env template:

```powershell
cd C:\src-enterprise\accounts_recievable\ci4-app
Copy-Item deployment\prod.env .env
```

Edit `.env`:

- Set `database.default.password` to the password used in the SQL file.
- Keep `app.baseURL = 'http://src-enterprise.local/'`.
- Keep `app.indexPage = ''` so URLs do not include `index.php`.
- Keep `CI_ENVIRONMENT = production`.

Generate a unique encryption key:

```powershell
php spark key:generate
```

If the key command does not update `.env`, generate a key manually:

```powershell
php -r "echo 'hex2bin:' . bin2hex(random_bytes(32)) . PHP_EOL;"
```

Then paste the output into:

```ini
encryption.key = hex2bin:PASTE_GENERATED_VALUE
```

## Add Local Domain

Open Notepad as Administrator and edit:

```text
C:\Windows\System32\drivers\etc\hosts
```

Add:

```text
127.0.0.1 src-enterprise.local
```

Then flush DNS:

```powershell
ipconfig /flushdns
```

This `hosts` entry only works on the machine where you add it. For single-machine use, that is enough.

## Sharing On The Local Network

If other computers on the same office network need to use the app, do not rely on a random changing IP address. Pick one stable LAN name and make every user open the app with that same URL.

Best options:

- Best: ask the router/admin to reserve a fixed DHCP IP for the host PC, then create local DNS like `src-enterprise.local`.
- Good without router access: use the Windows computer name, for example `SRC-SERVER`, and open `http://SRC-SERVER/` from other PCs.
- Last resort: add a `hosts` entry on every client PC, but this needs a stable IP on the host PC.

To use the Windows computer name approach, check the host PC name:

```powershell
hostname
```

Example result:

```text
SRC-SERVER
```

Then update the production `.env`:

```ini
app.baseURL = 'http://SRC-SERVER/'
```

Update `C:\Apache24\conf\extra\src-enterprise.local.conf`:

```apache
<VirtualHost *:80>
    ServerName SRC-SERVER
    ServerAlias src-enterprise.local
    DocumentRoot "C:/src-enterprise/accounts_recievable/ci4-app/public"

    <Directory "C:/src-enterprise/accounts_recievable/ci4-app/public">
        Options FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog "logs/src-enterprise-error.log"
    CustomLog "logs/src-enterprise-access.log" common
</VirtualHost>
```

Restart Apache:

```powershell
Restart-Service Apache24
```

Allow Apache through Windows Firewall for LAN access:

```powershell
New-NetFirewallRule -DisplayName "Apache HTTP Inbound" -Direction Inbound -Protocol TCP -LocalPort 80 -Action Allow
```

From another PC on the same network, test:

```powershell
ping SRC-SERVER
```

Then open:

```text
http://SRC-SERVER/
```

Important: all users should use the same URL. If the app base URL is `http://SRC-SERVER/`, do not tell some users to use `http://192.168.x.x/`, because generated links and redirects will point back to the base URL.

## Run Migrations And Seed Initial Data

From the app folder:

```powershell
cd C:\src-enterprise\accounts_recievable\ci4-app
php spark migrate
php spark db:seed MainSeeder
```

Default seeded admin login:

```text
Username: admin
Password: admin1234
```

Change this password immediately after confirming the app works.

## Final Verification

Run:

```powershell
php spark routes
php spark migrate:status
```

Open:

```text
http://src-enterprise.local/
```

Check these files if anything fails:

```text
C:\src-enterprise\accounts_recievable\ci4-app\writable\logs
C:\Apache24\logs\error.log
```

## Backup Routine

Create a local backup folder:

```powershell
mkdir C:\src-enterprise\backups
```

Manual database backup:

```powershell
$stamp = Get-Date -Format yyyy-MM-dd
mysqldump -u src_backup -p src_enterprise | Out-File -Encoding utf8 "C:\src-enterprise\backups\src_enterprise_$stamp.sql"
```

For production use, create a Windows Task Scheduler job that runs a backup daily. Also copy backups to a USB drive or another computer regularly, because the app and database are on the same machine.

## HeidiSQL Database Manager

HeidiSQL is a good lightweight DB manager for this setup. Use it for browsing data, checking tables, and visual SQL exports. Before making manual edits or running migrations, make a backup first.

Create a HeidiSQL session:

```text
Network type: MariaDB or MySQL (TCP/IP)
Hostname / IP: 127.0.0.1
User: src_backup
Password: your src_backup password
Port: 3306
```

After connecting, you should see:

```text
src_enterprise
```

You can also connect with the root user when you need full admin rights:

```text
User: root
Password: your MySQL/MariaDB root password
```

Keep `.env` using `src_app`. Use `src_backup` for browsing and backups. Use `root` only for database/user creation, restore operations, and other full-admin tasks.

Visual backup in HeidiSQL:

1. Connect to `127.0.0.1`.
2. Right-click database `src_enterprise`.
3. Choose `Export database as SQL`.
4. Select structure and data.
5. Save to `C:\src-enterprise\backups`.
6. Name the file with date and time.

Avoid dropping tables, truncating tables, or bulk editing data unless you have a fresh backup.

## Updating Later With Git

When you return for updates:

```powershell
cd C:\src-enterprise\accounts_recievable\ci4-app
git pull
composer install --no-dev --optimize-autoloader
php spark migrate
Restart-Service Apache24
```

Only run npm when frontend source files changed:

```powershell
npm ci
npm run build
```

## Updating Later With Zip

Before replacing files, back up:

```powershell
mkdir C:\src-enterprise\backups
Compress-Archive C:\src-enterprise\accounts_recievable\ci4-app C:\src-enterprise\backups\ci4-app-before-update.zip
```

Then copy the new release over the old app, but keep the existing production `.env` and `writable\uploads` folder unless the update specifically requires changing them.

After copying:

```powershell
cd C:\src-enterprise\accounts_recievable\ci4-app
php spark migrate
Restart-Service Apache24
```

## Quick Troubleshooting

- White page or 500 error: check `writable\logs` and `C:\Apache24\logs\error.log`.
- CSS missing: confirm `public\assets\css\app.css` exists.
- JavaScript missing: confirm `public\assets\js\alpine.min.js` exists.
- Login redirects wrong: confirm `.env` has `app.baseURL = 'http://src-enterprise.local/'`.
- URL shows `index.php`: confirm Apache `mod_rewrite` is enabled and the vhost has `AllowOverride All`.
- Database connection error: confirm database service is running, `.env` password is correct, and `extension=mysqli` is enabled in `php.ini`.
- Port 80 busy: run `netstat -ano | findstr :80`, stop the conflicting service, or change Apache to another port.
