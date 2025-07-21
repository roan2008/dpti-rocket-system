# ==============================================================================
# DPTI Rocket System Project Backup Script
# ==============================================================================
#
# !!! Important: To ensure the script works correctly,
# please save this file with "UTF-8 with BOM" encoding.
#
# How to use:
# 1. Open PowerShell
# 2. Navigate to the project folder: cd c:\xampp\htdocs\dpti-rocket-system
# 3. Run this script: powershell -ExecutionPolicy Bypass -File .\backup.ps1
#
# Result:
# A file named dpti_rocket_system_package.zip will be created on your Desktop.
#
# ==============================================================================

# --- Configuration (Edit as needed) ---
$projectName = "dpti-rocket-system"
$dbName = "dpti_rocket_prod"
$dbUser = "root" # Change if your username is not 'root'
$xamppPath = "C:\xampp"
# --- End of Configuration ---

# --- Variables and Paths (Do not edit) ---
$projectPath = (Get-Location).Path
$desktopPath = [System.Environment]::GetFolderPath('Desktop')
$backupTempDir = Join-Path -Path $desktopPath -ChildPath "_dpti_backup_temp"
$packageFile = Join-Path -Path $desktopPath -ChildPath "$($projectName)_package.zip"
$sqlBackupFile = Join-Path -Path $backupTempDir -ChildPath "$($dbName)_backup.sql"
$mysqlDumpPath = Join-Path -Path $xamppPath -ChildPath "mysql\bin\mysqldump.exe"

# --- Start Process ---
Write-Host "Starting backup process for project '$projectName'..." -ForegroundColor Green

# 1. Check if mysqldump.exe exists
if (-not (Test-Path $mysqlDumpPath)) {
    Write-Host "mysqldump.exe not found at '$mysqlDumpPath'" -ForegroundColor Red
    Write-Host "Please check the xamppPath variable in the script." -ForegroundColor Yellow
    exit
}

# 2. Create a temporary backup folder
Write-Host "1. Creating temporary backup folder..."
if (Test-Path $backupTempDir) {
    Remove-Item -Recurse -Force $backupTempDir
}
New-Item -ItemType Directory -Path $backupTempDir | Out-Null

# 3. Copy all project files to the temporary folder
Write-Host "2. Copying project files..."
Copy-Item -Path "$projectPath\*" -Destination "$backupTempDir\" -Recurse -Exclude "backup.ps1", "setup.ps1"

# 4. Export the database
Write-Host "3. Exporting database '$dbName'..."
Write-Host "   (You may be prompted for the database password)" -ForegroundColor Yellow
$error.clear()
# Use the Call Operator (&) to run the .exe and redirect output to the .sql file
& $mysqlDumpPath -u $dbUser -p $dbName > $sqlBackupFile

if ($LASTEXITCODE -eq 0 -and !$error.Count) {
    Write-Host "   Database export successful" -ForegroundColor Green
} else {
    Write-Host "Database export failed!" -ForegroundColor Red
    if ($error) { $error[0] | Out-String | Write-Host -ForegroundColor Red }
    Write-Host "Aborting process..." -ForegroundColor Yellow
    # Clean up the temp folder on failure
    Remove-Item -Recurse -Force $backupTempDir
    exit
}

# 5. Compress all files into a .zip archive
Write-Host "4. Compressing files to '$($packageFile)'..."
if (Test-Path $packageFile) {
    Remove-Item $packageFile
}
Compress-Archive -Path "$($backupTempDir)\*" -DestinationPath $packageFile -Force

# 6. Clean up the temporary folder
Write-Host "5. Cleaning up temporary files..."
Remove-Item -Recurse -Force $backupTempDir

Write-Host "Backup successful!" -ForegroundColor Cyan
Write-Host "Your package file is ready at: $packageFile"

# 7. Automatically run health check after backup (if php.exe and health_check.php exist)
$phpPath = Join-Path -Path $xamppPath -ChildPath "php\php.exe"
$healthCheckScript = Join-Path -Path $projectPath -ChildPath "health_check.php"

if ((Test-Path $phpPath) -and (Test-Path $healthCheckScript)) {
    Write-Host "6. Running Health Check..."
    $process = Start-Process -FilePath $phpPath -ArgumentList $healthCheckScript -NoNewWindow -Wait -PassThru
    if ($process.ExitCode -eq 0) {
        Write-Host "   Health Check Passed" -ForegroundColor Green
    }
    else {
        Write-Host "   Health Check Failed (Exit Code: $($process.ExitCode))" -ForegroundColor Red
    }
} else {
    Write-Host "6. Skipping Health Check (php.exe or health_check.php not found)" -ForegroundColor Yellow
}

Write-Host "--- Process complete ---" -ForegroundColor Green
