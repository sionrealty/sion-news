<?php
declare(strict_types=1);

if (parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) !== '/rent') {
    http_response_code(404);
    exit;
}

ob_start(static function (string $html): string {
    $canonical = '<link rel="canonical" href="https://sionrealtypm.com/rent">';
    $html = strtr($html, [
        '<meta name="robots" content="noindex,nofollow">' => '<meta name="robots" content="index,follow">',
        '<link rel="canonical" href="https://sionrealtypm.com/sion-imoveis/index.php">' => $canonical,
        'href="style.css' => 'href="/sion-imoveis/style.css',
        'href="rentals-public.css' => 'href="/sion-imoveis/rentals-public.css',
        'src="sion-logo.png"' => 'src="/sion-imoveis/sion-logo.png"',
        'src="rentals-public.js' => 'src="/sion-imoveis/rentals-public.js',
        'src="ui.js' => 'src="/sion-imoveis/ui.js',
    ]);
    if (!str_contains($html, $canonical)) {
        $html = str_replace('</head>', $canonical . '</head>', $html);
    }
    $share = <<<'HTML'
<meta property="og:type" content="website">
<meta property="og:site_name" content="Sion Property Management">
<meta property="og:title" content="Apartments for Rent in Orlando">
<meta property="og:description" content="See available apartments, photos, and rental details in the Orlando area.">
<meta property="og:url" content="https://sionrealtypm.com/rent">
<meta property="og:image" content="https://sionrealtypm.com/sion-imoveis/rent-share-v2.jpg">
<meta property="og:image:type" content="image/jpeg">
<meta property="og:image:width" content="256">
<meta property="og:image:height" content="256">
<meta property="og:image:alt" content="A small house with the Sion emblem, held in two hands">
HTML;
    return str_replace('</head>', $share . "\n</head>", $html);
});

require __DIR__ . '/index.php';
