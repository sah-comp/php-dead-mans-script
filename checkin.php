<?php

declare(strict_types=1);

require_once 'globals.php';
require_once 'functions.php';

// Parse command line arguments if running in CLI mode
if (PHP_SAPI === 'cli') {
    parse_str(implode('&', array_slice($argv, 1)), $_GET);
}

// Validate token parameter
if (!isset($_GET['token'])) {
    http_response_code(400);
    die('<p>Missing token.</p>');
}

// Validate token value
if ((int)$_GET['token'] !== getToken()) {
    http_response_code(401);
    die('<p>Invalid token.</p>');
}

// Process successful check-in
if (randomizeToken() && resetDayNum()) {
    echo '<p>Checked in.</p>';
} else {
    http_response_code(500);
    echo '<p>Check-in failed. Please try again.</p>';
}
