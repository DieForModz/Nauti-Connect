<?php
// Custom 500 – Internal Server Error page.
// This file is included directly by the error handler, so config constants
// may or may not be available. Use a safe fallback for SITE_URL.
if (!headers_sent()) {
    http_response_code(500);
}
$siteUrl = defined('SITE_URL') ? SITE_URL : '/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 – Internal Server Error | Nauti-Connect</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        navy: '#0a1628',
                        ocean: '#1e3a5f',
                        gold: '#c9a227',
                    }
                }
            }
        }
    </script>
</head>
<body class="text-white min-h-screen flex items-center justify-center" style="background:linear-gradient(175deg,#1a85c8 0%,#1070b0 28%,#0a58a0 58%,#074880 100%);background-attachment:fixed;">
    <div class="text-center px-4">
        <svg class="w-20 h-20 text-[#c9a227] mx-auto mb-6" viewBox="0 0 40 40" fill="currentColor" aria-hidden="true">
            <path d="M20 2L4 32h32L20 2zm0 6l11 20H9L20 8z"/>
            <path d="M17 34c0 1.1.9 2 2 2h2c1.1 0 2-.9 2-2v-2h-6v2z"/>
            <rect x="10" y="34" width="20" height="3" rx="1.5"/>
        </svg>
        <h1 class="text-6xl font-bold text-[#c9a227] mb-4">500</h1>
        <h2 class="text-2xl font-semibold mb-3">Internal Server Error</h2>
        <p class="text-gray-400 mb-8 max-w-md mx-auto">
            Something went wrong on our end. The error has been logged and we'll look into it.
            Please try again in a moment.
        </p>
        <a href="<?= htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8') ?>"
           class="inline-block bg-[#c9a227] hover:bg-[#d4af37] text-[#0a1628] font-bold py-3 px-8 rounded-lg transition-colors">
            Return to Dashboard
        </a>
    </div>
</body>
</html>
