<?php
/**
 * Quiz Handler Class
 * Manages quiz creation, retrieval, and processing
 */
class QMP_Quiz_Handler {

    /**
     * Constructor
     */
    public function __construct() {
        // Hook into necessary actions
        add_action('init', array($this, 'register_post_types'));
        add_action('wp_ajax_create_quiz', array($this, 'handle_create_quiz'));
        add_action('wp_ajax_get_quiz', array($this, 'handle_get_quiz'));
    }

    /**
     * Register custom post types if needed
     */
    public function register_post_types() {
        // Implementation for registering quiz post types if needed
    }

    /**
     * Create a new quiz
     */
    public function create_quiz($data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'qmp_quizzes';
        
        $result = $wpdb->insert(
            $table,
            array(
                'title' => sanitize_text_field($data['title']),
                'description' => !empty($data['description']) ? sanitize_textarea_field($data['description']) : '',
                'settings' => !empty($data['settings']) ? json_encode($data['settings']) : '{}'
            ),
            array('%s', '%s', '%s')
        );
        
        if ($result === false) {
            return false;
        }
        
        return $wpdb->insert_id;
    }

    /**
     * Get quiz by ID
     */
    public function get_quiz($quiz_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'qmp_quizzes';
        
        $quiz = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $quiz_id),
            ARRAY_A
        );
        
        if (!$quiz) {
            return false;
        }
        
        // Decode settings
        if (!empty($quiz['settings'])) {
            $quiz['settings'] = json_decode($quiz['settings'], true);
        } else {
            $quiz['settings'] = array();
        }
        
        return $quiz;
    }

    /**
     * Get all quizzes
     */
    public function get_all_quizzes($limit = 10, $offset = 0) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'qmp_quizzes';
        
        $quizzes = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d OFFSET %d", $limit, $offset),
            ARRAY_A
        );
        
        foreach ($quizzes as &$quiz) {
            if (!empty($quiz['settings'])) {
                $quiz['settings'] = json_decode($quiz['settings'], true);
            } else {
                $quiz['settings'] = array();
            }
        }
        
        return $quizzes;
    }

    /**
     * Update quiz
     */
    public function update_quiz($quiz_id, $data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'qmp_quizzes';
        
        $update_data = array();
        $format = array();
        
        if (isset($data['title'])) {
            $update_data['title'] = sanitize_text_field($data['title']);
            $format[] = '%s';
        }
        
        if (isset($data['description'])) {
            $update_data['description'] = sanitize_textarea_field($data['description']);
            $format[] = '%s';
        }
        
        if (isset($data['settings'])) {
            $update_data['settings'] = json_encode($data['settings']);
            $format[] = '%s';
        }
        
        if (empty($update_data)) {
            return false;
        }
        
        $result = $wpdb->update(
            $table,
            $update_data,
            array('id' => $quiz_id),
            $format,
            array('%d')
        );
        
        return $result !== false;
    }

    /**
     * Delete quiz
     */
    public function delete_quiz($quiz_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'qmp_quizzes';
        
        $result = $wpdb->delete(
            $table,
            array('id' => $quiz_id),
            array('%d')
        );
        
        return $result !== false;
    }

    /**
     * AJAX handler for creating a quiz
     */
    public function handle_create_quiz() {
        // Check nonce and permissions
        if (!wp_verify_nonce($_POST['nonce'], 'qmp_add_quiz') || !current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'quiz-maker-pro'));
        }
        
        $quiz_id = $this->create_quiz($_POST);
        
        if ($quiz_id) {
            wp_send_json_success(array(
                'message' => __('Quiz created successfully', 'quiz-maker-pro'),
                'quiz_id' => $quiz_id
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Error creating quiz', 'quiz-maker-pro')
            ));
        }
    }

    /**
     * AJAX handler for getting a quiz
     */
    public function handle_get_quiz() {
        $quiz_id = intval($_GET['quiz_id']);
        
        if (!$quiz_id) {
            wp_send_json_error(array(
                'message' => __('Invalid quiz ID', 'quiz-maker-pro')
            ));
        }
        
        $quiz = $this->get_quiz($quiz_id);
        
        if ($quiz) {
            wp_send_json_success($quiz);
        } else {
            wp_send_json_error(array(
                'message' => __('Quiz not found', 'quiz-maker-pro')
            ));
        }
    }
}