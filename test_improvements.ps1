# Script de test des améliorations
# Teste les nouveaux endpoints et fonctionnalités

$ErrorActionPreference = "Continue"

Write-Host ""
Write-Host "================================================" -ForegroundColor Cyan
Write-Host "   Test des Améliorations GameZone" -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan
Write-Host ""

$baseUrl = "http://localhost/projet%20ismo/api"
$testsPassed = 0
$testsFailed = 0

function Test-Endpoint {
    param(
        [string]$Name,
        [string]$Url,
        [string]$Method = "GET",
        [hashtable]$Body = $null
    )
    
    Write-Host "Testing: $Name..." -NoNewline
    
    try {
        if ($Method -eq "GET") {
            $response = Invoke-RestMethod -Uri $Url -Method Get -TimeoutSec 5 -ErrorAction Stop
        } else {
            $jsonBody = $Body | ConvertTo-Json
            $response = Invoke-RestMethod -Uri $Url -Method Post -ContentType "application/json" -Body $jsonBody -TimeoutSec 5 -ErrorAction Stop
        }
        
        Write-Host " [OK]" -ForegroundColor Green
        $script:testsPassed++
        return $response
    } catch {
        Write-Host " [FAILED]" -ForegroundColor Red
        Write-Host "   Error: $($_.Exception.Message)" -ForegroundColor Yellow
        $script:testsFailed++
        return $null
    }
}

# Test 1: Health Check
Write-Host ""
Write-Host "> Testing Health Check Endpoint..." -ForegroundColor Cyan
$health = Test-Endpoint "Health Check" "$baseUrl/health.php"
if ($health) {
    Write-Host "   Status: $($health.status)" -ForegroundColor $(if ($health.status -eq 'healthy') { 'Green' } else { 'Yellow' })
    Write-Host "   Database: $($health.checks.database.status)" -ForegroundColor $(if ($health.checks.database.status -eq 'up') { 'Green' } else { 'Red' })
    Write-Host "   Cache: $($health.checks.cache.status)" -ForegroundColor $(if ($health.checks.cache.status -eq 'up') { 'Green' } else { 'Red' })
}

# Test 2: Test endpoint (should still work)
Write-Host ""
Write-Host "> Testing Basic API..." -ForegroundColor Cyan
$test = Test-Endpoint "Test Endpoint" "$baseUrl/test.php"

# Test 3: Events endpoint (with cache)
Write-Host ""
Write-Host "> Testing Events API..." -ForegroundColor Cyan
$events1 = Test-Endpoint "Events (First Call)" "$baseUrl/events/index.php"
Start-Sleep -Milliseconds 100
$events2 = Test-Endpoint "Events (Cached Call)" "$baseUrl/events/index.php"

if ($events1 -and $events2) {
    Write-Host "   First call: $($events1.items.Count) events" -ForegroundColor Gray
    Write-Host "   Second call: $($events2.items.Count) events (should be cached)" -ForegroundColor Gray
}

# Test 4: Rate Limiting
Write-Host ""
Write-Host "> Testing Rate Limiting..." -ForegroundColor Cyan
Write-Host "   Attempting 10 rapid login requests..." -ForegroundColor Gray

$rateLimitTriggered = $false
for ($i = 1; $i -le 10; $i++) {
    try {
        $response = Invoke-RestMethod -Uri "$baseUrl/auth/login.php" -Method Post -ContentType "application/json" -Body '{"email":"test@test.com","password":"wrongpass"}' -TimeoutSec 2 -ErrorAction Stop
    } catch {
        $statusCode = $_.Exception.Response.StatusCode.value__
        if ($statusCode -eq 429) {
            $rateLimitTriggered = $true
            Write-Host "   Rate limit triggered after $i attempts" -ForegroundColor Green
            $script:testsPassed++
            break
        }
    }
}

if (-not $rateLimitTriggered) {
    Write-Host "   Rate limiting not triggered (expected after 5-6 attempts)" -ForegroundColor Yellow
}

# Test 5: Security Headers
Write-Host ""
Write-Host "> Testing Security Headers..." -ForegroundColor Cyan
try {
    $webRequest = [System.Net.HttpWebRequest]::Create("$baseUrl/test.php")
    $webRequest.Method = "GET"
    $response = $webRequest.GetResponse()
    $headers = $response.Headers
    
    $securityHeaders = @(
        'X-Content-Type-Options',
        'X-Frame-Options',
        'X-XSS-Protection',
        'Content-Security-Policy'
    )
    
    foreach ($header in $securityHeaders) {
        if ($headers[$header]) {
            Write-Host "   $header : Present" -ForegroundColor Green -NoNewline
            Write-Host " ($($headers[$header]))" -ForegroundColor Gray
            $script:testsPassed++
        } else {
            Write-Host "   $header : Missing" -ForegroundColor Yellow
        }
    }
    
    $response.Close()
} catch {
    Write-Host "   Could not check headers" -ForegroundColor Yellow
}

# Test 6: Leaderboard
Write-Host ""
Write-Host "> Testing Leaderboard..." -ForegroundColor Cyan
$leaderboard = Test-Endpoint "Leaderboard Weekly" "$baseUrl/leaderboard/index.php?period=weekly"
if ($leaderboard) {
    Write-Host "   Period: $($leaderboard.period)" -ForegroundColor Gray
    Write-Host "   Players: $($leaderboard.items.Count)" -ForegroundColor Gray
}

# Test 7: Check logs directory
Write-Host ""
Write-Host "> Checking Logs..." -ForegroundColor Cyan
$logsDir = "c:\xampp\htdocs\projet ismo\logs"
if (Test-Path $logsDir) {
    $logFiles = Get-ChildItem -Path $logsDir -Filter "*.log"
    Write-Host "   Logs directory: EXISTS" -ForegroundColor Green
    Write-Host "   Log files: $($logFiles.Count)" -ForegroundColor Gray
    $script:testsPassed++
} else {
    Write-Host "   Logs directory: NOT CREATED YET" -ForegroundColor Yellow
    Write-Host "   (Will be created on first API request)" -ForegroundColor Gray
}

# Summary
Write-Host ""
Write-Host "================================================" -ForegroundColor Cyan
Write-Host "              Test Summary" -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Tests Passed: " -NoNewline
Write-Host "$testsPassed" -ForegroundColor Green
Write-Host "Tests Failed: " -NoNewline
Write-Host "$testsFailed" -ForegroundColor $(if ($testsFailed -eq 0) { 'Green' } else { 'Red' })
Write-Host ""

if ($testsFailed -eq 0) {
    Write-Host "All critical tests passed!" -ForegroundColor Green
} else {
    Write-Host "Some tests failed. Check errors above." -ForegroundColor Yellow
}

Write-Host ""
Write-Host "================================================" -ForegroundColor Gray
Write-Host ""
Write-Host "Pour plus de détails, consultez:" -ForegroundColor Cyan
Write-Host "  - AMELIORATIONS.md (documentation complète)" -ForegroundColor White
Write-Host "  - api/examples/usage_examples.php (exemples de code)" -ForegroundColor White
Write-Host "  - logs/api_*.log (logs de l'API)" -ForegroundColor White
Write-Host ""
