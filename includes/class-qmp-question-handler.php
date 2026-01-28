<?php
/**
 * Question Handler Class
 * Manages question creation, retrieval, and processing
 */
class QMP_Question_Handler {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_add_question', array($this, 'handle_add_question'));
        add_action('wp_ajax_get_questions', array($this, 'handle_get_questions'));
        add_action('wp_ajax_update_question_order', array($this, 'handle_update_question_order'));
    }

    /**
     * Add a new question
     */
    public function add_question($data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'qmp_questions';
        
        $result = $wpdb->insert(
            $table,
            array(
                'quiz_id' => intval($data['quiz_id']),
                'question_text' => sanitize_textarea_field($data['question_text']),
                'question_type' => sanitize_text_field($data['question_type']), // yes_no, single_choice, multiple_choice, text
                'settings' => !empty($data['settings']) ? json_encode($data['settings']) : '{}',
                'sort_order' => isset($data['sort_order']) ? intval($data['sort_order']) : 0
            ),
            array('%d', '%s', '%s', '%s', '%d')
        );
        
        if ($result === false) {
            return false;
        }
        
        $question_id = $wpdb->insert_id;
        
        // Add answers if provided
        if (!empty($data['answers'])) {
            foreach ($data['answers'] as $answer_data) {
                $this->add_answer(array(
                    'question_id' => $question_id,
                    'answer_text' => $answer_data['answer_text'],
                    'is_correct' => isset($answer_data['is_correct']) ? intval($answer_data['is_correct']) : 0,
                    'points' => isset($answer_data['points']) ? intval($answer_data['points']) : 0,
                    'sort_order' => isset($answer_data['sort_order']) ? intval($answer_data['sort_order']) : 0
                ));
            }
        }
        
        return $question_id;
    }

    /**
     * Get question by ID
     */
    public function get_question($question_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'qmp_questions';
        
        $question = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $question_id),
            ARRAY_A
        );
        
        if (!$question) {
            return false;
        }
        
        // Decode settings
        if (!empty($question['settings'])) {
            $question['settings'] = json_decode($question['settings'], true);
        } else {
            $question['settings'] = array();
        }
        
        // Get associated answers
        $question['answers'] = $this->get_answers_by_question($question['id']);
        
        return $question;
    }

    /**
     * Get all questions for a quiz
     */
    public function get_questions_by_quiz($quiz_id, $order_by = 'sort_order ASC') {
        global $wpdb;
        
        $table = $wpdb->prefix . 'qmp_questions';
        
        $questions = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$table} WHERE quiz_id = %d ORDER BY {$order_by}", $quiz_id),
            ARRAY_A
        );
        
        foreach ($questions as &$question) {
            if (!empty($question['settings'])) {
                $question['settings'] = json_decode($question['settings'], true);
            } else {
                $question['settings'] = array();
            }
            
            // Get associated answers
            $question['answers'] = $this->get_answers_by_question($question['id']);
        }
        
        return $questions;
    }

    /**
     * Update question
     */
    public function update_question($question_id, $data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'qmp_questions';
        
        $update_data = array();
        $format = array();
        
        if (isset($data['quiz_id'])) {
            $update_data['quiz_id'] = intval($data['quiz_id']);
            $format[] = '%d';
        }
        
        if (isset($data['question_text'])) {
            $update_data['question_text'] = sanitize_textarea_field($data['question_text']);
            $format[] = '%s';
        }
        
        if (isset($data['question_type'])) {
            $update_data['question_type'] = sanitize_text_field($data['question_type']);
            $format[] = '%s';
        }
        
        if (isset($data['settings'])) {
            $update_data['settings'] = json_encode($data['settings']);
            $format[] = '%s';
        }
        
        if (isset($data['sort_order'])) {
            $update_data['sort_order'] = intval($data['sort_order']);
            $format[] = '%d';
        }
        
        if (empty($update_data)) {
            return false;
        }
        
        $result = $wpdb->update(
            $table,
            $update_data,
            array('id' => $question_id),
            $format,
            array('%d')
        );
        
        // Update answers if provided
        if (isset($data['answers'])) {
            $this->update_question_answers($question_id, $data['answers']);
        }
        
        return $result !== false;
    }

    /**
     * Delete question
     */
    public function delete_question($question_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'qmp_questions';
        
        $result = $wpdb->delete(
            $table,
            array('id' => $question_id),
            array('%d')
        );
        
        return $result !== false;
    }

    /**
     * Add an answer to a question
     */
    public function add_answer($data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'qmp_answers';
        
        $result = $wpdb->insert(
            $table,
            array(
                'question_id' => intval($data['question_id']),
                'answer_text' => sanitize_textarea_field($data['answer_text']),
                'is_correct' => isset($data['is_correct']) ? intval($data['is_correct']) : 0,
                'points' => isset($data['points']) ? intval($data['points']) : 0,
                'sort_order' => isset($data['sort_order']) ? intval($data['sort_order']) : 0
            ),
            array('%d', '%s', '%d', '%d', '%d')
        );
        
        return $result !== false;
    }

    /**
     * Get answers by question ID
     */
    public function get_answers_by_question($question_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'qmp_answers';
        
        $answers = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$table} WHERE question_id = %d ORDER BY sort_order ASC", $question_id),
            ARRAY_A
        );
        
        return $answers;
    }

    /**
     * Update all answers for a question
     */
    public function update_question_answers($question_id, $answers) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'qmp_answers';
        
        // First, delete existing answers
        $wpdb->delete($table, array('question_id' => $question_id), array('%d'));
        
        // Then add new answers
        foreach ($answers as $answer_data) {
            $this->add_answer(array(
                'question_id' => $question_id,
                'answer_text' => $answer_data['answer_text'],
                'is_correct' => isset($answer_data['is_correct']) ? intval($answer_data['is_correct']) : 0,
                'points' => isset($answer_data['points']) ? intval($answer_data['points']) : 0,
                'sort_order' => isset($answer_data['sort_order']) ? intval($answer_data['sort_order']) : 0
            ));
        }
    }

    /**
     * Get question types
     */
    public function get_question_types() {
        return array(
            'yes_no' => array(
                'name' => __('Yes/No', 'quiz-maker-pro'),
                'description' => __('Simple yes or no question', 'quiz-maker-pro')
            ),
            'single_choice' => array(
                'name' => __('Single Choice', 'quiz-maker-pro'),
                'description' => __('Choose one answer from multiple options', 'quiz-maker-pro')
            ),
            'multiple_choice' => array(
                'name' => __('Multiple Choice', 'quiz-maker-pro'),
                'description' => __('Select one or more answers from multiple options', 'quiz-maker-pro')
            ),
            'text' => array(
                'name' => __('Text Answer', 'quiz-maker-pro'),
                'description' => __('Open-ended question requiring text response', 'quiz-maker-pro')
            )
        );
    }

    /**
     * AJAX handler for adding a question
     */
    public function handle_add_question() {
        // Check nonce and permissions
        if (!wp_verify_nonce($_POST['nonce'], 'qmp_manage_questions') || !current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'quiz-maker-pro'));
        }
        
        $question_id = $this->add_question($_POST);
        
        if ($question_id) {
            wp_send_json_success(array(
                'message' => __('Question added successfully', 'quiz-maker-pro'),
                'question_id' => $question_id
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Error adding question', 'quiz-maker-pro')
            ));
        }
    }

    /**
     * AJAX handler for getting questions
     */
    public function handle_get_questions() {
        $quiz_id = intval($_GET['quiz_id']);
        
        if (!$quiz_id) {
            wp_send_json_error(array(
                'message' => __('Invalid quiz ID', 'quiz-maker-pro')
            ));
        }
        
        $questions = $this->get_questions_by_quiz($quiz_id);
        
        if ($questions) {
            wp_send_json_success(array(
                'questions' => $questions
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('No questions found for this quiz', 'quiz-maker-pro')
            ));
        }
    }

    /**
     * AJAX handler for updating question order
     */
    public function handle_update_question_order() {
        // Check nonce and permissions
        if (!wp_verify_nonce($_POST['nonce'], 'qmp_manage_questions') || !current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'quiz-maker-pro'));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'qmp_questions';
        
        $orders = $_POST['order'];
        $success = true;
        
        foreach ($orders as $id => $order) {
            $result = $wpdb->update(
                $table,
                array('sort_order' => intval($order)),
                array('id' => intval($id)),
                array('%d'),
                array('%d')
            );
            
            if ($result === false) {
                $success = false;
            }
        }
        
        if ($success) {
            wp_send_json_success(array(
                'message' => __('Question order updated successfully', 'quiz-maker-pro')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Error updating question order', 'quiz-maker-pro')
            ));
        }
    }
}