#!/usr/bin/env pwsh
# Build and optionally push the TrueTrack PHP 8.5 base image

param(
    [string]$Action = "build",  # build, push, or both
    [string]$Registry = "",     # e.g., "yourusername" for Docker Hub, or "ghcr.io/yourusername"
    [switch]$NoCache
)

$ErrorActionPreference = "Stop"

$ImageName = "truetrack-php"
$Tag = "8.5-fpm"

# Determine full image name
if ($Registry) {
    $FullImageName = "$Registry/$ImageName`:$Tag"
    $LocalImageName = "$ImageName`:$Tag"
} else {
    $FullImageName = "$ImageName`:$Tag"
    $LocalImageName = $FullImageName
}

Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "TrueTrack PHP 8.5 Base Image Builder" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""

# Build the base image
if ($Action -eq "build" -or $Action -eq "both") {
    Write-Host "Building base image: $LocalImageName" -ForegroundColor Yellow
    Write-Host ""

    $buildArgs = @(
        "build",
        "-f", "Dockerfile.base-php-8.5",
        "-t", $LocalImageName
    )

    if ($Registry) {
        $buildArgs += @("-t", $FullImageName)
    }

    if ($NoCache) {
        $buildArgs += "--no-cache"
    }

    $buildArgs += "."

    & docker $buildArgs

    if ($LASTEXITCODE -ne 0) {
        Write-Host "❌ Build failed!" -ForegroundColor Red
        exit 1
    }

    Write-Host ""
    Write-Host "✅ Base image built successfully!" -ForegroundColor Green
    Write-Host ""

    # Verify extensions
    Write-Host "Verifying PHP extensions..." -ForegroundColor Yellow
    docker run --rm $LocalImageName php -m | Select-String -Pattern "pdo_pgsql|pgsql|gd|zip|pcntl|bcmath|opcache"
    Write-Host ""
}

# Push to registry
if ($Action -eq "push" -or $Action -eq "both") {
    if (-not $Registry) {
        Write-Host "❌ Error: Registry must be specified for push action" -ForegroundColor Red
        Write-Host "   Example: -Registry yourusername" -ForegroundColor Yellow
        Write-Host "   Example: -Registry ghcr.io/yourusername" -ForegroundColor Yellow
        exit 1
    }

    Write-Host "Pushing image to registry: $FullImageName" -ForegroundColor Yellow
    Write-Host ""

    docker push $FullImageName

    if ($LASTEXITCODE -ne 0) {
        Write-Host "❌ Push failed!" -ForegroundColor Red
        exit 1
    }

    Write-Host ""
    Write-Host "✅ Image pushed successfully!" -ForegroundColor Green
    Write-Host ""
    Write-Host "To use this image, update Dockerfile.production:" -ForegroundColor Cyan
    Write-Host "  FROM $FullImageName" -ForegroundColor White
    Write-Host ""
}

# Show image info
Write-Host "Image Information:" -ForegroundColor Cyan
docker images $LocalImageName --format "table {{.Repository}}\t{{.Tag}}\t{{.Size}}\t{{.CreatedAt}}"
Write-Host ""

Write-Host "Next steps:" -ForegroundColor Cyan
Write-Host "  1. Test the base image: docker run --rm $LocalImageName php -v" -ForegroundColor White
Write-Host "  2. Update Dockerfile.production to use: FROM $LocalImageName" -ForegroundColor White
if ($Registry) {
    Write-Host "  3. Push to registry: .\build-base-image.ps1 -Action push -Registry $Registry" -ForegroundColor White
} else {
    Write-Host "  3. (Optional) Push to Docker Hub: .\build-base-image.ps1 -Action push -Registry yourusername" -ForegroundColor White
}
Write-Host ""
