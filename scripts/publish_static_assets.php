<?php

declare(strict_types=1);

/**
 * Copy frontend static assets from resources/ into public/ and rewrite common
 * asset references for WAMP production deployments.
 *
 * Usage:
 *   php scripts/publish_static_assets.php
 *   php scripts/publish_static_assets.php --dry-run
 */

$root = realpath(__DIR__ . '/..');
if ($root === false) {
    fwrite(STDERR, "Unable to resolve project root.\n");
    exit(1);
}

$dryRun = in_array('--dry-run', $argv, true);
$resourceRoot = $root . DIRECTORY_SEPARATOR . 'resources';
$publicRoot = $root . DIRECTORY_SEPARATOR . 'public';

$assetExtensions = [
    'apng', 'avif', 'bmp', 'cur', 'eot', 'gif', 'ico', 'jpeg', 'jpg', 'otf',
    'png', 'svg', 'ttf', 'webp', 'woff', 'woff2',
];

$assetFolders = [
    'images' => 'images',
    'img' => 'images',
    'icons' => 'icons',
    'fonts' => 'fonts',
    'webfonts' => 'fonts',
    'media' => 'media',
    'assets' => 'assets',
    'plugins' => 'plugins',
];

$rewriteExtensions = ['blade.php', 'css', 'scss', 'sass', 'js', 'jsx', 'ts', 'tsx'];
$assetMap = [];
$copied = [];
$updated = [];

function normalize_slashes(string $path): string
{
    return str_replace('\\', '/', $path);
}

function relative_path(string $from, string $to): string
{
    $from = rtrim(normalize_slashes($from), '/') . '/';
    $to = normalize_slashes($to);

    if (str_starts_with($to, $from)) {
        return substr($to, strlen($from));
    }

    return basename($to);
}

function ensure_dir(string $dir, bool $dryRun): void
{
    if (is_dir($dir) || $dryRun) {
        return;
    }

    mkdir($dir, 0775, true);
}

function file_extension_key(SplFileInfo $file): string
{
    $filename = $file->getFilename();
    if (str_ends_with($filename, '.blade.php')) {
        return 'blade.php';
    }

    return strtolower($file->getExtension());
}

function public_asset_bucket(string $relative, array $assetFolders): string
{
    $parts = explode('/', normalize_slashes($relative));
    $first = strtolower($parts[0] ?? '');

    return $assetFolders[$first] ?? match (strtolower(pathinfo($relative, PATHINFO_EXTENSION))) {
        'eot', 'otf', 'ttf', 'woff', 'woff2' => 'fonts',
        'svg', 'ico' => 'icons',
        'apng', 'avif', 'bmp', 'cur', 'gif', 'jpeg', 'jpg', 'png', 'webp' => 'images',
        default => 'assets',
    };
}

function laravel_asset_expression(string $publicPath): string
{
    return "{{ asset('" . ltrim(normalize_slashes($publicPath), '/') . "') }}";
}

function css_url_path(string $publicPath): string
{
    return '/' . ltrim(normalize_slashes($publicPath), '/');
}

function rewrite_content(string $content, array $assetMap, string $extension): string
{
    foreach ($assetMap as $source => $publicPath) {
        $source = normalize_slashes($source);
        $publicPath = normalize_slashes($publicPath);
        $assetExpr = laravel_asset_expression($publicPath);
        $cssPath = css_url_path($publicPath);

        if ($extension === 'blade.php') {
            $content = preg_replace(
                '/\b(src|href)=([\'"])' . preg_quote($source, '/') . '\2/',
                '$1=' . '"' . $assetExpr . '"',
                $content
            ) ?? $content;

            $content = preg_replace(
                '/\b(src|href)=([\'"])\/?' . preg_quote($source, '/') . '\2/',
                '$1=' . '"' . $assetExpr . '"',
                $content
            ) ?? $content;

            $content = preg_replace(
                '/asset\(([\'"])' . preg_quote($source, '/') . '\1\)/',
                "asset('" . $publicPath . "')",
                $content
            ) ?? $content;

            $content = preg_replace(
                '/asset\(([\'"])\/?' . preg_quote($source, '/') . '\1\)/',
                "asset('" . $publicPath . "')",
                $content
            ) ?? $content;
        }

        if (in_array($extension, ['css', 'scss', 'sass'], true)) {
            $content = preg_replace(
                '/url\((["\']?)\/?' . preg_quote($source, '/') . '\1\)/',
                'url("' . $cssPath . '")',
                $content
            ) ?? $content;
        }

        if (in_array($extension, ['js', 'jsx', 'ts', 'tsx'], true)) {
            $content = str_replace(
                ["'/" . $source . "'", '"/' . $source . '"', "'" . $source . "'", '"' . $source . '"'],
                ["'" . $cssPath . "'", '"' . $cssPath . '"', "'" . $cssPath . "'", '"' . $cssPath . '"'],
                $content
            );
        }
    }

    return $content;
}

if (!is_dir($resourceRoot)) {
    fwrite(STDERR, "Missing resources directory: {$resourceRoot}\n");
    exit(1);
}

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($resourceRoot, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if (!$file instanceof SplFileInfo || !$file->isFile()) {
        continue;
    }

    $extension = strtolower($file->getExtension());
    if (!in_array($extension, $assetExtensions, true)) {
        continue;
    }

    $sourcePath = $file->getPathname();
    $relative = relative_path($resourceRoot, $sourcePath);
    $bucket = public_asset_bucket($relative, $assetFolders);
    $relativeParts = explode('/', normalize_slashes($relative));

    if (isset($assetFolders[strtolower($relativeParts[0] ?? '')])) {
        array_shift($relativeParts);
    }

    $targetRelative = $bucket . '/' . implode('/', $relativeParts);
    $targetPath = $publicRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $targetRelative);

    $assetMap[$relative] = $targetRelative;
    $assetMap['resources/' . $relative] = $targetRelative;

    if (!file_exists($targetPath) || sha1_file($sourcePath) !== sha1_file($targetPath)) {
        $copied[] = [$relative, $targetRelative];
        ensure_dir(dirname($targetPath), $dryRun);
        if (!$dryRun) {
            copy($sourcePath, $targetPath);
        }
    }
}

$rewriteRoots = [
    $root . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views',
    $root . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'sass',
    $root . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'css',
    $root . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'js',
];

foreach ($rewriteRoots as $rewriteRoot) {
    if (!is_dir($rewriteRoot)) {
        continue;
    }

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($rewriteRoot, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($files as $file) {
        if (!$file instanceof SplFileInfo || !$file->isFile()) {
            continue;
        }

        $extension = file_extension_key($file);
        if (!in_array($extension, $rewriteExtensions, true)) {
            continue;
        }

        $path = $file->getPathname();
        $original = file_get_contents($path);
        if ($original === false) {
            continue;
        }

        $rewritten = rewrite_content($original, $assetMap, $extension);
        if ($rewritten !== $original) {
            $updated[] = relative_path($root, $path);
            if (!$dryRun) {
                file_put_contents($path, $rewritten);
            }
        }
    }
}

echo ($dryRun ? '[dry-run] ' : '') . 'Static asset publish complete.' . PHP_EOL;
echo 'Copied assets: ' . count($copied) . PHP_EOL;
foreach ($copied as [$from, $to]) {
    echo "  {$from} -> public/{$to}" . PHP_EOL;
}

echo 'Updated source files: ' . count($updated) . PHP_EOL;
foreach ($updated as $file) {
    echo "  {$file}" . PHP_EOL;
}

if ($assetMap === []) {
    echo 'No resource static asset files were found. Existing public/ assets were left unchanged.' . PHP_EOL;
}
