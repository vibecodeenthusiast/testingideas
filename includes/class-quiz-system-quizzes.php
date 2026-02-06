<?php
/**
 * Quizzes handler for Quiz System
 */

if (!defined('ABSPATH')) {
    exit;
}

class QuizSystemQuizzes {
    
    public function __construct() {
        add_action('wp_ajax_save_quiz', array($this, 'handle_save_quiz'));
        add_action('wp_ajax_delete_quiz', array($this, 'handle_delete_quiz'));
        add_action('wp_ajax_load_quiz_form', array($this, 'handle_load_quiz_form'));
    }
    
    /**
     * Render the quizzes admin page
     */
    public static function render_quizzes_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Handle form submissions
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['action']) && $_POST['action'] === 'save_quiz') {
                self::handle_save_quiz_request();
            }
        }
        
        // Get quizzes for display
        $quizzes = QuizSystemDatabase::get_quizzes();
        
        include_once QUIZ_SYSTEM_PLUGIN_PATH . 'admin/views/quizzes-page.php';
    }
    
    /**
     * Handle saving a quiz via AJAX
     */
    public function handle_save_quiz() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'quiz_system_nonce')) {
            wp_die('Security check failed');
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $quiz_data = array(
            'id' => isset($_POST['quiz_id']) ? intval($_POST['quiz_id']) : 0,
            'title' => sanitize_text_field($_POST['title']),
            'description' => wp_kses_post($_POST['description']),
            'settings' => array(
                'show_progress' => isset($_POST['settings']['show_progress']) ? 1 : 0,
                'random_questions' => isset($_POST['settings']['random_questions']) ? 1 : 0,
                'allow_back' => isset($_POST['settings']['allow_back']) ? 1 : 0,
                'require_login' => isset($_POST['settings']['require_login']) ? 1 : 0
            )
        );
        
        $result = QuizSystemDatabase::save_quiz($quiz_data);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => $quiz_data['id'] ? 'Quiz updated successfully' : 'Quiz created successfully',
                'quiz_id' => $result
            ));
        } else {
            wp_send_json_error('Failed to save quiz');
        }
    }
    
    /**
     * Handle deleting a quiz via AJAX
     */
    public function handle_delete_quiz() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'quiz_system_nonce')) {
            wp_die('Security check failed');
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $quiz_id = intval($_POST['quiz_id']);
        
        $result = QuizSystemDatabase::delete_quiz($quiz_id);
        
        if ($result) {
            wp_send_json_success(array('message' => 'Quiz deleted successfully'));
        } else {
            wp_send_json_error('Failed to delete quiz');
        }
    }
    
    /**
     * Handle loading quiz form via AJAX
     */
    public function handle_load_quiz_form() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'quiz_system_nonce')) {
            wp_die('Security check failed');
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $quiz_id = intval($_POST['quiz_id']);
        $quiz = QuizSystemDatabase::get_quiz($quiz_id);
        
        if ($quiz) {
            // Unserialize settings
            $quiz->settings = maybe_unserialize($quiz->settings);
            
            wp_send_json_success(array('quiz' => $quiz));
        } else {
            wp_send_json_error('Quiz not found');
        }
    }
    
    /**
     * Handle save quiz request from admin page
     */
    private static function handle_save_quiz_request() {
        if (!wp_verify_nonce($_POST['nonce'], 'quiz_system_nonce')) {
            add_settings_error('quiz_system', 'error', 'Security check failed');
            return;
        }
        
        $quiz_data = array(
            'id' => isset($_POST['quiz_id']) ? intval($_POST['quiz_id']) : 0,
            'title' => sanitize_text_field($_POST['title']),
            'description' => wp_kses_post($_POST['description']),
            'settings' => array(
                'show_progress' => isset($_POST['settings']['show_progress']) ? 1 : 0,
                'random_questions' => isset($_POST['settings']['random_questions']) ? 1 : 0,
                'allow_back' => isset($_POST['settings']['allow_back']) ? 1 : 0,
                'require_login' => isset($_POST['settings']['require_login']) ? 1 : 0
            )
        );
        
        $result = QuizSystemDatabase::save_quiz($quiz_data);
        
        if ($result) {
            add_settings_error('quiz_system', 'success', $quiz_data['id'] ? 'Quiz updated successfully' : 'Quiz created successfully', 'updated');
        } else {
            add_settings_error('quiz_system', 'error', 'Failed to save quiz');
        }
    }
    
    /**
     * Get quiz by ID with questions
     */
    public static function get_quiz_with_questions($quiz_id) {
        $quiz = QuizSystemDatabase::get_quiz($quiz_id);
        if (!$quiz) {
            return null;
        }
        
        $quiz->settings = maybe_unserialize($quiz->settings);
        $quiz->questions = QuizSystemDatabase::get_questions_by_quiz($quiz_id);
        
        return $quiz;
    }
}