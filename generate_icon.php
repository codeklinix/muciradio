<?php
// Simple icon generator for radio stations
header('Content-Type: image/svg+xml');
header('Cache-Control: max-age=86400'); // Cache for 24 hours

// Get parameters
$text = isset($_GET['text']) ? strtoupper(substr($_GET['text'], 0, 3)) : 'R';
$bg = isset($_GET['bg']) ? $_GET['bg'] : 'FF6B35';
$color = isset($_GET['color']) ? $_GET['color'] : 'FFFFFF';
$size = isset($_GET['size']) ? intval($_GET['size']) : 60;

// Clean the color codes
$bg = preg_replace('/[^A-Fa-f0-9]/', '', $bg);
$color = preg_replace('/[^A-Fa-f0-9]/', '', $color);

// Ensure valid hex colors
if (strlen($bg) !== 6) $bg = 'FF6B35';
if (strlen($color) !== 6) $color = 'FFFFFF';

$svg = '<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 ' . $size . ' ' . $size . '">
    <defs>
        <linearGradient id="grad" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" style="stop-color:#' . $bg . ';stop-opacity:1" />
            <stop offset="100%" style="stop-color:#' . $bg . 'CC;stop-opacity:1" />
        </linearGradient>
    </defs>
    <rect width="' . $size . '" height="' . $size . '" rx="' . ($size * 0.15) . '" fill="url(#grad)" />
    <text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="#' . $color . '" font-family="Arial, sans-serif" font-weight="bold" font-size="' . ($size * 0.3) . '">' . htmlspecialchars($text) . '</text>
    <circle cx="' . ($size * 0.8) . '" cy="' . ($size * 0.2) . '" r="' . ($size * 0.08) . '" fill="#' . $color . '" opacity="0.7"/>
</svg>';

echo $svg;
?>
