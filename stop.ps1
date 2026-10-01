$serverPath = [System.IO.Path]::GetFullPath((Join-Path $PSScriptRoot 'server.js'))
Get-CimInstance Win32_Process -Filter "Name = 'node.exe'" | Where-Object {
    $_.CommandLine -and $_.CommandLine.Contains($serverPath)
} | ForEach-Object { Stop-Process -Id $_.ProcessId -ErrorAction SilentlyContinue }
