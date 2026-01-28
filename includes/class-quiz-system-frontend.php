<?php
/**
 * Frontend handler for Quiz System
 */

if (!defined('ABSPATH')) {
    exit;
}

class QuizSystemFrontend {
    
    public function __construct() {
        add_action('wp_ajax_submit_quiz', array($this, 'handle_submit_quiz'));
        add_action('wp_ajax_nopriv_submit_quiz', array($this, 'handle_submit_quiz'));
        add_action('wp_ajax_save_quiz_progress', array($this, 'handle_save_quiz_progress'));
        add_action('wp_ajax_nopriv_save_quiz_progress', array($this, 'handle_save_quiz_progress'));
    }
    
    /**
     * Handle quiz submission
     */
    public function handle_submit_quiz() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'quiz_system_nonce')) {
            wp_die('Security check failed');
        }
        
        $quiz_id = intval($_POST['quiz_id']);
        $answers = isset($_POST['answers']) ? $_POST['answers'] : array();
        
        // Validate quiz exists
        $quiz = QuizSystemDatabase::get_quiz($quiz_id);
        if (!$quiz) {
            wp_send_json_error('Quiz not found');
        }
        
        // Prepare data for storage
        $submission_data = array(
            'quiz_id' => $quiz_id,
            'answers_data' => $answers,
            'score' => 0 // Calculate score if needed
        );
        
        $result = QuizSystemDatabase::save_result($submission_data);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => 'Quiz submitted successfully',
                'result_id' => $result
            ));
        } else {
            wp_send_json_error('Failed to submit quiz');
        }
    }
    
    /**
     * Handle saving quiz progress
     */
    public function handle_save_quiz_progress() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'quiz_system_nonce')) {
            wp_die('Security check failed');
        }
        
        $quiz_id = intval($_POST['quiz_id']);
        $current_question = intval($_POST['current_question']);
        $answers = isset($_POST['answers']) ? $_POST['answers'] : array();
        
        // Store temporary progress (could be in user meta, session, or DB)
        $progress_data = array(
            'quiz_id' => $quiz_id,
            'current_question' => $current_question,
            'answers' => $answers,
            'timestamp' => current_time('mysql')
        );
        
        // For now, we'll just return success - in a real implementation you'd store this
        // either in user meta if logged in, or in a session/db table if not
        $user_id = get_current_user_id();
        if ($user_id) {
            update_user_meta($user_id, 'quiz_progress_' . $quiz_id, $progress_data);
        } else {
            // For non-logged-in users, you might want to use cookies or a temporary DB table
            // For now, we'll just acknowledge the save
        }
        
        wp_send_json_success(array(
            'message' => 'Progress saved',
            'saved_data' => $progress_data
        ));
    }
    
    /**
     * Get question HTML based on type
     */
    public static function render_question($question, $question_number = 1) {
        $html = '<div class="quiz-question" data-question-id="' . esc_attr($question->id) . '" data-question-type="' . esc_attr($question->type) . '">';
        $html .= '<h3 class="question-title">' . esc_html($question_number . '. ' . $question->title) . '</h3>';
        
        if (!empty($question->content)) {
            $html .= '<div class="question-content">' . wp_kses_post($question->content) . '</div>';
        }
        
        $html .= '<div class="question-answers">';
        
        switch ($question->type) {
            case 'yes_no':
                $html .= self::render_yes_no_answers($question);
                break;
                
            case 'single_choice':
                $html .= self::render_single_choice_answers($question);
                break;
                
            case 'multiple_choice':
                $html .= self::render_multiple_choice_answers($question);
                break;
                
            case 'text':
                $html .= self::render_text_answer($question);
                break;
                
            default:
                $html .= '<p>Unknown question type</p>';
                break;
        }
        
        $html .= '</div>'; // .question-answers
        $html .= '</div>'; // .quiz-question
        
        return $html;
    }
    
    /**
     * Render Yes/No question
     */
    private static function render_yes_no_answers($question) {
        $html = '<div class="yes-no-options">';
        $html .= '<label class="answer-option"><input type="radio" name="answer_' . $question->id . '" value="yes"> Yes</label>';
        $html .= '<label class="answer-option"><input type="radio" name="answer_' . $question->id . '" value="no"> No</label>';
        $html .= '</div>';
        return $html;
    }
    
    /**
     * Render Single Choice question
     */
    private static function render_single_choice_answers($question) {
        $html = '<div class="single-choice-options">';
        foreach ($question->answers as $answer) {
            $html .= '<label class="answer-option"><input type="radio" name="answer_' . $question->id . '" value="' . esc_attr($answer['id']) . '"> ' . esc_html($answer['content']) . '</label>';
        }
        $html .= '</div>';
        return $html;
    }
    
    /**
     * Render Multiple Choice question
     */
    private static function render_multiple_choice_answers($question) {
        $html = '<div class="multiple-choice-options">';
        foreach ($question->answers as $answer) {
            $html .= '<label class="answer-option"><input type="checkbox" name="answer_' . $question->id . '[]" value="' . esc_attr($answer['id']) . '"> ' . esc_html($answer['content']) . '</label>';
        }
        $html .= '</div>';
        return $html;
    }
    
    /**
     * Render Text Answer question
     */
    private static function render_text_answer($question) {
        $html = '<textarea name="answer_' . $question->id . '" class="text-answer-input" placeholder="Type your answer here..."></textarea>';
        return $html;
    }
    
    /**
     * Get quiz progress for a user
     */
    public static function get_user_quiz_progress($quiz_id) {
        $user_id = get_current_user_id();
        if ($user_id) {
            return get_user_meta($user_id, 'quiz_progress_' . $quiz_id, true);
        }
        // For non-logged-in users, you would implement alternative storage
        return false;
    }
}