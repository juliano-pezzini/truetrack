<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Create a fake Vite manifest for testing
        $this->createFakeViteManifest();
    }

    protected function tearDown(): void
    {
        // Clean up the fake manifest
        $this->removeFakeViteManifest();

        parent::tearDown();
    }

    /**
     * Create a minimal fake Vite manifest.json for testing.
     */
    protected function createFakeViteManifest(): void
    {
        $publicPath = public_path('build');
        $manifestPath = $publicPath . '/manifest.json';

        // Create directory if it doesn't exist
        if (!is_dir($publicPath)) {
            mkdir($publicPath, 0755, true);
        }

        // Create a minimal manifest with the main app entry point
        $manifest = [
            'resources/js/app.jsx' => [
                'file' => 'assets/app.js',
                'src' => 'resources/js/app.jsx',
                'isEntry' => true,
            ],
            'resources/css/app.css' => [
                'file' => 'assets/app.css',
                'src' => 'resources/css/app.css',
                'isEntry' => true,
            ],
        ];

        file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT));
    }

    /**
     * Remove the fake Vite manifest after tests.
     */
    protected function removeFakeViteManifest(): void
    {
        $manifestPath = public_path('build/manifest.json');

        if (file_exists($manifestPath)) {
            unlink($manifestPath);
        }

        // Remove the build directory if it's empty
        $buildDir = public_path('build');
        if (is_dir($buildDir) && count(scandir($buildDir)) === 2) { // Only . and ..
            rmdir($buildDir);
        }
    }
}
