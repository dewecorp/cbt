<?php
include '../../includes/init_session.php';
if (!isset($_SESSION['user_id'])) {
    exit('Access Denied');
}

$url = isset($_GET['url']) ? $_GET['url'] : '';

if (filter_var($url, FILTER_VALIDATE_URL)) {
    // Basic User Agent to mimic browser
    $options = [
        "http" => [
            "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36\r\n"
        ]
    ];
    $context = stream_context_create($options);
    
    // Suppress warnings
    $content = @file_get_contents($url, false, $context);
    
    if ($content !== false) {
        // Inject <base> tag to fix relative links
        $urlParts = parse_url($url);
        $baseUrl = $urlParts['scheme'] . '://' . $urlParts['host'] . (isset($urlParts['path']) ? dirname($urlParts['path']) : '') . '/';
        
        // Simple regex to insert base tag after <head>
        $content = preg_replace('/<head>/i', '<head><base href="' . $baseUrl . '">', $content, 1);
        
        // Remove X-Frame-Options meta tags if present in HTML (though usually in headers, which we don't forward)
        echo $content;
    } else {
        echo '<div style="text-align:center; padding:50px; font-family:sans-serif;">';
        echo '<h3>Gagal memuat konten website.</h3>';
        echo '<p>Website ini mungkin memblokir akses dari aplikasi lain.</p>';
        echo '<a href="'.$url.'" target="_blank" style="padding:10px 20px; background:#007bff; color:white; text-decoration:none; border-radius:5px;">Buka di Tab Baru</a>';
        echo '</div>';
    }
} else {
    echo 'Invalid URL';
}
