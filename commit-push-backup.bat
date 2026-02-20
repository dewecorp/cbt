@echo off
setlocal enabledelayedexpansion
set "REPO_DIR=D:\laragon\www\cbt"
cd /d "%REPO_DIR%"

git --version >nul 2>&1
if errorlevel 1 (
  echo Git tidak ditemukan atau belum ada di PATH.
  goto end
)

if not exist ".git" (
  git init
)

set "HASREMOTE="
for /f "tokens=1" %%r in ('git remote') do set "HASREMOTE=%%r"
if "%HASREMOTE%"=="" (
  git remote add origin https://github.com/dewecorp/cbt.git
) else (
  git remote set-url origin https://github.com/dewecorp/cbt.git
)

set /p commitMsg=Masukkan pesan commit:
if "%commitMsg%"=="" (
  echo Pesan commit kosong. Dibatalkan.
  goto end
)

echo Pesan commit: "%commitMsg%"
set /p confirm=Lanjutkan? (Y/N):
if /I not "%confirm%"=="Y" goto end

git add -A
git diff --cached --quiet
if errorlevel 1 (
  git commit -m "%commitMsg%"
  if errorlevel 1 (
    echo Commit gagal.
    goto end
  )
  git push origin main
) else (
  echo Tidak ada perubahan untuk di-commit.
)

set "BACKUP_DIR=%REPO_DIR%\backups"
if not exist "%BACKUP_DIR%" mkdir "%BACKUP_DIR%"
set "ZIP_PATH=%BACKUP_DIR%\cbt-backup.zip"
powershell -NoProfile -ExecutionPolicy Bypass -File "%REPO_DIR%\backup_helper.ps1"
echo Selesai.

:end
endlocal
