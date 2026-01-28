<?php
/**
 * Question Bank handler for Quiz System
 */

if (!defined('ABSPATH')) {
    exit;
}

class QuizSystemQuestionBank {
    
    public function __construct() {
        add_action('wp_ajax_save_question', array($this, 'handle_save_question'));
        add_action('wp_ajax_delete_question', array($this, 'handle_delete_question'));
        add_action('wp_ajax_load_question_form', array($this, 'handle_load_question_form'));
    }
    
    /**
     * Render the question bank admin page
     */
    public static function render_question_bank() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Handle form submissions
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['action']) && $_POST['action'] === 'save_question') {
                $this->handle_save_question_request();
            }
        }
        
        // Get questions for display
        $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
        $type_filter = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
        
        $questions_args = array(
            'search' => $search,
            'type' => $type_filter,
            'limit' => 20,
            'offset' => isset($_GET['paged']) ? (intval($_GET['paged']) - 1) * 20 : 0
        );
        
        $questions = QuizSystemDatabase::get_all_questions($questions_args);
        $total_questions = count(QuizSystemDatabase::get_all_questions(array('search' => $search, 'type' => $type_filter)));
        $total_pages = ceil($total_questions / 20);
        
        include_once QUIZ_SYSTEM_PLUGIN_PATH . 'admin/views/question-bank-page.php';
    }
    
    /**
     * Handle saving a question via AJAX
     */
    public function handle_save_question() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'quiz_system_nonce')) {
            wp_die('Security check failed');
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $question_data = array(
            'id' => isset($_POST['question_id']) ? intval($_POST['question_id']) : 0,
            'title' => sanitize_text_field($_POST['title']),
            'type' => sanitize_key($_POST['type']),
            'content' => wp_kses_post($_POST['content']),
            'answers' => array()
        );
        
        // Process answers based on question type
        if (isset($_POST['answers']) && is_array($_POST['answers'])) {
            foreach ($_POST['answers'] as $key => $answer) {
                $question_data['answers'][] = array(
                    'content' => sanitize_textarea_field($answer['content']),
                    'is_correct' => isset($answer['is_correct']) ? intval($answer['is_correct']) : 0,
                    'weight' => isset($answer['weight']) ? intval($answer['weight']) : 0
                );
            }
        }
        
        $result = QuizSystemDatabase::save_question($question_data);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => $question_data['id'] ? 'Question updated successfully' : 'Question created successfully',
                'question_id' => $result
            ));
        } else {
            wp_send_json_error('Failed to save question');
        }
    }
    
    /**
     * Handle deleting a question via AJAX
     */
    public function handle_delete_question() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'quiz_system_nonce')) {
            wp_die('Security check failed');
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $question_id = intval($_POST['question_id']);
        
        $result = QuizSystemDatabase::delete_question($question_id);
        
        if ($result) {
            wp_send_json_success(array('message' => 'Question deleted successfully'));
        } else {
            wp_send_json_error('Failed to delete question');
        }
    }
    
    /**
     * Handle loading question form via AJAX
     */
    public function handle_load_question_form() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'quiz_system_nonce')) {
            wp_die('Security check failed');
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $question_id = intval($_POST['question_id']);
        $question = QuizSystemDatabase::get_question($question_id);
        
        if ($question) {
            wp_send_json_success(array('question' => $question));
        } else {
            wp_send_json_error('Question not found');
        }
    }
    
    /**
     * Handle save question request from admin page
     */
    private function handle_save_question_request() {
        if (!wp_verify_nonce($_POST['nonce'], 'quiz_system_nonce')) {
            add_settings_error('quiz_system', 'error', 'Security check failed');
            return;
        }
        
        $question_data = array(
            'id' => isset($_POST['question_id']) ? intval($_POST['question_id']) : 0,
            'title' => sanitize_text_field($_POST['title']),
            'type' => sanitize_key($_POST['type']),
            'content' => wp_kses_post($_POST['content']),
            'answers' => array()
        );
        
        // Process answers based on question type
        if (isset($_POST['answers']) && is_array($_POST['answers'])) {
            foreach ($_POST['answers'] as $key => $answer) {
                $question_data['answers'][] = array(
                    'content' => sanitize_textarea_field($answer['content']),
                    'is_correct' => isset($answer['is_correct']) ? intval($answer['is_correct']) : 0,
                    'weight' => isset($answer['weight']) ? intval($answer['weight']) : 0
                );
            }
        }
        
        $result = QuizSystemDatabase::save_question($question_data);
        
        if ($result) {
            add_settings_error('quiz_system', 'success', $question_data['id'] ? 'Question updated successfully' : 'Question created successfully', 'updated');
        } else {
            add_settings_error('quiz_system', 'error', 'Failed to save question');
        }
    }
    
    /**
     * Get question types
     */
    public static function get_question_types() {
        return array(
            'yes_no' => 'Yes/No',
            'single_choice' => 'Single Choice',
            'multiple_choice' => 'Multiple Choice',
            'text' => 'Text Answer'
        );
    }
}