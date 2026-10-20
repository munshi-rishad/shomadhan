# Shomadhan - XAMPP chalu kora (setup.bat sobar age eta chalay). Kono prosno / "Yes" chaoa hoy na, sob nije nije.
# 1) MySQL ar Apache bondho thakle lukiye chalu kore, chalu howa porjonto opekkha kore.
#    (XAMPP Control Panel eder nijei chine nay: kichukkhon por sobuj dekhay, sekhan theke Stop/Start kora jay.)
# 2) MySQL "shutdown unexpectedly" hole nijei thik kore (XAMPP er backup theke, apnar database gulo rekhe) ar abar chalu kore.
# 3) XAMPP Control Panel khola na thakle choto kore khule dey.
# exit 0 = MySQL cholche, exit 1 = MySQL chalu holo na.
param(
    [string]$XamppDir = 'C:\xampp'
)

$x     = $XamppDir.TrimEnd('\')
$panel = Join-Path $x 'xampp-control.exe'

function Test-Running([string]$name) {
    return [bool](Get-Process -Name $name -ErrorAction SilentlyContinue)
}

function Test-Port([int]$port) {
    $c = New-Object System.Net.Sockets.TcpClient
    try {
        $r = $c.BeginConnect('127.0.0.1', $port, $null, $null)
        return ($r.AsyncWaitHandle.WaitOne(500) -and $c.Connected)
    } catch { return $false } finally { $c.Close() }
}

function Wait-Port([int]$port, [int]$seconds) {
    for ($i = 0; $i -lt $seconds; $i++) {
        if (Test-Port $port) { return $true }
        Start-Sleep -Seconds 1
    }
    return $false
}

# ---- lukiye (window chara) chalu kora
function Start-Hidden([string]$batName) {
    $bat = Join-Path $x $batName
    if (Test-Path -LiteralPath $bat) { Start-Process -FilePath 'cmd.exe' -ArgumentList @('/c', ('"' + $bat + '"')) -WorkingDirectory $x -WindowStyle Hidden }
    else { Write-Host ('  [!] Pawa jayni: ' + $bat) }
}

function Start-MySQL {
    Start-Hidden 'mysql_start.bat'
    return (Wait-Port 3306 30)
}

# ---- MySQL "shutdown unexpectedly" thik kora (fix-mysql.bat er moto, prosno chara):
#      data folder ta data_old_<tarikh> name e rekhe XAMPP er backup theke notun data banai,
#      tarpor apnar database folder gulo (shomadhan etc.) ar ibdata1 ferot ani. Kichu muche na.
function Repair-MySQL {
    $data = Join-Path $x 'mysql\data'
    $bak  = Join-Path $x 'mysql\backup'
    if (-not (Test-Path -LiteralPath $bak) -or -not (Test-Path -LiteralPath $data)) { Write-Host '  [!] XAMPP er mysql\backup ba mysql\data pawa jayni.'; return $false }
    Get-Process -Name 'mysqld' -ErrorAction SilentlyContinue | Stop-Process -Force -ErrorAction SilentlyContinue
    Start-Sleep -Seconds 2
    $old = Join-Path $x ('mysql\data_old_' + (Get-Date -Format 'yyyyMMdd_HHmmss'))
    try {
        Move-Item -LiteralPath $data -Destination $old -ErrorAction Stop
        Copy-Item -LiteralPath $bak -Destination $data -Recurse -ErrorAction Stop
        foreach ($d in Get-ChildItem -LiteralPath $old -Directory) {
            if (@('mysql', 'performance_schema', 'phpmyadmin', 'test') -notcontains $d.Name) {
                Copy-Item -LiteralPath $d.FullName -Destination (Join-Path $data $d.Name) -Recurse -Force -ErrorAction Stop
            }
        }
        $ib = Join-Path $old 'ibdata1'
        if (Test-Path -LiteralPath $ib) { Copy-Item -LiteralPath $ib -Destination (Join-Path $data 'ibdata1') -Force }
        Write-Host ('  [OK] MySQL thik kora holo. Ager data rakha ache: ' + $old)
        return $true
    } catch {
        Write-Host ('  [X] MySQL thik kora gelo na: ' + $_.Exception.Message)
        if (-not (Test-Path -LiteralPath $data) -and (Test-Path -LiteralPath $old)) { Move-Item -LiteralPath $old -Destination $data -ErrorAction SilentlyContinue }
        return $false
    }
}

$mysqlUp  = Test-Port 3306
$apacheUp = Test-Running 'httpd'

# ---- MySQL
$ok = $true
if ($mysqlUp) { Write-Host '  [OK] MySQL age thekei cholche.' }
else {
    Write-Host '  MySQL bondho. Chalu korchi...'
    $up = Start-MySQL
    if (-not $up) {
        Write-Host '  MySQL chalu hocche na ("shutdown unexpectedly"). Nijei thik korchi...'
        if (Repair-MySQL) { $up = Start-MySQL }
    }
    if ($up) { Write-Host '  [OK] MySQL chalu holo.' }
    else {
        Write-Host '  [X] MySQL chalu holo na. XAMPP Control Panel e MySQL er Logs er screenshot pathan.'
        $ok = $false
    }
}

# ---- Apache
if ($apacheUp) { Write-Host '  [OK] Apache age thekei cholche.' }
elseif (Test-Port 80) {
    Write-Host '  [!] Port 80 onno kono program (Skype / IIS / onno server) dhore rekheche, tai Apache chalu hobe na.'
    Write-Host '      Oi program bondho kore abar setup.bat chalan.'
} else {
    Write-Host '  Apache bondho. Chalu korchi...'
    Start-Hidden 'apache_start.bat'
    if (Wait-Port 80 20) { Write-Host '  [OK] Apache chalu holo.' } else { Write-Host '  [!] Apache chalu holo na. XAMPP Control Panel e Apache er Logs dekhun.' }
}

# ---- XAMPP Control Panel (choto kore), sekhane MySQL / Apache sobuj dekhabe
if (-not (Test-Running 'xampp-control') -and (Test-Path -LiteralPath $panel)) {
    Start-Process -FilePath $panel -WorkingDirectory $x -WindowStyle Minimized
}

if ($ok) { exit 0 } else { exit 1 }
