<?php
/**
 * Shortcode Handler Class
 * Handles the [qmp_quiz] shortcode to display quizzes on frontend
 */
class QMP_Shortcode_Handler {

    /**
     * Constructor
     */
    public function __construct() {
        add_shortcode('qmp_quiz', array($this, 'render_quiz_shortcode'));
    }

    /**
     * Render the quiz shortcode
     */
    public function render_quiz_shortcode($atts) {
        // Parse attributes
        $atts = shortcode_atts(array(
            'id' => 0,
            'quiz_id' => 0
        ), $atts);

        // Get the quiz ID from either attribute
        $quiz_id = !empty($atts['id']) ? intval($atts['id']) : intval($atts['quiz_id']);

        if (!$quiz_id) {
            return '<p>' . __('No quiz ID provided', 'quiz-maker-pro') . '</p>';
        }

        // Get quiz data
        $quiz_handler = new QMP_Quiz_Handler();
        $quiz = $quiz_handler->get_quiz($quiz_id);

        if (!$quiz) {
            return '<p>' . __('Quiz not found', 'quiz-maker-pro') . '</p>';
        }

        // Get questions for the quiz
        $question_handler = new QMP_Question_Handler();
        $questions = $question_handler->get_questions_by_quiz($quiz_id);

        if (empty($questions)) {
            return '<p>' . __('No questions found for this quiz', 'quiz-maker-pro') . '</p>';
        }

        // Start output buffering to capture HTML
        ob_start();

        // Render the quiz HTML
        $this->display_quiz($quiz, $questions);

        // Get the buffered content and return it
        return ob_get_clean();
    }

    /**
     * Display the quiz with questions
     */
    private function display_quiz($quiz, $questions) {
        $total_questions = count($questions);
        ?>
        <div class="qmp-quiz-container" id="qmp-quiz-<?php echo esc_attr($quiz['id']); ?>">
            <h2><?php echo esc_html($quiz['title']); ?></h2>
            <?php if (!empty($quiz['description'])): ?>
                <div class="qmp-quiz-description"><?php echo wp_kses_post($quiz['description']); ?></div>
            <?php endif; ?>

            <div class="qmp-progress-bar">
                <div class="qmp-progress"></div>
            </div>

            <form id="qmp-quiz-form-<?php echo esc_attr($quiz['id']); ?>" class="qmp-quiz-form">
                <input type="hidden" name="quiz_id" value="<?php echo esc_attr($quiz['id']); ?>" />
                <input type="hidden" name="current_question" value="1" class="qmp-current-question" />
                <input type="hidden" name="total_questions" value="<?php echo esc_attr($total_questions); ?>" class="qmp-total-questions" />

                <?php $counter = 1; ?>
                <?php foreach ($questions as $question): ?>
                    <div class="qmp-question qmp-question-<?php echo $counter; ?>" style="<?php echo $counter > 1 ? 'display:none;' : ''; ?>">
                        <div class="qmp-question-header">
                            <h3><?php printf(__('Question %d of %d', 'quiz-maker-pro'), $counter, $total_questions); ?></h3>
                            <div class="qmp-question-text"><?php echo esc_html($question['question_text']); ?></div>
                        </div>

                        <div class="qmp-answers">
                            <?php echo $this->render_question_answers($question); ?>
                        </div>
                    </div>
                    <?php $counter++; ?>
                <?php endforeach; ?>

                <div class="qmp-navigation">
                    <button type="button" class="qmp-btn qmp-prev-btn" style="display:none;"> <?php _e('Previous', 'quiz-maker-pro'); ?></button>
                    <?php if ($total_questions > 1): ?>
                        <button type="button" class="qmp-btn qmp-next-btn"> <?php _e('Next', 'quiz-maker-pro'); ?></button>
                    <?php endif; ?>
                    <button type="button" class="qmp-btn qmp-submit-btn" style="<?php echo $total_questions > 1 ? 'display:none;' : ''; ?>"> <?php _e('Submit Quiz', 'quiz-maker-pro'); ?></button>
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * Render answers for a specific question based on its type
     */
    private function render_question_answers($question) {
        $html = '';
        $question_type = $question['question_type'];

        switch ($question_type) {
            case 'yes_no':
                $html .= $this->render_yes_no_answers($question);
                break;

            case 'single_choice':
                $html .= $this->render_single_choice_answers($question);
                break;

            case 'multiple_choice':
                $html .= $this->render_multiple_choice_answers($question);
                break;

            case 'text':
                $html .= $this->render_text_answer($question);
                break;

            default:
                $html .= '<p>' . __('Unknown question type', 'quiz-maker-pro') . '</p>';
                break;
        }

        return $html;
    }

    /**
     * Render Yes/No question type
     */
    private function render_yes_no_answers($question) {
        $html = '<div class="qmp-answer-options">';
        $html .= '<div class="qmp-answer-option">';
        $html .= '<input type="radio" id="q_' . $question['id'] . '_yes" name="q_' . $question['id'] . '" value="yes">';
        $html .= '<label for="q_' . $question['id'] . '_yes">' . __('Yes', 'quiz-maker-pro') . '</label>';
        $html .= '</div>';
        $html .= '<div class="qmp-answer-option">';
        $html .= '<input type="radio" id="q_' . $question['id'] . '_no" name="q_' . $question['id'] . '" value="no">';
        $html .= '<label for="q_' . $question['id'] . '_no">' . __('No', 'quiz-maker-pro') . '</label>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Render Single Choice question type
     */
    private function render_single_choice_answers($question) {
        $html = '<div class="qmp-answer-options">';
        foreach ($question['answers'] as $answer) {
            $html .= '<div class="qmp-answer-option">';
            $html .= '<input type="radio" id="q_' . $question['id'] . '_ans_' . $answer['id'] . '" name="q_' . $question['id'] . '" value="' . esc_attr($answer['id']) . '">';
            $html .= '<label for="q_' . $question['id'] . '_ans_' . $answer['id'] . '">' . esc_html($answer['answer_text']) . '</label>';
            $html .= '</div>';
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * Render Multiple Choice question type
     */
    private function render_multiple_choice_answers($question) {
        $html = '<div class="qmp-answer-options">';
        foreach ($question['answers'] as $answer) {
            $html .= '<div class="qmp-answer-option">';
            $html .= '<input type="checkbox" id="q_' . $question['id'] . '_ans_' . $answer['id'] . '" name="q_' . $question['id'] . '[]" value="' . esc_attr($answer['id']) . '">';
            $html .= '<label for="q_' . $question['id'] . '_ans_' . $answer['id'] . '">' . esc_html($answer['answer_text']) . '</label>';
            $html .= '</div>';
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * Render Text Answer question type
     */
    private function render_text_answer($question) {
        $html = '<div class="qmp-answer-options">';
        $html .= '<textarea name="q_' . $question['id'] . '" class="qmp-text-answer" rows="4" cols="50" placeholder="' . __('Type your answer here...', 'quiz-maker-pro') . '"></textarea>';
        $html .= '</div>';

        return $html;
    }
}