param(
    [string]$SourceRoot = "$env:USERPROFILE\.antigravity\extensions",
    [string]$TargetRoot = "$env:USERPROFILE\.vscode\extensions",
    [switch]$PruneOldVersions
)

$ErrorActionPreference = 'Stop'

function Get-LatestCodexExtension {
    param(
        [string]$Root
    )

    return Get-ChildItem -Path $Root -Directory -Filter 'openai.chatgpt-*' |
        Sort-Object Name -Descending |
        Select-Object -First 1
}

$latestExtension = Get-LatestCodexExtension -Root $SourceRoot

if (-not $latestExtension) {
    throw "Khong tim thay extension Codex trong '$SourceRoot'."
}

New-Item -ItemType Directory -Force -Path $TargetRoot | Out-Null

$targetPath = Join-Path $TargetRoot $latestExtension.Name

if (Test-Path $targetPath) {
    Remove-Item -Path $targetPath -Recurse -Force
}

Copy-Item -Path $latestExtension.FullName -Destination $targetPath -Recurse -Force

if ($PruneOldVersions) {
    Get-ChildItem -Path $TargetRoot -Directory -Filter 'openai.chatgpt-*' |
        Where-Object { $_.FullName -ne $targetPath } |
        Remove-Item -Recurse -Force
}

$packageJsonPath = Join-Path $targetPath 'package.json'
if (-not (Test-Path $packageJsonPath)) {
    throw "Da copy extension nhung thieu package.json tai '$packageJsonPath'."
}

$packageInfo = Get-Content -Path $packageJsonPath -Raw | ConvertFrom-Json

Write-Host "Codex da duoc sync vao VS Code."
Write-Host "Extension: $($packageInfo.publisher).$($packageInfo.name)"
Write-Host "Version:   $($packageInfo.version)"
Write-Host "Source:    $($latestExtension.FullName)"
Write-Host "Target:    $targetPath"
Write-Host "Hay reload VS Code de kich hoat extension moi."
