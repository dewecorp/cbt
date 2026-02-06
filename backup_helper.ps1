$source = "D:\laragon\www\cbt"
$dest = "D:\laragon\www\cbt\backups\cbt-backup.zip"
$exclude = @("\\.git\\", "\\backups\\", "\\vendor\\google\\", "\\vendor\\composer\\39897f00\\", "\\vendor\\composer\\f436f662\\", "\\.vscode\\")

Add-Type -Assembly System.IO.Compression.FileSystem

Write-Host "Creating backup..."
if (Test-Path $dest) { Remove-Item $dest -Force }

$zip = [System.IO.Compression.ZipFile]::Open($dest, "Create")

$files = Get-ChildItem $source -Recurse
foreach ($file in $files) {
    if ($file.Attributes -band [System.IO.FileAttributes]::Directory) { continue }
    
    # Check exclusion
    $skip = $false
    foreach ($exc in $exclude) {
        if ($file.FullName -match $exc) { $skip = $true; break }
    }
    if ($skip) { continue }

    $relPath = $file.FullName.Substring($source.Length + 1)
    # Ensure relative path uses forward slashes for zip spec or backslashes work?
    # ZipFile usually handles it, but let's be safe? Windows works fine.
    
    [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, $file.FullName, $relPath)
}

$zip.Dispose()
Write-Host "Backup created at $dest"
