<?php
/**
 * Plugin Name: Quiz Maker Pro
 * Description: Advanced quiz system with multiple question types, question bank, and beautiful UI
 * Version: 0.1.0
 * Author: Your Name
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('QMP_VERSION', '0.1.0');
define('QMP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('QMP_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include main plugin class
require_once QMP_PLUGIN_DIR . 'includes/class-quiz-maker-pro.php';

// Initialize the plugin
add_action('plugins_loaded', array('Quiz_Maker_Pro', 'get_instance'));