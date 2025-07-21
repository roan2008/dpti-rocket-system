# ==============================================================================
# สคริปต์สำหรับติดตั้งโปรเจกต์ DPTI Rocket System (Setup Script)
# ==============================================================================
#
# วิธีใช้:
# 1. ตรวจสอบว่า XAMPP ถูกติดตั้งที่ C:\xampp และทำงานอยู่
# 2. นำไฟล์นี้ (setup.ps1) และไฟล์ dpti_rocket_system_package.zip
#    ไปวางไว้ที่เดียวกัน (เช่น บน Desktop)
# 3. เปิด PowerShell
# 4. ไปยังตำแหน่งที่วางไฟล์: cd $env:USERPROFILE\Desktop
# 5. รันสคริปต์นี้: .\setup.ps1
#
# ==============================================================================

# --- การตั้งค่า (สามารถแก้ไขได้ตามต้องการ) ---
$projectName = "dpti-rocket-system"
$dbName = "dpti_rocket_prod"
$dbUser = "root" # แก้ไขหาก username ไม่ใช่ root
$xamppPath = "C:\xampp"
# --- สิ้นสุดการตั้งค่า ---

# --- ตัวแปรและเส้นทาง (ไม่ต้องแก้ไข) ---
$scriptPath = $PSScriptRoot
$packageFile = "$scriptPath\$($projectName)_package.zip"
$setupTempDir = "$scriptPath\_dpti_setup_temp"
$projectDestPath = "$xamppPath\htdocs\$projectName"
$sqlBackupFile = "$setupTempDir\$($dbName)_backup.sql"
$mysqlPath = "$xamppPath\mysql\bin\mysql.exe"

# --- เริ่มกระบวนการ ---
Write-Host "🚀 เริ่มกระบวนการติดตั้งโปรเจกต์ '$projectName'..." -ForegroundColor Green

# 1. ตรวจสอบไฟล์และโปรแกรมที่จำเป็น
if (-not (Test-Path $packageFile)) {
    Write-Host "❌ ไม่พบไฟล์แพ็กเกจ '$packageFile'" -ForegroundColor Red
    Write-Host "กรุณานำไฟล์ .zip มาวางไว้ที่เดียวกับสคริปต์นี้" -ForegroundColor Yellow
    exit
}
if (-not (Test-Path $mysqlPath)) {
    Write-Host "❌ ไม่พบไฟล์ mysql.exe ที่ '$mysqlPath'" -ForegroundColor Red
    Write-Host "กรุณาตรวจสอบว่า XAMPP ถูกติดตั้งอย่างถูกต้อง" -ForegroundColor Yellow
    exit
}

# 2. สร้างโฟลเดอร์ชั่วคราวสำหรับติดตั้ง
Write-Host "1. กำลังเตรียมไฟล์สำหรับการติดตั้ง..."
if (Test-Path $setupTempDir) {
    Remove-Item -Recurse -Force $setupTempDir
}
New-Item -ItemType Directory -Path $setupTempDir | Out-Null

# 3. แตกไฟล์ .zip
Write-Host "2. กำลังแตกไฟล์โปรเจกต์..."
Expand-Archive -Path $packageFile -DestinationPath $setupTempDir -Force

# 4. ตรวจสอบว่าโปรเจกต์ปลายทางมีอยู่หรือไม่
if (Test-Path $projectDestPath) {
    Write-Host "พบโปรเจกต์ '$projectName' ที่ '$($xamppPath)\htdocs' อยู่แล้ว" -ForegroundColor Yellow
    $choice = Read-Host "คุณต้องการเขียนทับหรือไม่? (Y/N)"
    if ($choice -ne 'Y' -and $choice -ne 'y') {
        Write-Host "ยกเลิกการติดตั้ง" -ForegroundColor Red
        exit
    }
    Remove-Item -Recurse -Force $projectDestPath
}

# 5. ย้ายไฟล์โปรเจกต์ไปยัง htdocs
Write-Host "3. กำลังติดตั้งไฟล์โปรเจกต์ไปยัง '$projectDestPath'..."
Move-Item -Path "$setupTempDir\*" -Destination $projectDestPath -Force

# 6. สร้างและนำเข้าฐานข้อมูล
Write-Host "4. กำลังสร้างและนำเข้าฐานข้อมูล '$dbName'..."
Write-Host "   (ระบบอาจถามรหัสผ่านฐานข้อมูลของคุณ)"
try {
    # สร้าง database ใหม่ (จะ error ถ้ามีอยู่แล้ว แต่ไม่เป็นไร)
    $createDbCommand = "CREATE DATABASE IF NOT EXISTS \`$dbName\` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
    $createDbCommand | & $mysqlPath -u $dbUser -p
    
    # นำเข้าข้อมูลจากไฟล์ .sql
    Get-Content $projectDestPath\$($dbName)_backup.sql | & $mysqlPath -u $dbUser -p $dbName
    
    Write-Host "   ✅ สร้างและนำเข้าฐานข้อมูลสำเร็จ" -ForegroundColor Green
} catch {
    Write-Host "❌ เกิดข้อผิดพลาดระหว่างการจัดการฐานข้อมูล!" -ForegroundColor Red
    Write-Host $_.Exception.Message
    exit
}


# 7. ลบไฟล์และโฟลเดอร์ชั่วคราว
Write-Host "5. กำลังล้างไฟล์ชั่วคราว..."
Remove-Item -Path $projectDestPath\$($dbName)_backup.sql -Force
Remove-Item -Recurse -Force $setupTempDir

Write-Host "✅ ติดตั้งสำเร็จ!" -ForegroundColor Green
Write-Host "คุณสามารถเข้าถึงโปรเจกต์ได้ที่: http://localhost/$projectName/"

# 8. รัน health check อัตโนมัติ
Write-Host "\n🚀 กำลังตรวจสอบความพร้อมของระบบ (Health Check)..." -ForegroundColor Cyan
$healthCheckScript = "$projectDestPath\..\$projectName\run_health_check.ps1"
if (Test-Path $healthCheckScript) {
    & $healthCheckScript
} else {
    Write-Host "❌ ไม่พบไฟล์ run_health_check.ps1" -ForegroundColor Red
    Write-Host "กรุณาตรวจสอบว่าไฟล์นี้อยู่ในโฟลเดอร์โปรเจกต์"
}
