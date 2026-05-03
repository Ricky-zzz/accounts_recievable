# Command-Line Installation On Windows

This file is only for installing the server tools on the client PC:

- Git
- Composer
- Node/npm
- Apache
- MySQL or MariaDB
- PHP
- Optional: HeidiSQL

After these are installed, continue with `deployment/README.md` for the app deployment.

## Recommended Fast Path

Use Chocolatey if you want the closest Windows equivalent of:

```bash
apt install git composer nodejs php apache2 mysql-server
```

For the smoothest repeatable install, use package-manager commands for Git, Node/npm, Composer, and MySQL/MariaDB, but use the PowerShell zip sections for PHP and Apache. That keeps PHP at `C:\php` and Apache at `C:\Apache24`, matching the config files in this repo.

## Run PowerShell As Administrator

Open Start Menu, search `PowerShell`, right-click, then choose `Run as administrator`.

Verify basic tools:

```powershell
$PSVersionTable.PSVersion
winget --version
```

If `winget` is missing, install or update **App Installer** from Microsoft Store, then open a new Administrator PowerShell window.

## Option A: Chocolatey Install

Chocolatey is the smoothest command-line path for a local Windows server setup.

Install Chocolatey:

```powershell
Set-ExecutionPolicy Bypass -Scope Process -Force
[System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072
iwr https://community.chocolatey.org/install.ps1 -UseBasicParsing | iex
```

Close PowerShell, open a new Administrator PowerShell window, then verify:

```powershell
choco --version
```

Install the tools:

```powershell
choco install git -y
choco install nodejs-lts -y
choco install mysql -y
choco install heidisql -y
```

Then install PHP using `Install PHP By PowerShell Zip`, install Composer using `Install Composer After PHP`, and install Apache using `Install Apache By PowerShell Zip`.

Chocolatey can also install PHP and Apache, but the install paths may not be `C:\php` and `C:\Apache24`. If you use Chocolatey for PHP or Apache, update `deployment\apache\php-module.conf` and `deployment\apache\src-enterprise.local.conf` to match the actual paths.

If a package name fails, search for the current package name:

```powershell
choco search php
choco search apache
choco search mysql
```

Chocolatey usually updates PATH automatically. Close and reopen PowerShell after install.

Verify:

```powershell
git --version
node -v
npm -v
mysql --version
```

## Option B: winget Install

winget is built into modern Windows, but not every server package is as smooth as Linux packages.

Search first:

```powershell
winget search Git
winget search Composer
winget search Node.js
winget search MySQL
winget search MariaDB
winget search PHP
winget search Apache
winget search HeidiSQL
```

Install the common packages:

```powershell
winget install --id Git.Git -e
winget install --id OpenJS.NodeJS.LTS -e
```

Install HeidiSQL if available:

```powershell
winget install --id HeidiSQL.HeidiSQL -e
```

Install one database server:

```powershell
winget install --id Oracle.MySQL -e
```

Or, if you prefer MariaDB and it appears in `winget search MariaDB`:

```powershell
winget install --id MariaDB.Server -e
```

Close and reopen PowerShell, then verify:

```powershell
git --version
node -v
npm -v
mysql --version
```

Then install PHP using `Install PHP By PowerShell Zip`, install Composer using `Install Composer After PHP`, and install Apache using `Install Apache By PowerShell Zip`.

If `Oracle.MySQL` or `MariaDB.Server` does not resolve, use the ID shown by `winget search MySQL` or `winget search MariaDB`.

## Install PHP By PowerShell Zip

Use this when package-manager PHP is unavailable or you want a predictable path.

1. Go to the official PHP for Windows downloads page and copy the URL for a PHP 8.2 or 8.3 x64 Thread Safe zip.
2. Set `$PhpZipUrl` to that URL.

```powershell
$PhpZipUrl = "PASTE_PHP_8_2_OR_8_3_X64_THREAD_SAFE_ZIP_URL_HERE"
$PhpZip = "$env:TEMP\php.zip"

Invoke-WebRequest $PhpZipUrl -OutFile $PhpZip
New-Item -ItemType Directory -Force C:\php | Out-Null
Expand-Archive $PhpZip -DestinationPath C:\php -Force
Copy-Item C:\php\php.ini-production C:\php\php.ini
```

Enable needed PHP extensions:

```powershell
$phpIni = "C:\php\php.ini"
(Get-Content $phpIni) `
  -replace ';extension=curl', 'extension=curl' `
  -replace ';extension=fileinfo', 'extension=fileinfo' `
  -replace ';extension=gd', 'extension=gd' `
  -replace ';extension=intl', 'extension=intl' `
  -replace ';extension=mbstring', 'extension=mbstring' `
  -replace ';extension=mysqli', 'extension=mysqli' `
  -replace ';extension=openssl', 'extension=openssl' `
  -replace ';extension=pdo_mysql', 'extension=pdo_mysql' `
  -replace ';extension=zip', 'extension=zip' `
  -replace ';date.timezone =', 'date.timezone = Asia/Manila' |
  Set-Content $phpIni

Add-Content $phpIni "`nextension_dir = `"ext`""
Add-Content $phpIni "memory_limit = 256M"
Add-Content $phpIni "upload_max_filesize = 20M"
Add-Content $phpIni "post_max_size = 25M"
```

Add PHP to the machine PATH:

```powershell
$currentPath = [Environment]::GetEnvironmentVariable("Path", "Machine")
if ($currentPath -notlike "*C:\php*") {
    [Environment]::SetEnvironmentVariable("Path", "$currentPath;C:\php", "Machine")
}
```

Close and reopen PowerShell, then verify:

```powershell
php -v
php -m | findstr /I "mysqli intl mbstring curl fileinfo"
```

## Install Composer After PHP

Composer needs PHP first.

With Chocolatey:

```powershell
choco install composer -y
```

With winget:

```powershell
winget install --id Composer.Composer -e
```

Manual PowerShell installer:

```powershell
$ComposerSetup = "$env:TEMP\Composer-Setup.exe"
Invoke-WebRequest https://getcomposer.org/Composer-Setup.exe -OutFile $ComposerSetup
Start-Process $ComposerSetup -Wait
```

Close and reopen PowerShell, then verify:

```powershell
composer --version
```

## Install Apache By PowerShell Zip

Apache on Windows is commonly installed from Apache Lounge or Apache Haus zip builds.

1. Download or copy the URL for a Windows Apache 2.4 x64 zip.
2. Set `$ApacheZipUrl` to that URL.

```powershell
$ApacheZipUrl = "PASTE_APACHE_2_4_X64_ZIP_URL_HERE"
$ApacheZip = "$env:TEMP\apache.zip"
$ApacheExtract = "$env:TEMP\apache-extract"

Invoke-WebRequest $ApacheZipUrl -OutFile $ApacheZip
Remove-Item $ApacheExtract -Recurse -Force -ErrorAction SilentlyContinue
New-Item -ItemType Directory -Force $ApacheExtract | Out-Null
Expand-Archive $ApacheZip -DestinationPath $ApacheExtract -Force
```

Find the extracted `Apache24` folder and copy it to `C:\Apache24`:

```powershell
$ApacheFolder = Get-ChildItem $ApacheExtract -Recurse -Directory |
    Where-Object { $_.Name -eq "Apache24" } |
    Select-Object -First 1

Copy-Item $ApacheFolder.FullName C:\Apache24 -Recurse -Force
```

Add Apache to the machine PATH:

```powershell
$currentPath = [Environment]::GetEnvironmentVariable("Path", "Machine")
if ($currentPath -notlike "*C:\Apache24\bin*") {
    [Environment]::SetEnvironmentVariable("Path", "$currentPath;C:\Apache24\bin", "Machine")
}
```

Copy this repo's Apache config files after the app is copied to `C:\src-enterprise\accounts_recievable\ci4-app`:

```powershell
Copy-Item C:\src-enterprise\accounts_recievable\ci4-app\deployment\apache\php-module.conf C:\Apache24\conf\extra\php-module.conf
Copy-Item C:\src-enterprise\accounts_recievable\ci4-app\deployment\apache\src-enterprise.local.conf C:\Apache24\conf\extra\src-enterprise.local.conf
```

Patch `httpd.conf`:

```powershell
$httpdConf = "C:\Apache24\conf\httpd.conf"
$content = Get-Content $httpdConf

$content = $content -replace '#LoadModule rewrite_module modules/mod_rewrite.so', 'LoadModule rewrite_module modules/mod_rewrite.so'
$content = $content -replace '#LoadModule headers_module modules/mod_headers.so', 'LoadModule headers_module modules/mod_headers.so'

Set-Content $httpdConf $content

Add-Content $httpdConf "`nServerName localhost:80"
Add-Content $httpdConf "Include conf/extra/php-module.conf"
Add-Content $httpdConf "Include conf/extra/src-enterprise.local.conf"
```

Install and start Apache as a Windows service:

```powershell
cd C:\Apache24\bin
.\httpd.exe -t
.\httpd.exe -k install -n "Apache24"
Set-Service -Name Apache24 -StartupType Automatic
Start-Service Apache24
```

`Set-Service -StartupType Automatic` makes Apache start when Windows starts.

Verify:

```powershell
Get-Service Apache24
httpd -v
```

## Install MySQL Or MariaDB

With Chocolatey:

```powershell
choco install mysql -y
```

With winget:

```powershell
winget install --id Oracle.MySQL -e
```

Check the service name:

```powershell
Get-Service *mysql*,*maria*
```

Common service names are `MySQL80`, `MySQL`, or `MariaDB`.

Set startup and start the service:

```powershell
Set-Service -Name "MySQL80" -StartupType Automatic
Start-Service "MySQL80"
```

If your service is named `MariaDB`:

```powershell
Set-Service -Name "MariaDB" -StartupType Automatic
Start-Service "MariaDB"
```

`Set-Service -StartupType Automatic` makes MySQL/MariaDB start when Windows starts.

Verify:

```powershell
mysql --version
mysql -u root -p
```

## Add The Local Domain

This maps `src-enterprise.local` to the same computer.

```powershell
Add-Content -Path "C:\Windows\System32\drivers\etc\hosts" -Value "127.0.0.1 src-enterprise.local"
ipconfig /flushdns
```

Verify:

```powershell
ping src-enterprise.local
```

## Environment Variables Summary

These paths should be available from a new PowerShell window:

```text
C:\php
C:\Apache24\bin
```

Git, Node, npm, Composer, and MySQL installers usually add themselves to PATH automatically. Verify with:

```powershell
git --version
node -v
npm -v
php -v
composer --version
mysql --version
httpd -v
```

If a command is not recognized, find the executable and add its folder to PATH:

```powershell
Get-ChildItem "C:\Program Files" -Recurse -Filter composer.bat -ErrorAction SilentlyContinue
Get-ChildItem "C:\Program Files" -Recurse -Filter mysql.exe -ErrorAction SilentlyContinue
```

Then add the folder:

```powershell
$folder = "PASTE_FOLDER_PATH_HERE"
$currentPath = [Environment]::GetEnvironmentVariable("Path", "Machine")
if ($currentPath -notlike "*$folder*") {
    [Environment]::SetEnvironmentVariable("Path", "$currentPath;$folder", "Machine")
}
```

Open a new PowerShell window after changing PATH.

## One-Shot Verification

Run this before moving to the app deployment:

```powershell
git --version
node -v
npm -v
php -v
php -m | findstr /I "mysqli intl mbstring curl fileinfo"
composer --version
mysql --version
httpd -v
Get-Service Apache24
Get-Service *mysql*,*maria*
```

Optional HeidiSQL verification:

```powershell
Get-ChildItem "C:\Program Files" -Recurse -Filter heidisql.exe -ErrorAction SilentlyContinue
```

Then continue with:

```text
deployment/README.md
```
