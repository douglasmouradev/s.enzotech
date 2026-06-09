# Backup Enzo Tech — banco + documentos
param(
    [string]$MysqlBin = "C:\xampp\mysql\bin\mysqldump.exe",
    [string]$DbUser = "root",
    [string]$DbPass = "",
    [string]$DbName = "enzo_tech"
)

$timestamp = Get-Date -Format "yyyy-MM-dd_HHmm"
$backupDir = Join-Path $PSScriptRoot "..\backups"
New-Item -ItemType Directory -Force -Path $backupDir | Out-Null

$sqlFile = Join-Path $backupDir "enzo_tech_$timestamp.sql"
$zipFile = Join-Path $backupDir "enzo_tech_$timestamp.zip"

& $MysqlBin -u $DbUser -p$DbPass $DbName > $sqlFile

$uploads = Join-Path $PSScriptRoot "..\uploads\documentos"
Compress-Archive -Path $sqlFile, $uploads -DestinationPath $zipFile -Force
Remove-Item $sqlFile

Write-Host "Backup salvo: $zipFile"
