$ErrorActionPreference = 'Stop'
$applicationRoot = $PSScriptRoot
$nodeCommand = Get-Command node -ErrorAction SilentlyContinue
$nodePath = if ($nodeCommand) { $nodeCommand.Source } else { Join-Path $env:USERPROFILE '.cache\codex-runtimes\codex-primary-runtime\dependencies\node\bin\node.exe' }
if (-not (Test-Path -LiteralPath $nodePath)) { Write-Host 'Node.js를 찾을 수 없습니다. https://nodejs.org 에서 LTS 버전을 설치한 뒤 다시 실행하세요.'; Read-Host 'Enter를 누르면 닫습니다'; exit 1 }
$applicationUrl = 'http://127.0.0.1:4173'
function Test-Dayflow { try { $response = Invoke-RestMethod -Uri "$applicationUrl/api/data" -TimeoutSec 2; return $response.schemaVersion -eq 1 } catch { return $false } }
if (-not (Test-Dayflow)) {
    $serverPath = Join-Path $applicationRoot 'server.js'
    $serverProcess = Start-Process -FilePath $nodePath -ArgumentList ('"' + $serverPath + '"') -WorkingDirectory $applicationRoot -WindowStyle Hidden -PassThru
    for ($attempt = 0; $attempt -lt 30; $attempt++) { if (Test-Dayflow) { break }; Start-Sleep -Milliseconds 250 }
    if (-not (Test-Dayflow)) { Write-Host '앱을 시작하지 못했습니다. 4173 포트를 사용하는 다른 앱이 있는지 확인해 주세요.'; Read-Host 'Enter를 누르면 닫습니다'; exit 1 }
}
Start-Process $applicationUrl

