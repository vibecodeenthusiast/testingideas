<?php
/**
 * Plugin Name: Quiz System
 * Description: A comprehensive quiz system plugin with question bank, various question types, and modern UI
 * Version: 1.0.0
 * Author: Your Name
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('QUIZ_SYSTEM_VERSION', '1.0.0');
define('QUIZ_SYSTEM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('QUIZ_SYSTEM_PLUGIN_PATH', plugin_dir_path(__FILE__));

class QuizSystem {
    
    public function __construct() {
        // Initialize plugin
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // Load required files
        $this->load_dependencies();
        
        // Register hooks
        add_action('admin_menu', array($this, 'register_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_assets'));
        add_shortcode('quiz', array($this, 'quiz_shortcode'));
        
        // Initialize custom post types and taxonomies if needed
        add_action('init', array($this, 'register_post_types'));
    }
    
    private function load_dependencies() {
        // Load includes
        require_once QUIZ_SYSTEM_PLUGIN_PATH . 'includes/class-quiz-system-database.php';
        require_once QUIZ_SYSTEM_PLUGIN_PATH . 'includes/class-quiz-system-question-bank.php';
        require_once QUIZ_SYSTEM_PLUGIN_PATH . 'includes/class-quiz-system-quizzes.php';
        require_once QUIZ_SYSTEM_PLUGIN_PATH . 'includes/class-quiz-system-frontend.php';
        
        // Initialize classes that register their own hooks
        new QuizSystemQuestionBank();
        new QuizSystemQuizzes();
        new QuizSystemFrontend();
    }
    
    public function activate() {
        // Run activation tasks
        global $wpdb;
        
        // Create tables
        $charset_collate = $wpdb->get_charset_collate();
        
        // Table for quizzes
        $quizzes_table = $wpdb->prefix . 'qs_quizzes';
        $sql = "CREATE TABLE $quizzes_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            description text,
            settings text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Table for questions
        $questions_table = $wpdb->prefix . 'qs_questions';
        $sql .= "CREATE TABLE $questions_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            type enum('yes_no', 'single_choice', 'multiple_choice', 'text') NOT NULL,
            content text,
            quiz_id mediumint(9),
            question_order int DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Table for answers
        $answers_table = $wpdb->prefix . 'qs_answers';
        $sql .= "CREATE TABLE $answers_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            question_id mediumint(9) NOT NULL,
            content text NOT NULL,
            is_correct tinyint(1) DEFAULT 0,
            weight int DEFAULT 0,
            answer_order int DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Table for results
        $results_table = $wpdb->prefix . 'qs_results';
        $sql .= "CREATE TABLE $results_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            quiz_id mediumint(9) NOT NULL,
            user_id bigint(20),
            answers_data text,
            score decimal(5,2),
            completed_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function deactivate() {
        // Run deactivation tasks if needed
    }
    
    public function register_post_types() {
        // Register custom post types if needed
    }
    
    public function register_admin_menu() {
        // Add main menu page
        add_menu_page(
            'Quiz System',
            'Quiz System',
            'manage_options',
            'quiz-system',
            array($this, 'admin_dashboard'),
            'dashicons-welcome-learn-more',
            30
        );
        
        // Add submenu pages
        add_submenu_page(
            'quiz-system',
            'Quizzes',
            'Quizzes',
            'manage_options',
            'quiz-system',
            array($this, 'admin_dashboard')
        );
        
        add_submenu_page(
            'quiz-system',
            'Question Bank',
            'Question Bank',
            'manage_options',
            'quiz-system-questions',
            array($this, 'admin_question_bank')
        );
    }
    
    public function admin_dashboard() {
        include_once QUIZ_SYSTEM_PLUGIN_PATH . 'admin/views/dashboard.php';
    }
    
    public function admin_question_bank() {
        include_once QUIZ_SYSTEM_PLUGIN_PATH . 'admin/views/question-bank.php';
    }
    
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'quiz-system') !== false) {
            wp_enqueue_style('quiz-system-admin-css', QUIZ_SYSTEM_PLUGIN_URL . 'assets/css/admin.css', array(), QUIZ_SYSTEM_VERSION);
            wp_enqueue_script('quiz-system-admin-js', QUIZ_SYSTEM_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), QUIZ_SYSTEM_VERSION, true);
        }
    }
    
    public function enqueue_public_assets() {
        wp_enqueue_style('quiz-system-public-css', QUIZ_SYSTEM_PLUGIN_URL . 'assets/css/public.css', array(), QUIZ_SYSTEM_VERSION);
        wp_enqueue_script('quiz-system-public-js', QUIZ_SYSTEM_PLUGIN_URL . 'assets/js/public.js', array('jquery'), QUIZ_SYSTEM_VERSION, true);
        
        // Localize script for AJAX
        wp_localize_script('quiz-system-public-js', 'quiz_system_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('quiz_system_nonce')
        ));
    }
    
    public function quiz_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0
        ), $atts);
        
        ob_start();
        include_once QUIZ_SYSTEM_PLUGIN_PATH . 'public/templates/quiz-template.php';
        return ob_get_clean();
    }
}

// Initialize the plugin
new QuizSystem();