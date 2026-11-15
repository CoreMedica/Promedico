<?php

declare(strict_types=1);

/**
 * 404 handler.
 *
 * Redirects URLs that contain uppercase letters in the path to their
 * lowercase form with a 301 (mod_rewrite cannot lowercase in .htaccess
 * on shared hosting, because RewriteMap needs vhost access).
 * Genuine 404s render a simple error page.
 */

$host = $_SERVER['HTTP_HOST'] ?? '';
$isLocal = preg_match('/\.(test|localhost)$/i', $host) === 1;

$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = (string) (parse_url($uri, PHP_URL_PATH) ?: '/');
$query = (string) (parse_url($uri, PHP_URL_QUERY) ?? '');
$lowerPath = strtolower($path);

if (!$isLocal && $lowerPath !== $path) {
    // Path + query only: no user input reaches headers unfiltered.
    header('Location: ' . $lowerPath . ($query !== '' ? '?' . $query : ''), true, 301);
    exit;
}

http_response_code(404);

// Prefer the styled Astro 404 page if one exists later (src/pages/404.astro).
$styled = __DIR__ . '/404.html';
if (is_file($styled)) {
    readfile($styled);
    exit;
}
?>
<!doctype html>
<html lang="en-GB">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Page Not Found | Promedico</title>
    <meta name="robots" content="noindex">
    <style>
        body { font-family: sans-serif; margin: 0; min-height: 100vh; display: grid; place-content: center; text-align: center; padding: 1.5rem; }
        h1 { font-size: 1.5rem; margin: 0 0 .5rem; }
        p { margin: 0 0 1.25rem; color: #444; }
        a { display: inline-block; margin: .25rem; padding: .75rem 1.25rem; border: 1px solid #2f5f5b; border-radius: .375rem; color: #2f5f5b; text-decoration: none; min-height: 44px; line-height: 1.4; }
        a:hover { background: #2f5f5b; color: #fff; }
    </style>
</head>
<body>
    <main>
        <h1>Page not found</h1>
        <p>The page you're looking for doesn't exist or has moved.</p>
        <nav aria-label="Helpful links">
            <a href="/">Home</a>
            <a href="/services/ear-care">Ear care services</a>
            <a href="/contact">Contact us</a>
        </nav>
    </main>
</body>
</html>
