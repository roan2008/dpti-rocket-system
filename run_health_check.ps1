# ============================================================================== 
# DPTI Rocket System - Post-Setup Health Check (Auto)
# ============================================================================== 
#
# สคริปต์นี้จะถูกรันโดยอัตโนมัติหลังจากติดตั้งโปรเจกต์เสร็จ (ใน setup.ps1)
# เพื่อพิสูจน์ว่าโปรเจกต์พร้อมใช้งานจริง 100% บนเครื่องใหม่
#
# ==============================================================================

# --- ตัวแปร ---
$projectName = "dpti-rocket-system"
$htdocsPath = "C:\xampp\htdocs\$projectName"
$phpPath = "C:\xampp\php\php.exe"
$healthCheckScript = "$htdocsPath\health_check.php"

# --- ตรวจสอบไฟล์ health_check.php ---
if (-not (Test-Path $healthCheckScript)) {
    Write-Host "❌ ไม่พบไฟล์ health_check.php ในโปรเจกต์" -ForegroundColor Red
    Write-Host "กรุณาตรวจสอบการติดตั้งไฟล์ใน $htdocsPath" -ForegroundColor Yellow
    exit 1
}

# --- ตรวจสอบ php.exe ---
if (-not (Test-Path $phpPath)) {
    Write-Host "❌ ไม่พบ php.exe ที่ $phpPath" -ForegroundColor Red
    Write-Host "กรุณาตรวจสอบการติดตั้ง XAMPP" -ForegroundColor Yellow
    exit 1
}

Write-Host "\n🚀 Running post-setup health check..." -ForegroundColor Cyan

# --- รัน health_check.php ---
$process = Start-Process -FilePath $phpPath -ArgumentList $healthCheckScript -NoNewWindow -Wait -PassThru

if ($process.ExitCode -eq 0) {
    Write-Host "\n✅ Health check passed! The system is ready to use." -ForegroundColor Green
    exit 0
} else {
    Write-Host "\n❌ Health check failed. Please review the output above." -ForegroundColor Red
    exit 1
}
