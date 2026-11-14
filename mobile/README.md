# MyBatchoy Mobile Application (Expo Go)

Complete mobile client for the **MyBatchoy Inventory Management System**, connected to the same Laravel 10 backend and MySQL database via `/api/v1`.

## Quick Start with Expo Go

1. **Install dependencies:**
   ```powershell
   cd c:\xampp\htdocs\Mybatchoy\mobile
   npm install
   ```

2. **Ensure the Laravel backend is reachable on your Wi-Fi network:**
   - **Option A (XAMPP Apache):** Make sure Apache & MySQL are running in XAMPP. Your endpoint is:
     `http://<YOUR_PC_LAN_IP>/Mybatchoy/public/api/v1`
   - **Option B (Artisan Server):**
     ```powershell
     cd c:\xampp\htdocs\Mybatchoy
     C:\xampp\php\php.exe artisan serve --host=0.0.0.0 --port=8000
     ```
     Your endpoint is:
     `http://<YOUR_PC_LAN_IP>:8000/api/v1`

3. **Start Expo Go bundler:**
   ```powershell
   cd c:\xampp\htdocs\Mybatchoy\mobile
   npx expo start
   ```

4. **Open in Expo Go:**
   - Scan the QR code with the **Expo Go** app (Android) or Camera app (iOS).
   - Tap the **API URL** button on the top-right of the Sign In card and enter your PC's Wi-Fi IP URL (e.g. `http://192.168.1.10/Mybatchoy/public/api/v1`).
   - Sign in with your existing Owner or Staff username and password.

