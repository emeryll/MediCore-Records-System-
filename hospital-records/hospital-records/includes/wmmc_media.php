<?php
declare(strict_types=1);

/**
 * West Metro Medical Center (Metro Pacific Health) official brand & building media helpers.
 * Automatically syncs the uploaded image files into assets/ when running under Apache/XAMPP
 * and falls back seamlessly to inline base64 data URIs.
 */

$_wmmcScratchMedia = 'C:/Users/karlp/.gemini/antigravity/brain/ee1f55ec-3023-436b-8b8d-af3f98f83c13/scratch/wmmc_media.php';
if (is_file($_wmmcScratchMedia)) {
    require_once $_wmmcScratchMedia;
}

function wmmc_resolve_asset(string $assetFilename, array $candidateSources, string $mimeType, string $fallbackConstant = ''): string
{
    $assetsDir  = __DIR__ . '/../assets';
    $targetPath = $assetsDir . '/' . $assetFilename;
    $webPath    = 'assets/' . $assetFilename;

    if (is_file($targetPath) && filesize($targetPath) > 100) {
        return $webPath;
    }

    foreach ($candidateSources as $src) {
        if (is_file($src) && is_readable($src)) {
            if (is_dir($assetsDir) && is_writable($assetsDir)) {
                if (@copy($src, $targetPath) && is_file($targetPath)) {
                    return $webPath;
                }
            }
            $raw = @file_get_contents($src);
            if ($raw !== false && $raw !== '') {
                return 'data:' . $mimeType . ';base64,' . base64_encode($raw);
            }
        }
    }

    if ($fallbackConstant !== '' && defined($fallbackConstant)) {
        return (string) constant($fallbackConstant);
    }

    return $webPath;
}

/** Official West Metro Medical Center — Metro Pacific Health Logo PNG. */
function wmmc_logo_src(): string
{
    $base = 'C:/Users/karlp/.gemini/antigravity/brain/ee1f55ec-3023-436b-8b8d-af3f98f83c13';
    return wmmc_resolve_asset(
        'wmmc_logo.png',
        [
            $base . '/scratch/wmmc_logo.png',
            $base . '/.user_uploaded/media_1793972106318_f69bec1e.png',
        ],
        'image/png',
        'WMMC_LOGO_DATA_URI'
    );
}

/** Official West Metro Medical Center Building Exterior Photo JPG. */
function wmmc_building_src(): string
{
    $base = 'C:/Users/karlp/.gemini/antigravity/brain/ee1f55ec-3023-436b-8b8d-af3f98f83c13';
    return wmmc_resolve_asset(
        'wmmc_building.jpg',
        [
            $base . '/scratch/wmmc_building.jpg',
            $base . '/.user_uploaded/media_1793972106314_afe5ffa7.jpg',
        ],
        'image/jpeg',
        'WMMC_BUILDING_DATA_URI'
    );
}

/** Official West Metro Medical Center Welcome to Wellness Banner JPG. */
function wmmc_banner_src(): string
{
    $base = 'C:/Users/karlp/.gemini/antigravity/brain/ee1f55ec-3023-436b-8b8d-af3f98f83c13';
    return wmmc_resolve_asset(
        'wmmc_banner.jpg',
        [
            $base . '/scratch/wmmc_banner.jpg',
            $base . '/.user_uploaded/media_1793972106314_5a89311c.jpg',
        ],
        'image/jpeg',
        'WMMC_BANNER_DATA_URI'
    );
}
