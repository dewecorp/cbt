@echo off
setlocal enabledelayedexpansion
set REPO_DIR=D:\laragon\www\cbt
cd /d "%REPO_DIR%"
if not exist ".git" git init
for /f "tokens=1" %%r in ('git remote') do set HASREMOTE=%%r
if "%HASREMOTE%"=="" (
  git remote add origin https://github.com/dewecorp/cbt.git
) else (
  git remote set-url origin https://github.com/dewecorp/cbt.git
)
set /p commitMsg=Masukkan pesan commit:
echo Pesan commit: "%commitMsg%"
set /p confirm=Lanjutkan? (Y/N):
if /I not "%confirm%"=="Y" goto end
git add -A
git diff --cached --quiet
if errorlevel 1 (
  git commit -m "%commitMsg%"
) else (
  echo Tidak ada perubahan untuk commit.
)
for /f "delims=" %%i in ('git rev-parse --abbrev-ref HEAD') do set BRANCH=%%i
if "%BRANCH%"=="" set BRANCH=main
git rev-parse --verify "%BRANCH%" >nul 2>&1
if errorlevel 1 git checkout -b "%BRANCH%"
git push -u origin "%BRANCH%"
set BACKUP_DIR=%REPO_DIR%\backups
if not exist "%BACKUP_DIR%" mkdir "%BACKUP_DIR%"
set ZIP_PATH=%BACKUP_DIR%\cbt-backup.zip
powershell -NoProfile -ExecutionPolicy Bypass -File "%REPO_DIR%\backup_helper.ps1"
echo Selesai.
:end
endlocal
