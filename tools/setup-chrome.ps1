# Shomadhan - Chrome helper (setup.bat theke chole)
#  1) Profile er NAAM (jemon "Najiat Islam") theke profile folder khuje (jemon "Profile 1"); na pele "Default".
#  2) Oi profile e bookmarks bar e 2 ta bookmark dey (age theke thakle kichu kore na).
#     Chrome cholle bookmark file bodlano jay na, tai shudhu dorkar holei Chrome ekbar bondho kore.
#  Profile folder er naam -OutFile e likhe dey (setup.bat pore oi profile e tab khule).
#  Windows PowerShell 5.1 e chole.
param(
    [string]$ProfileName = 'Najiat Islam',
    [string]$SiteUrl = 'http://localhost/shomadhan/public/',
    [string]$PmaUrl = 'http://localhost/phpmyadmin/index.php?route=/database/structure&db=shomadhan',
    [string]$OutFile = ''
)

$ErrorActionPreference = 'Stop'
# PS 5.1 bug: eta na korle ConvertTo-Json kokhono array ke {"value":[..],"Count":n} banay
Remove-TypeData System.Array -ErrorAction SilentlyContinue

$userData = Join-Path $env:LOCALAPPDATA 'Google\Chrome\User Data'

# ---------- 1) profile folder khuji (Local State file theke)
$profileDir = 'Default'
try {
    $ls = Join-Path $userData 'Local State'
    if (Test-Path -LiteralPath $ls) {
        $j = ConvertFrom-Json (Get-Content -LiteralPath $ls -Raw -Encoding UTF8)
        foreach ($p in $j.profile.info_cache.PSObject.Properties) {
            if ($p.Value.name -eq $ProfileName -or $p.Value.gaia_name -eq $ProfileName) { $profileDir = $p.Name; break }
        }
    }
} catch {
    Write-Host '  [!] Chrome er Local State pora gelo na, Default profile nichchi.'
}
Write-Host ("  Chrome profile: {0} ({1})" -f $ProfileName, $profileDir)
if ($OutFile) { [IO.File]::WriteAllText($OutFile, $profileDir, [Text.Encoding]::ASCII) }

# ---------- 2) bookmarks
$profilePath = Join-Path $userData $profileDir
if (-not (Test-Path -LiteralPath $profilePath)) {
    Write-Host '  [!] Chrome profile folder pawa jayni, bookmark deya holo na.'
    exit 0
}
$bmFile = Join-Path $profilePath 'Bookmarks'

$wanted = @(
    @{ Name = 'Shomadhan - Welcome';  Url = $SiteUrl },
    @{ Name = 'Shomadhan - Database'; Url = $PmaUrl }
)

# Chrome er shomoy: 1601-01-01 UTC theke microsecond (string)
function Get-ChromeTime {
    $epoch = New-Object DateTime 1601, 1, 1, 0, 0, 0, ([DateTimeKind]::Utc)
    return [string][int64](([DateTime]::UtcNow - $epoch).Ticks / 10)
}

function New-RootFolder([string]$id, [string]$name, [string]$guid) {
    return [pscustomobject][ordered]@{
        children      = @()
        date_added    = (Get-ChromeTime)
        date_modified = '0'
        guid          = $guid
        id            = $id
        name          = $name
        type          = 'folder'
    }
}

# file na thakle choto ekta valid Bookmarks banai
function New-BookmarksData {
    return [pscustomobject][ordered]@{
        roots = [pscustomobject][ordered]@{
            bookmark_bar = (New-RootFolder '1' 'Bookmarks bar'    '0bc5d13f-2cba-5d74-951f-3f233fe6c908')
            other        = (New-RootFolder '2' 'Other bookmarks'  '82b081ec-3dd3-529c-8475-ab6c344590dd')
            synced       = (New-RootFolder '3' 'Mobile bookmarks' '4cf2e351-0e85-532b-bb37-df045d8f8d0f')
        }
        version = 1
    }
}

function Read-Bookmarks {
    if (Test-Path -LiteralPath $bmFile) {
        return ConvertFrom-Json (Get-Content -LiteralPath $bmFile -Raw -Encoding UTF8)
    }
    return New-BookmarksData
}

# sob node ghure: url gulo, sobcheye boro id, ar koto gobhir (depth) ta jani
function Get-Info($data) {
    $info = @{ Urls = @{}; MaxId = [int64]0; Depth = 0 }
    $stack = New-Object System.Collections.Stack
    foreach ($r in $data.roots.PSObject.Properties) {
        if ($r.Value -is [System.Management.Automation.PSCustomObject]) { $stack.Push(@($r.Value, 1)) }
    }
    while ($stack.Count -gt 0) {
        $item = $stack.Pop()
        $n = $item[0]; $d = $item[1]
        if ($d -gt $info.Depth) { $info.Depth = $d }
        $idVal = [int64]0
        if ($n.id -and [int64]::TryParse([string]$n.id, [ref]$idVal)) {
            if ($idVal -gt $info.MaxId) { $info.MaxId = $idVal }
        }
        if ($n.url) { $info.Urls[([string]$n.url).Trim().ToLowerInvariant()] = $true }
        if ($n.children) { foreach ($c in @($n.children)) { if ($c) { $stack.Push(@($c, $d + 1)) } } }
    }
    return $info
}

function Get-Missing($data) {
    $info = Get-Info $data
    return @($wanted | Where-Object { -not $info.Urls.ContainsKey($_.Url.Trim().ToLowerInvariant()) })
}

try {
    $data = Read-Bookmarks
    $missing = @(Get-Missing $data)
    if ($missing.Count -eq 0) {
        Write-Host '  [OK] Chrome bookmark age thekei ache.'
        exit 0
    }

    # ---- Chrome cholle bondho kori (na hole Chrome amader lekha muche dibe)
    if (Get-Process -Name chrome -ErrorAction SilentlyContinue) {
        Write-Host '  Bookmark dite Chrome ekbar bondho korchi (ektu por abar khulbe)...'
        & cmd.exe /c 'taskkill /IM chrome.exe >nul 2>&1'
        for ($i = 0; $i -lt 20 -and (Get-Process -Name chrome -ErrorAction SilentlyContinue); $i++) {
            Start-Sleep -Milliseconds 500
        }
        if (Get-Process -Name chrome -ErrorAction SilentlyContinue) {
            & cmd.exe /c 'taskkill /F /T /IM chrome.exe >nul 2>&1'
            Start-Sleep -Seconds 2
        }
        Start-Sleep -Seconds 1
        # Chrome bondho howar shomoy file abar lekhe, tai notun kore pori
        $data = Read-Bookmarks
        $missing = @(Get-Missing $data)
        if ($missing.Count -eq 0) {
            Write-Host '  [OK] Chrome bookmark age thekei ache.'
            exit 0
        }
    }

    $info = Get-Info $data
    # ConvertTo-Json -Depth 100 er beshi gobhir hole data harabe, tai tokhon kichu kori na
    if ($info.Depth -gt 45) {
        Write-Host '  [!] Bookmark folder onek gobhir, file bodlalam na.'
        exit 0
    }

    $bar = $data.roots.bookmark_bar
    if (-not $bar) {
        $bar = New-RootFolder ([string]($info.MaxId + 1)) 'Bookmarks bar' '0bc5d13f-2cba-5d74-951f-3f233fe6c908'
        $info.MaxId++
        $data.roots | Add-Member -NotePropertyName bookmark_bar -NotePropertyValue $bar
    }
    if (-not ($bar.PSObject.Properties.Name -contains 'children')) {
        $bar | Add-Member -NotePropertyName children -NotePropertyValue @()
    }

    $nextId = $info.MaxId + 1
    $list = @($bar.children | Where-Object { $_ })
    foreach ($w in $missing) {
        $list += [pscustomobject][ordered]@{
            date_added     = (Get-ChromeTime)
            date_last_used = '0'
            guid           = [guid]::NewGuid().ToString()
            id             = [string]$nextId
            name           = $w.Name
            type           = 'url'
            url            = $w.Url
        }
        $nextId++
    }
    $bar.children = [object[]]$list

    # checksum muche dei, na hole Chrome bodlano file mane na
    $data.PSObject.Properties.Remove('checksum')

    if (Test-Path -LiteralPath $bmFile) {
        Copy-Item -LiteralPath $bmFile -Destination ($bmFile + '.shomadhan-backup') -Force
    }
    $json = ConvertTo-Json -InputObject $data -Depth 100 -Compress
    [IO.File]::WriteAllText($bmFile, $json, (New-Object Text.UTF8Encoding $false))
    Write-Host ('  [OK] Chrome bookmarks bar e {0} ta bookmark deya holo.' -f $missing.Count)
} catch {
    Write-Host ('  [!] Chrome bookmark deya gelo na: ' + $_.Exception.Message)
}
exit 0
