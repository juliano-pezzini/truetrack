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

        // Withouth verifying CSRF token in tests for web routes
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
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
        $manifestPath = $publicPath.'/manifest.json';

        // Create directory if it doesn't exist
        if (! is_dir($publicPath)) {
            mkdir($publicPath, 0755, true);
        }

        // Create a comprehensive manifest with all page components
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

        // Add all page components dynamically
        $pagesPath = resource_path('js/Pages');
        if (is_dir($pagesPath)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($pagesPath, \RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'jsx') {
                    $relativePath = $this->getRelativeJsxPath($file->getPathname(), $pagesPath);

                    $manifest[$relativePath] = [
                        'file' => 'assets/'.str_replace(['/', '.jsx'], ['-', '.js'], $relativePath),
                        'src' => $relativePath,
                        'isEntry' => true,
                    ];
                }
            }
        }

        file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT));
    }

    /**
     * Remove the fake Vite manifest after tests.
     */
    protected function removeFakeViteManifest(): void
    {
        $manifestPath = public_path('build/manifest.json');

        if (file_exists($manifestPath)) {
            @unlink($manifestPath); // Suppress permission errors
        }

        // Remove the build directory if it's empty
        $buildDir = public_path('build');
        if (is_dir($buildDir)) {
            $iterator = new \FilesystemIterator($buildDir, \FilesystemIterator::SKIP_DOTS);

            if (! $iterator->valid()) {
                @rmdir($buildDir); // Suppress permission errors
            }
        }
    }

    /**
     * Get relative path for JSX file.
     */
    private function getRelativeJsxPath(string $fullPath, string $basePath): string
    {
        $relativePath = 'resources/js/Pages/'.str_replace($basePath.DIRECTORY_SEPARATOR, '', $fullPath);

        return str_replace('\\', '/', $relativePath); // Normalize path separators
    }
}
