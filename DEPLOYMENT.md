# MyBatchoy — Free Cloud Deployment Guide (GitHub + Vercel + Cloud MySQL + Expo Go)

## Overview
- **GitHub**: Stores source code and triggers automatic deployments.
- **Vercel**: Hosts the Laravel web application and `/api/v1` REST API for free (`vercel.json` + `api/index.php`).
- **TiDB Cloud Serverless / Aiven**: Free managed cloud MySQL database shared by both the website and mobile app.
- **Expo Go / EAS**: Runs the mobile app on Android & iOS connected to your Vercel `/api/v1` URL.

---

## Step 1: Push Project to GitHub
1. Create a new **Private** repository at https://github.com/new named `mybatchoy`.
2. In PowerShell inside `c:\xampp\htdocs\Mybatchoy`:
   ```powershell
   cd c:\xampp\htdocs\Mybatchoy
   git add .
   git commit -m "Prepare MyBatchoy web and mobile for production"
   git branch -M main
   git remote set-url origin https://github.com/Tristan-HubTech/mybatchoyv2.git
   git push -u origin main
   ```

---

## Step 2: Create Free Cloud MySQL Database
1. Sign up at **TiDB Cloud** (https://tidbcloud.com) or **Aiven** (https://aiven.io) and create a free MySQL cluster.
2. Copy the connection credentials:
   - `DB_HOST`
   - `DB_PORT` (`4000` for TiDB or `3306` for standard MySQL)
   - `DB_DATABASE`
   - `DB_USERNAME`
   - `DB_PASSWORD`

---

## Step 3: Deploy Web + API on Vercel
1. Sign in to https://vercel.com with GitHub.
2. Click **Add New... → Project** and **Import** your `mybatchoy` repository.
3. Add these **Environment Variables** in Vercel:
   ```ini
   APP_NAME=MyBatchoy
   APP_ENV=production
   APP_DEBUG=false
   APP_KEY=base64:YOUR_APP_KEY_FROM_LOCAL_ENV
   APP_URL=https://your-project-name.vercel.app

   DB_CONNECTION=mysql
   DB_HOST=your-cloud-mysql-host
   DB_PORT=4000
   DB_DATABASE=mybatchoy
   DB_USERNAME=your-cloud-db-user
   DB_PASSWORD=your-cloud-db-password
   MYSQL_ATTR_SSL_CA=/etc/ssl/certs/ca-certificates.crt
   ```
4. Click **Deploy**.

---

## Step 4: Initialize Production Cloud MySQL Database Safely
> **Important:** Never run demo seeders (`OwnerSeeder`, `StaffSeeder`, `InventorySeeder`, `TestDataSeeder`) in production. Always provision production credentials interactively.

Temporarily point your local `.env` `DB_*` variables to your cloud MySQL database (or run from a secure admin shell), then run:
```powershell
C:\xampp\php\php.exe artisan migrate --force
C:\xampp\php\php.exe artisan db:seed --class=ProductionSeeder --force
C:\xampp\php\php.exe artisan app:create-owner
C:\xampp\php\php.exe artisan app:create-system-admin
```
Then restore your local `.env` `DB_HOST=127.0.0.1` for local XAMPP use.

### Local Development / Demo Seeding (Non-Production Only)
For local testing environments (`APP_ENV=local` only), you can populate sample roles, inventory, and test accounts using:
```powershell
C:\xampp\php\php.exe artisan migrate --seed
```

---

## Step 5: Connect Mobile App to Live Vercel API
1. Open the MyBatchoy app in **Expo Go**.
2. On the Sign In screen, tap **API Server URL**.
3. Set the URL to:
   ```text
   https://your-project-name.vercel.app/api/v1
   ```
4. Tap **Save Endpoint**.

