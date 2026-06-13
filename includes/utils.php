<?php
// includes/utils.php
// UI Utility functions and vector graphic generators for Kigali TechHub

function formatRWF($amount) {
    return number_format($amount, 0, '.', ',') . ' RWF';
}

function renderProductImage($imageName, $categorySlug, $productName, $className = 'product-img') {
    $filePath = __DIR__ . '/../assets/images/' . $imageName;

    // Check if the physical file exists in the directory
    if (file_exists($filePath) && !is_dir($filePath)) {
        return '<img src="assets/images/' . htmlspecialchars($imageName) . '" class="' . htmlspecialchars($className) . '" alt="' . htmlspecialchars($productName) . '">';
    }

    // Fallback: Return a gorgeous custom dark-glowing tech SVG corresponding to the category
    $gradientId = 'grad_' . uniqid();
    $accentColor = '#00f2fe';
    $secondaryColor = '#4facfe';

    switch ($categorySlug) {
        case 'laptops':
            $accentColor = '#00f2fe';
            $secondaryColor = '#4facfe';
            $svgContent = '
            <!-- Laptop SVG -->
            <rect x="20" y="30" width="160" height="100" rx="6" fill="rgba(0, 242, 254, 0.04)" stroke="url(#' . $gradientId . ')" stroke-width="2.5" />
            <rect x="28" y="38" width="144" height="84" rx="2" fill="rgba(0, 0, 0, 0.4)" stroke="rgba(255,255,255,0.05)" />
            <path d="M10 130 L190 130 A10 10 0 0 1 180 148 L20 148 A10 10 0 0 1 10 130 Z" fill="url(#' . $gradientId . ')" opacity="0.8" />
            <line x1="85" y1="138" x2="115" y2="138" stroke="#000" stroke-width="3" stroke-linecap="round" />
            <circle cx="100" cy="80" r="14" fill="rgba(0, 242, 254, 0.1)" stroke="url(#' . $gradientId . ')" stroke-width="1.5" stroke-dasharray="2, 2" />
            <path d="M92 80 L108 80 M100 72 L100 88" stroke="url(#' . $gradientId . ')" stroke-width="2" stroke-linecap="round" />
            ';
            break;

        case 'smartphones':
            $accentColor = '#a855f7';
            $secondaryColor = '#6366f1';
            $svgContent = '
            <!-- Smartphone SVG -->
            <rect x="55" y="20" width="90" height="160" rx="14" fill="rgba(168, 85, 247, 0.04)" stroke="url(#' . $gradientId . ')" stroke-width="2.5" />
            <rect x="62" y="28" width="76" height="144" rx="8" fill="rgba(0, 0, 0, 0.4)" stroke="rgba(255,255,255,0.05)" />
            <circle cx="100" cy="38" r="5" fill="#000" stroke="url(#' . $gradientId . ')" stroke-width="1" />
            <circle cx="100" cy="158" r="8" fill="rgba(255,255,255,0.1)" stroke="url(#' . $gradientId . ')" stroke-width="1.5" />
            <path d="M85 80 Q100 65 115 80 T100 110 Z" fill="none" stroke="url(#' . $gradientId . ')" stroke-width="2" opacity="0.6" />
            ';
            break;

        case 'audio':
            $accentColor = '#f43f5e';
            $secondaryColor = '#fb7185';
            $svgContent = '
            <!-- Audio / Headphones SVG -->
            <path d="M40 100 A60 60 0 0 1 160 100" fill="none" stroke="url(#' . $gradientId . ')" stroke-width="4" stroke-linecap="round" />
            <rect x="30" y="85" width="20" height="40" rx="6" fill="url(#' . $gradientId . ')" />
            <rect x="150" y="85" width="20" height="40" rx="6" fill="url(#' . $gradientId . ')" />
            <circle cx="100" cy="100" r="22" fill="rgba(244, 63, 94, 0.04)" stroke="url(#' . $gradientId . ')" stroke-width="1.5" stroke-dasharray="3, 3" />
            <path d="M85 100 Q92 90 100 100 T115 100" fill="none" stroke="url(#' . $gradientId . ')" stroke-width="2" />
            ';
            break;

        case 'wearables':
            $accentColor = '#10b981';
            $secondaryColor = '#059669';
            $svgContent = '
            <!-- Wearable / Smartwatch SVG -->
            <rect x="85" y="15" width="30" height="170" rx="6" fill="rgba(255, 255, 255, 0.05)" stroke="rgba(255,255,255,0.1)" stroke-width="2" />
            <circle cx="100" cy="100" r="46" fill="rgba(16, 185, 129, 0.04)" stroke="url(#' . $gradientId . ')" stroke-width="3" />
            <circle cx="100" cy="100" r="38" fill="rgba(0, 0, 0, 0.5)" stroke="rgba(255,255,255,0.05)" />
            <path d="M80 100 L95 105 L105 95 L120 100" fill="none" stroke="url(#' . $gradientId . ')" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
            <text x="100" y="74" fill="url(#' . $gradientId . ')" font-size="10" font-weight="bold" text-anchor="middle">ACTIVE</text>
            ';
            break;

        case 'services':
        default:
            $accentColor = '#f59e0b';
            $secondaryColor = '#d97706';
            $svgContent = '
            <!-- IT Services SVG -->
            <circle cx="100" cy="100" r="40" fill="rgba(245, 158, 11, 0.04)" stroke="url(#' . $gradientId . ')" stroke-width="2" stroke-dasharray="4, 4" />
            <!-- Gears and tools outline -->
            <path d="M80 80 L120 120 M120 80 L80 120" stroke="url(#' . $gradientId . ')" stroke-width="3" stroke-linecap="round" />
            <circle cx="100" cy="100" r="12" fill="url(#' . $gradientId . ')" stroke="#000" stroke-width="2" />
            <path d="M100 68 L100 60 M100 132 L100 140 M68 100 L60 100 M132 100 L140 100" stroke="url(#' . $gradientId . ')" stroke-width="3" stroke-linecap="round" />
            ';
            break;
    }

    return '
    <div class="vector-card-graphic">
        <svg class="vector-svg" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <linearGradient id="' . $gradientId . '" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="' . $accentColor . '" />
                    <stop offset="100%" stop-color="' . $secondaryColor . '" />
                </linearGradient>
            </defs>
            ' . $svgContent . '
        </svg>
    </div>';
}
?>
