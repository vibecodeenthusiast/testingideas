<?php
$quiz_id = intval($atts['id']);

if (!$quiz_id) {
    echo '<p>Please specify a quiz ID.</p>';
    return;
}

$quiz = QuizSystemQuizzes::get_quiz_with_questions($quiz_id);

if (!$quiz) {
    echo '<p>Quiz not found.</p>';
    return;
}

// Check if user is required to be logged in
if ($quiz->settings && isset($quiz->settings['require_login']) && $quiz->settings['require_login'] && !is_user_logged_in()) {
    echo '<p>You must be logged in to take this quiz.</p>';
    return;
}

// Get any saved progress for the user
$user_progress = QuizSystemFrontend::get_user_quiz_progress($quiz_id);
?>

<div class="quiz-system-container" id="quiz-<?php echo $quiz_id; ?>" data-quiz-id="<?php echo $quiz_id; ?>">
    <div class="quiz-header">
        <h2 class="quiz-title"><?php echo esc_html($quiz->title); ?></h2>
        <?php if (!empty($quiz->description)): ?>
            <div class="quiz-description"><?php echo wp_kses_post($quiz->description); ?></div>
        <?php endif; ?>
        
        <?php if ($quiz->settings && isset($quiz->settings['show_progress']) && $quiz->settings['show_progress']): ?>
            <div class="quiz-progress">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: 0%"></div>
                </div>
                <div class="progress-text">Question <span class="current-question">1</span> of <span class="total-questions"><?php echo count($quiz->questions); ?></span></div>
            </div>
        <?php endif; ?>
    </div>
    
    <form class="quiz-form" id="quiz-form-<?php echo $quiz_id; ?>">
        <div class="quiz-questions">
            <?php foreach ($quiz->questions as $index => $question): ?>
                <?php echo QuizSystemFrontend::render_question($question, $index + 1); ?>
            <?php endforeach; ?>
        </div>
        
        <div class="quiz-actions">
            <button type="submit" class="submit-quiz-button">Submit Quiz</button>
        </div>
    </form>
    
    <div class="quiz-results" style="display: none;">
        <h3>Quiz Results</h3>
        <p>Your answers have been submitted successfully!</p>
    </div>
</div>

<style>
.quiz-system-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    font-family: Arial, sans-serif;
}

.quiz-header {
    margin-bottom: 30px;
}

.quiz-title {
    font-size: 2em;
    margin-bottom: 10px;
    color: #333;
}

.quiz-description {
    margin-bottom: 20px;
    color: #666;
    line-height: 1.6;
}

.quiz-progress {
    margin-top: 20px;
}

.progress-bar {
    width: 100%;
    height: 20px;
    background-color: #e0e0e0;
    border-radius: 10px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background-color: #4CAF50;
    transition: width 0.3s ease;
}

.progress-text {
    text-align: center;
    margin-top: 5px;
    font-weight: bold;
}

.quiz-question {
    margin-bottom: 30px;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 8px;
    background-color: #fafafa;
}

.question-title {
    font-size: 1.2em;
    margin-top: 0;
    margin-bottom: 15px;
    color: #333;
}

.question-content {
    margin-bottom: 15px;
    color: #555;
}

.question-answers {
    margin-left: 15px;
}

.answer-option {
    display: block;
    margin-bottom: 10px;
    padding: 10px;
    background-color: white;
    border: 1px solid #ddd;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s;
}

.answer-option:hover {
    background-color: #f0f0f0;
}

.answer-option input[type="radio"],
.answer-option input[type="checkbox"] {
    margin-right: 10px;
}

.text-answer-input {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    resize: vertical;
}

.quiz-actions {
    margin-top: 30px;
    text-align: center;
}

.submit-quiz-button {
    background-color: #0073aa;
    color: white;
    border: none;
    padding: 12px 30px;
    font-size: 16px;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.3s;
}

.submit-quiz-button:hover {
    background-color: #005a87;
}

.quiz-results {
    margin-top: 30px;
    padding: 20px;
    background-color: #d4edda;
    border: 1px solid #c3e6cb;
    border-radius: 4px;
    color: #155724;
}

.yes-no-options,
.single-choice-options,
.multiple-choice-options {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
</style>