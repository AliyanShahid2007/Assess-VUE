@echo off
setlocal
set MYSQL=C:\xampp\mysql\bin\mysql.exe
set SQLFILE=C:\xampp\htdocs\examapp\database.sql

"%MYSQL%" -uroot -e "CREATE DATABASE IF NOT EXISTS exam_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
"%MYSQL%" -uroot -e "CREATE USER IF NOT EXISTS 'examuser'@'localhost' IDENTIFIED BY 'ExamPass2024!';"
"%MYSQL%" -uroot -e "GRANT ALL PRIVILEGES ON exam_system.* TO 'examuser'@'localhost'; FLUSH PRIVILEGES;"
"%MYSQL%" -uroot exam_system < "%SQLFILE%"
"%MYSQL%" -u examuser -pExamPass2024! -D exam_system -e "SHOW TABLES;"

if errorlevel 1 (
  echo IMPORT_FAILED
  exit /b 1
)

echo IMPORT_SUCCESS
endlocal
