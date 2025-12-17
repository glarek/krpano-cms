@echo off
echo Starting Krpano CMS Server...
echo Limits: Upload=256MB, Post=256MB, Memory=512M
echo Extensions: Zip
echo Protection: Active (using router.php)
echo Access at http://localhost:8000/admin/
php -d upload_max_filesize=256M -d post_max_size=256M -d memory_limit=512M -d extension=zip -S localhost:8000 router.php
pause
