<?php
/**
 * Main entry point
 * Redirects to dashboard if logged in, otherwise to login page
 */

require_once '../config/config.php';
require_once INCLUDE_PATH . '/functions.php';
require_once INCLUDE_PATH . '/auth.php';

// Check if user is logged in
if (is_logged_in()) {
    // Redirect to dashboard
    redirect('dashboard.php');  // Remove the leading slash
} else {
    // Redirect to login page
    redirect('login.php');  // Remove the leading slash
}