# Test d'inscription rapide
Write-Host "=== Test d'inscription ===" -ForegroundColor Cyan

$email = "testuser$([int](Get-Random -Maximum 9999))@example.com"
$body = @{
    username = "TestUser"
    email = $email
    password = "password123"
} | ConvertTo-Json

Write-Host "Email de test: $email" -ForegroundColor Yellow
Write-Host "URL: http://localhost/projet%20ismo/api/auth/register.php" -ForegroundColor Yellow
Write-Host ""

try {
    $response = Invoke-RestMethod -Uri "http://localhost/projet%20ismo/api/auth/register.php" -Method Post -ContentType "application/json" -Body $body -SessionVariable session
    Write-Host "SUCCESS!" -ForegroundColor Green
    $response | ConvertTo-Json -Depth 3 | Write-Host
} catch {
    Write-Host "ERREUR:" -ForegroundColor Red
    Write-Host $_.Exception.Message -ForegroundColor Red
    if ($_.Exception.Response) {
        $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
        $reader.BaseStream.Position = 0
        $responseBody = $reader.ReadToEnd()
        Write-Host "Réponse du serveur:" -ForegroundColor Yellow
        Write-Host $responseBody
    }
}
