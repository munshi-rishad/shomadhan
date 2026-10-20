# Shomadhan - auto-start helper (setup.bat theke chole)
# Current user er Startup folder e "Shomadhan-XAMPP-autostart.vbs" rakhe (protibar notun kore lekhe).
# Windows e login korlei eta lukiye (window chara) XAMPP MySQL ar Apache chalu kore, jodi age theke na chole.
# Admin lagbe na. Bondho korte chaile Startup folder theke file ta muche din.
param(
    [string]$XamppDir = 'C:\xampp'
)

$ErrorActionPreference = 'Stop'
try {
    $startup = [Environment]::GetFolderPath('Startup')
    if (-not $startup) { $startup = Join-Path $env:APPDATA 'Microsoft\Windows\Start Menu\Programs\Startup' }
    if (-not (Test-Path -LiteralPath $startup)) { New-Item -ItemType Directory -Path $startup -Force | Out-Null }
    $vbs = Join-Path $startup 'Shomadhan-XAMPP-autostart.vbs'

    # VBScript e " ke "" likhte hoy
    $x = $XamppDir.TrimEnd('\').Replace('"', '""')
    $lines = @(
        "' Shomadhan: Windows chalu hole XAMPP MySQL ar Apache lukiye chalu kore (setup.bat baniyeche)",
        "' Bondho korte chaile ei file ta muche din.",
        'Option Explicit',
        'Dim sh, fso, xampp',
        'Set sh = CreateObject("WScript.Shell")',
        'Set fso = CreateObject("Scripting.FileSystemObject")',
        ('xampp = "{0}"' -f $x),
        '',
        'Function IsRunning(exeName)',
        '    Dim wmi, procs',
        '    IsRunning = False',
        '    On Error Resume Next',
        '    Set wmi = GetObject("winmgmts:\\.\root\cimv2")',
        '    Set procs = wmi.ExecQuery("SELECT ProcessId FROM Win32_Process WHERE Name = ''" & exeName & "''")',
        '    If Err.Number = 0 Then IsRunning = (procs.Count > 0)',
        '    On Error GoTo 0',
        'End Function',
        '',
        'Sub StartHidden(batName)',
        '    Dim bat',
        '    bat = xampp & "\" & batName',
        '    If fso.FileExists(bat) Then',
        '        sh.CurrentDirectory = xampp',
        '        sh.Run "cmd /c """ & bat & """", 0, False',
        '    End If',
        'End Sub',
        '',
        'If Not IsRunning("mysqld.exe") Then StartHidden "mysql_start.bat"',
        'If Not IsRunning("httpd.exe") Then StartHidden "apache_start.bat"'
    )
    [IO.File]::WriteAllText($vbs, (($lines -join "`r`n") + "`r`n"), [Text.Encoding]::ASCII)
    Write-Host '  [OK] Windows chalu holei MySQL ar Apache nije chalu hobe (Startup e file rakha holo).'
} catch {
    Write-Host ('  [!] Auto-start file banano gelo na: ' + $_.Exception.Message)
}
exit 0
