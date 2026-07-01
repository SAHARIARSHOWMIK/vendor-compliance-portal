Vendor Compliance Portal - Windows Run Notes

Option A: Local PHP setup
1. Install PHP 8.2 or newer.
2. Install Composer.
3. Install Node.js LTS.
4. Double-click setup_windows.bat.
5. Start two terminals:
   - start_laravel.bat
   - start_vite.bat
6. Open http://127.0.0.1:8000

Option B: Docker
1. Install Docker Desktop.
2. Run: docker compose up --build
3. Follow the README Docker section.

Never commit .env, vendor/, node_modules/, or local database/storage files.
