<?php
/**
 * Main Entry Point / Router
 * 
 * This file handles routing for deployment while maintaining
 * backward compatibility with existing file structure.
 * 
 * All existing files continue to work as before.
 * This router adds clean URL support for deployment.
 */

// Get the request URI and parse it
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$requestPath = parse_url($requestUri, PHP_URL_PATH);
$queryString = $_SERVER['QUERY_STRING'] ?? '';

// Remove the base path if the app is in a subdirectory
$scriptName = dirname($_SERVER['SCRIPT_NAME']);
if ($scriptName !== '/' && $scriptName !== '\\') {
    $scriptName = rtrim(str_replace('\\', '/', $scriptName), '/');
    if (strpos($requestPath, $scriptName) === 0) {
        $requestPath = substr($requestPath, strlen($scriptName));
    }
}

// Normalize the path
$requestPath = '/' . ltrim($requestPath, '/');

// If it's an empty path or just '/', default to login/signup
if ($requestPath === '/' || $requestPath === '') {
    $requestPath = '/login';
}

// Remove leading slash for routing
$route = ltrim($requestPath, '/');

// Split route into parts
$routeParts = explode('/', $route);
$mainRoute = $routeParts[0] ?? '';

// Check if this is a request for a static file (assets, images, etc.)
// Static files should be handled by Apache via .htaccess before reaching here
// But if they do reach here, check if file exists and serve it, or 404
$staticExtensions = ['css', 'js', 'jpg', 'jpeg', 'png', 'gif', 'svg', 'ico', 'woff', 'woff2', 'ttf', 'eot', 'pdf', 'docx', 'html'];
$fileExtension = strtolower(pathinfo($requestPath, PATHINFO_EXTENSION));

if (in_array($fileExtension, $staticExtensions)) {
    // Check if the static file exists
    $staticFile = __DIR__ . $requestPath;
    if (file_exists($staticFile) && is_file($staticFile)) {
        // Determine MIME type and serve the file
        $mimeTypes = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'eot' => 'application/vnd.ms-fontobject',
            'pdf' => 'application/pdf',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'html' => 'text/html',
        ];
        
        $mimeType = $mimeTypes[$fileExtension] ?? 'application/octet-stream';
        header('Content-Type: ' . $mimeType);
        readfile($staticFile);
        exit;
    } else {
        // File doesn't exist - 404
        http_response_code(404);
        die('Static file not found');
    }
}

// Route mapping - maps clean URLs to actual files
$routes = [
    // Authentication
    'login' => 'signup.php',
    'signup' => 'signup.php',
    'signup.php' => 'signup.php',
    
    // Admin routes
    'admin' => 'admin.php',
    'admin.php' => 'admin.php',
    
    // Instructor routes
    'instructor' => 'app/Views/instructor/dashboard.php',
    'instructor.php' => 'app/Views/instructor/dashboard.php',
    
    // Student routes
    'student' => 'app/Views/student/student_index.php',
    'student.php' => 'app/Views/student/student_index.php',
    'student_index.php' => 'app/Views/student/student_index.php',
    
    // Logout
    'logout' => 'app/logout.php',
];

// Check if the route exists in our mapping
if (isset($routes[$mainRoute])) {
    $targetFile = $routes[$mainRoute];
    
    // Check if file exists
    $fullPath = __DIR__ . '/' . $targetFile;
    if (file_exists($fullPath)) {
        // Preserve query string
        if (!empty($queryString)) {
            $_SERVER['QUERY_STRING'] = $queryString;
            parse_str($queryString, $_GET);
        }
        
        // Include the target file
        require $fullPath;
        exit;
    }
}

// Check if it's a direct PHP file request (backward compatibility)
$directPhpFile = __DIR__ . $requestPath;
if (file_exists($directPhpFile) && is_file($directPhpFile)) {
    // Preserve query string
    if (!empty($queryString)) {
        $_SERVER['QUERY_STRING'] = $queryString;
        parse_str($queryString, $_GET);
    }
    
    require $directPhpFile;
    exit;
}

// Check if it's a request for a directory that has an index file
$directoryPath = __DIR__ . $requestPath;
if (is_dir($directoryPath)) {
    $indexFiles = ['index.php', 'index.html'];
    foreach ($indexFiles as $indexFile) {
        $indexPath = $directoryPath . '/' . $indexFile;
        if (file_exists($indexPath)) {
            require $indexPath;
            exit;
        }
    }
}

// 404 - Route not found
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background: #f5f5f5;
        }
        .error-container {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
        }
        p {
            color: #666;
            margin-bottom: 20px;
        }
        a {
            color: #007bff;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <h1>404 - Page Not Found</h1>
        <p>The page you're looking for doesn't exist.</p>
        <a href="/">Go to Login</a>
    </div>
</body>
</html>

