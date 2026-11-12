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
    return $html;
});

require __DIR__ . '/index.php';
