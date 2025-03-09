<?php
/**
 * Logout page
 */

require_once '../config/config.php';
require_once INCLUDE_PATH . '/functions.php';
require_once INCLUDE_PATH . '/auth.php';

// Logout the user
logout_user();

// Redirect to login page with message
set_flash_message('info', 'You have been logged out successfully');
redirect('/login.php');