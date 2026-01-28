<?php
/**
 * Database handler for Quiz System
 */

if (!defined('ABSPATH')) {
    exit;
}

class QuizSystemDatabase {
    
    /**
     * Get all quizzes
     */
    public static function get_quizzes($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'limit' => 10,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $table = $wpdb->prefix . 'qs_quizzes';
        
        $query = "SELECT * FROM {$table} ORDER BY {$args['orderby']} {$args['order']}";
        
        if ($args['limit']) {
            $query .= $wpdb->prepare(" LIMIT %d", $args['limit']);
            if ($args['offset']) {
                $query .= $wpdb->prepare(" OFFSET %d", $args['offset']);
            }
        }
        
        return $wpdb->get_results($query);
    }
    
    /**
     * Get quiz by ID
     */
    public static function get_quiz($id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'qs_quizzes';
        
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id));
    }
    
    /**
     * Create or update quiz
     */
    public static function save_quiz($data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'qs_quizzes';
        
        $quiz_data = array(
            'title' => sanitize_text_field($data['title']),
            'description' => !empty($data['description']) ? wp_kses_post($data['description']) : '',
            'settings' => !empty($data['settings']) ? maybe_serialize($data['settings']) : ''
        );
        
        $format = array('%s', '%s', '%s');
        
        if (!empty($data['id'])) {
            // Update existing quiz
            $result = $wpdb->update(
                $table,
                $quiz_data,
                array('id' => intval($data['id'])),
                $format,
                array('%d')
            );
            return $result !== false ? intval($data['id']) : false;
        } else {
            // Insert new quiz
            $result = $wpdb->insert($table, $quiz_data, $format);
            return $result ? $wpdb->insert_id : false;
        }
    }
    
    /**
     * Delete quiz by ID
     */
    public static function delete_quiz($id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'qs_quizzes';
        
        return $wpdb->delete($table, array('id' => intval($id)), array('%d'));
    }
    
    /**
     * Get questions for a specific quiz
     */
    public static function get_questions_by_quiz($quiz_id, $args = array()) {
        global $wpdb;
        
        $defaults = array(
            'orderby' => 'question_order',
            'order' => 'ASC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $questions_table = $wpdb->prefix . 'qs_questions';
        $answers_table = $wpdb->prefix . 'qs_answers';
        
        $query = $wpdb->prepare("
            SELECT q.*, 
                   GROUP_CONCAT(a.id ORDER BY a.answer_order SEPARATOR ',') as answer_ids,
                   GROUP_CONCAT(a.content ORDER BY a.answer_order SEPARATOR '|||') as answer_contents,
                   GROUP_CONCAT(a.is_correct ORDER BY a.answer_order SEPARATOR ',') as answer_correctness,
                   GROUP_CONCAT(a.weight ORDER BY a.answer_order SEPARATOR ',') as answer_weights
            FROM {$questions_table} q
            LEFT JOIN {$answers_table} a ON q.id = a.question_id
            WHERE q.quiz_id = %d
            GROUP BY q.id
            ORDER BY q.{$args['orderby']} {$args['order']}
        ", $quiz_id);
        
        $results = $wpdb->get_results($query);
        
        // Process the answers for each question
        foreach ($results as $result) {
            if (!empty($result->answer_ids)) {
                $result->answers = array();
                $ids = explode(',', $result->answer_ids);
                $contents = explode('|||', $result->answer_contents);
                $correctness = explode(',', $result->answer_correctness);
                $weights = explode(',', $result->answer_weights);
                
                for ($i = 0; $i < count($ids); $i++) {
                    $result->answers[] = array(
                        'id' => intval($ids[$i]),
                        'content' => $contents[$i],
                        'is_correct' => boolval(intval($correctness[$i])),
                        'weight' => intval($weights[$i])
                    );
                }
            } else {
                $result->answers = array();
            }
            
            unset($result->answer_ids);
            unset($result->answer_contents);
            unset($result->answer_correctness);
            unset($result->answer_weights);
        }
        
        return $results;
    }
    
    /**
     * Get all questions (for question bank)
     */
    public static function get_all_questions($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'limit' => 20,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC',
            'type' => '', // Filter by question type
            'search' => ''
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $questions_table = $wpdb->prefix . 'qs_questions';
        
        $where_clause = "WHERE 1=1";
        
        if (!empty($args['type'])) {
            $where_clause .= $wpdb->prepare(" AND type = %s", $args['type']);
        }
        
        if (!empty($args['search'])) {
            $where_clause .= $wpdb->prepare(" AND (title LIKE %s OR content LIKE %s)", 
                                          '%' . $wpdb->esc_like($args['search']) . '%',
                                          '%' . $wpdb->esc_like($args['search']) . '%');
        }
        
        $query = "SELECT * FROM {$questions_table} {$where_clause} ORDER BY {$args['orderby']} {$args['order']}";
        
        if ($args['limit']) {
            $query .= $wpdb->prepare(" LIMIT %d", $args['limit']);
            if ($args['offset']) {
                $query .= $wpdb->prepare(" OFFSET %d", $args['offset']);
            }
        }
        
        return $wpdb->get_results($query);
    }
    
    /**
     * Get question by ID
     */
    public static function get_question($id) {
        global $wpdb;
        
        $questions_table = $wpdb->prefix . 'qs_questions';
        $answers_table = $wpdb->prefix . 'qs_answers';
        
        $query = $wpdb->prepare("
            SELECT q.*, 
                   GROUP_CONCAT(a.id ORDER BY a.answer_order SEPARATOR ',') as answer_ids,
                   GROUP_CONCAT(a.content ORDER BY a.answer_order SEPARATOR '|||') as answer_contents,
                   GROUP_CONCAT(a.is_correct ORDER BY a.answer_order SEPARATOR ',') as answer_correctness,
                   GROUP_CONCAT(a.weight ORDER BY a.answer_order SEPARATOR ',') as answer_weights
            FROM {$questions_table} q
            LEFT JOIN {$answers_table} a ON q.id = a.question_id
            WHERE q.id = %d
            GROUP BY q.id
        ", $id);
        
        $result = $wpdb->get_row($query);
        
        if ($result && !empty($result->answer_ids)) {
            $result->answers = array();
            $ids = explode(',', $result->answer_ids);
            $contents = explode('|||', $result->answer_contents);
            $correctness = explode(',', $result->answer_correctness);
            $weights = explode(',', $result->answer_weights);
            
            for ($i = 0; $i < count($ids); $i++) {
                $result->answers[] = array(
                    'id' => intval($ids[$i]),
                    'content' => $contents[$i],
                    'is_correct' => boolval(intval($correctness[$i])),
                    'weight' => intval($weights[$i])
                );
            }
        } else {
            $result->answers = array();
        }
        
        unset($result->answer_ids);
        unset($result->answer_contents);
        unset($result->answer_correctness);
        unset($result->answer_weights);
        
        return $result;
    }
    
    /**
     * Save question
     */
    public static function save_question($data) {
        global $wpdb;
        
        $questions_table = $wpdb->prefix . 'qs_questions';
        $answers_table = $wpdb->prefix . 'qs_answers';
        
        $question_data = array(
            'title' => sanitize_text_field($data['title']),
            'type' => sanitize_key($data['type']),
            'content' => !empty($data['content']) ? wp_kses_post($data['content']) : '',
            'question_order' => !empty($data['question_order']) ? intval($data['question_order']) : 0
        );
        
        // Add quiz_id only if it's not empty to avoid issues with NULL values
        if (!empty($data['quiz_id'])) {
            $question_data['quiz_id'] = intval($data['quiz_id']);
        } else {
            $question_data['quiz_id'] = null;
        }

        if (!empty($data['quiz_id'])) {
            $format = array('%s', '%s', '%s', '%d', '%d');
        } else {
            $format = array('%s', '%s', '%s', null, '%d');
        }
        
        if (!empty($data['id'])) {
            // Update existing question
            $result = $wpdb->update(
                $questions_table,
                $question_data,
                array('id' => intval($data['id'])),
                $format,
                array('%d')
            );
            
            $question_id = intval($data['id']);
        } else {
            // Insert new question
            $result = $wpdb->insert($questions_table, $question_data, $format);
            $question_id = $result ? $wpdb->insert_id : false;
        }
        
        if ($question_id) {
            // Handle answers if provided
            if (isset($data['answers']) && is_array($data['answers'])) {
                // First, delete existing answers
                $wpdb->delete($answers_table, array('question_id' => $question_id), array('%d'));
                
                // Then insert new answers
                foreach ($data['answers'] as $index => $answer) {
                    $answer_data = array(
                        'question_id' => $question_id,
                        'content' => sanitize_textarea_field($answer['content']),
                        'is_correct' => isset($answer['is_correct']) ? intval($answer['is_correct']) : 0,
                        'weight' => isset($answer['weight']) ? intval($answer['weight']) : 0,
                        'answer_order' => $index
                    );
                    
                    $wpdb->insert($answers_table, $answer_data, array('%d', '%s', '%d', '%d', '%d'));
                }
            }
            
            return $question_id;
        }
        
        return false;
    }
    
    /**
     * Delete question by ID
     */
    public static function delete_question($id) {
        global $wpdb;
        
        $questions_table = $wpdb->prefix . 'qs_questions';
        
        return $wpdb->delete($questions_table, array('id' => intval($id)), array('%d'));
    }
    
    /**
     * Save quiz result
     */
    public static function save_result($data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'qs_results';
        
        $result_data = array(
            'quiz_id' => intval($data['quiz_id']),
            'user_id' => !empty($data['user_id']) ? intval($data['user_id']) : get_current_user_id(),
            'answers_data' => maybe_serialize($data['answers_data']),
            'score' => !empty($data['score']) ? floatval($data['score']) : 0
        );
        
        $format = array('%d', '%d', '%s', '%f');
        
        $result = $wpdb->insert($table, $result_data, $format);
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Get quiz results
     */
    public static function get_results($quiz_id, $user_id = null) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'qs_results';
        
        if ($user_id) {
            $query = $wpdb->prepare(
                "SELECT * FROM {$table} WHERE quiz_id = %d AND user_id = %d ORDER BY completed_at DESC",
                $quiz_id,
                $user_id
            );
        } else {
            $query = $wpdb->prepare(
                "SELECT * FROM {$table} WHERE quiz_id = %d ORDER BY completed_at DESC",
                $quiz_id
            );
        }
        
        return $wpdb->get_results($query);
    }
}