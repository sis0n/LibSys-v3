<?php

/**
 * config.php
 * * Naglo-load ng environment variables (Standardized via public/index.php).
 */

// 1. Magsimula ng Session kung wala pa
if (session_status() == PHP_SESSION_NONE) {
  session_start();
}

// 2. I-define ang APP_BASE_PATH gamit ang ROOT_PATH para sa consistency
if (!defined('APP_BASE_PATH')) {
    define('APP_BASE_PATH', defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__, 2));
}

