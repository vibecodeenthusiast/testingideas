<?php
/**
 * Main Quiz Maker Pro Class
 */
class Quiz_Maker_Pro {

    /**
     * Plugin version
     */
    const VERSION = '0.1.0';

    /**
     * Instance of this class
     */
    private static $instance = null;

    /**
     * Return an instance of this class.
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize the plugin
     */
    private function __construct() {
        // Load plugin text domain
        add_action('init', array($this, 'load_plugin_textdomain'));

        // Activate plugin when new blog is added
        add_action('wpmu_new_blog', array($this, 'activate_new_site'));

        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));

        // Register activation hook
        register_activation_hook(__FILE__, array($this, 'activate'));

        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Initialize components
        $this->init_components();
    }

    /**
     * Load plugin text domain for localization
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'quiz-maker-pro',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }

    /**
     * Activate the plugin
     */
    public function activate() {
        // Create necessary database tables
        $this->create_tables();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Create database tables
     */
    private function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Table for quizzes
        $quizzes_table = $wpdb->prefix . 'qmp_quizzes';
        $sql = "CREATE TABLE IF NOT EXISTS $quizzes_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            description text,
            settings text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        // Table for questions
        $questions_table = $wpdb->prefix . 'qmp_questions';
        $sql .= "CREATE TABLE IF NOT EXISTS $questions_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            quiz_id mediumint(9),
            question_text text NOT NULL,
            question_type varchar(50) NOT NULL, -- 'yes_no', 'single_choice', 'multiple_choice', 'text'
            settings text,
            sort_order int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            FOREIGN KEY (quiz_id) REFERENCES {$wpdb->prefix}qmp_quizzes(id) ON DELETE CASCADE
        ) $charset_collate;";

        // Table for answers
        $answers_table = $wpdb->prefix . 'qmp_answers';
        $sql .= "CREATE TABLE IF NOT EXISTS $answers_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            question_id mediumint(9) NOT NULL,
            answer_text text NOT NULL,
            is_correct tinyint(1) DEFAULT 0,
            points int(11) DEFAULT 0,
            sort_order int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            FOREIGN KEY (question_id) REFERENCES {$wpdb->prefix}qmp_questions(id) ON DELETE CASCADE
        ) $charset_collate;";

        // Table for quiz attempts
        $attempts_table = $wpdb->prefix . 'qmp_attempts';
        $sql .= "CREATE TABLE IF NOT EXISTS $attempts_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            quiz_id mediumint(9) NOT NULL,
            user_id mediumint(9),
            start_time datetime DEFAULT CURRENT_TIMESTAMP,
            end_time datetime,
            status varchar(50) DEFAULT 'in_progress', -- 'in_progress', 'completed', 'abandoned'
            score decimal(10,2),
            total_score decimal(10,2),
            answers_data longtext,
            PRIMARY KEY (id),
            FOREIGN KEY (quiz_id) REFERENCES {$wpdb->prefix}qmp_quizzes(id) ON DELETE CASCADE
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Quizzes', 'quiz-maker-pro'),
            __('Quizzes', 'quiz-maker-pro'),
            'manage_options',
            'qmp-quizzes',
            array($this, 'quizzes_page'),
            'dashicons-welcome-learn-more',
            30
        );

        add_submenu_page(
            'qmp-quizzes',
            __('All Quizzes', 'quiz-maker-pro'),
            __('All Quizzes', 'quiz-maker-pro'),
            'manage_options',
            'qmp-quizzes',
            array($this, 'quizzes_page')
        );

        add_submenu_page(
            'qmp-quizzes',
            __('Add New', 'quiz-maker-pro'),
            __('Add New', 'quiz-maker-pro'),
            'manage_options',
            'qmp-add-new',
            array($this, 'add_new_quiz_page')
        );

        add_submenu_page(
            'qmp-quizzes',
            __('Question Bank', 'quiz-maker-pro'),
            __('Question Bank', 'quiz-maker-pro'),
            'manage_options',
            'qmp-question-bank',
            array($this, 'question_bank_page')
        );
    }

    /**
     * Render quizzes page
     */
    public function quizzes_page() {
        include_once QMP_PLUGIN_DIR . 'admin/views/quizzes-list.php';
    }

    /**
     * Render add new quiz page
     */
    public function add_new_quiz_page() {
        include_once QMP_PLUGIN_DIR . 'admin/views/add-quiz.php';
    }

    /**
     * Render question bank page
     */
    public function question_bank_page() {
        include_once QMP_PLUGIN_DIR . 'admin/views/question-bank.php';
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        wp_enqueue_style(
            'qmp-frontend-style',
            QMP_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            QMP_VERSION
        );

        wp_enqueue_script(
            'qmp-frontend-script',
            QMP_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            QMP_VERSION,
            true
        );
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'qmp-') !== false) {
            wp_enqueue_style(
                'qmp-admin-style',
                QMP_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                QMP_VERSION
            );

            wp_enqueue_script(
                'qmp-admin-script',
                QMP_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery', 'wp-color-picker'),
                QMP_VERSION,
                true
            );

            wp_enqueue_script('wp-color-picker');
        }
    }

    /**
     * Initialize components
     */
    private function init_components() {
        // Initialize quiz handler
        include_once QMP_PLUGIN_DIR . 'includes/class-qmp-quiz-handler.php';
        
        // Initialize question handler
        include_once QMP_PLUGIN_DIR . 'includes/class-qmp-question-handler.php';
        
        // Initialize shortcode handler
        include_once QMP_PLUGIN_DIR . 'includes/class-qmp-shortcode-handler.php';
    }

    /**
     * Activate plugin on new site (for multisite)
     */
    public function activate_new_site($blog_id) {
        switch_to_blog($blog_id);
        $this->activate();
        restore_current_blog();
    }
}