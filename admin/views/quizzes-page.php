<div class="wrap">
    <h1>Quizzes</h1>
    
    <div class="wp-header-end"></div>
    
    <div class="tablenav top">
        <div class="alignleft actions bulkactions">
            <button type="button" class="button button-primary" id="add-new-quiz">Add New Quiz</button>
        </div>
    </div>
    
    <!-- Quizzes Table -->
    <table class="wp-list-table widefat fixed striped table-view-list quizzes">
        <thead>
            <tr>
                <th scope="col" class="manage-column">ID</th>
                <th scope="col" class="manage-column">Title</th>
                <th scope="col" class="manage-column">Questions</th>
                <th scope="col" class="manage-column">Actions</th>
            </tr>
        </thead>
        <tbody id="the-list">
            <?php if (!empty($quizzes)): ?>
                <?php foreach ($quizzes as $quiz): ?>
                    <tr id="quiz-<?php echo $quiz->id; ?>">
                        <td><?php echo $quiz->id; ?></td>
                        <td><strong><?php echo esc_html($quiz->title); ?></strong></td>
                        <td>
                            <?php 
                            $questions = QuizSystemDatabase::get_questions_by_quiz($quiz->id);
                            echo count($questions);
                            ?>
                        </td>
                        <td>
                            <button type="button" class="button edit-quiz-btn" data-quiz-id="<?php echo $quiz->id; ?>">Edit</button>
                            <button type="button" class="button button-link-delete delete-quiz-btn" data-quiz-id="<?php echo $quiz->id; ?>">Delete</button>
                            <a href="#" class="button">Preview</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4">No quizzes found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <th scope="col">ID</th>
                <th scope="col">Title</th>
                <th scope="col">Questions</th>
                <th scope="col">Actions</th>
            </tr>
        </tfoot>
    </table>
    
    <!-- Add/Edit Quiz Modal -->
    <div id="quiz-modal" class="modal" style="display:none;">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2 id="modal-title">Add New Quiz</h2>
            <form id="quiz-form" method="post">
                <?php wp_nonce_field('quiz_system_nonce', 'nonce'); ?>
                <input type="hidden" id="quiz-id" name="quiz_id" value="">
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="quiz-title">Quiz Title *</label></th>
                        <td><input type="text" id="quiz-title" name="title" class="regular-text" required></td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="quiz-description">Description</label></th>
                        <td><textarea id="quiz-description" name="description" class="large-text" rows="4"></textarea></td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Settings</th>
                        <td>
                            <fieldset>
                                <legend class="screen-reader-text"><span>Quiz Settings</span></legend>
                                <label for="show-progress">
                                    <input type="checkbox" id="show-progress" name="settings[show_progress]" value="1">
                                    Show Progress Bar
                                </label><br>
                                
                                <label for="random-questions">
                                    <input type="checkbox" id="random-questions" name="settings[random_questions]" value="1">
                                    Randomize Questions Order
                                </label><br>
                                
                                <label for="allow-back">
                                    <input type="checkbox" id="allow-back" name="settings[allow_back]" value="1">
                                    Allow Back Navigation
                                </label><br>
                                
                                <label for="require-login">
                                    <input type="checkbox" id="require-login" name="settings[require_login]" value="1">
                                    Require Login to Take Quiz
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" id="submit-quiz" class="button-primary" value="Save Quiz">
                </p>
            </form>
        </div>
    </div>
</div>

<style>
.modal {
    position: fixed;
    z-index: 10000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.4);
}

.modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 60%;
    max-width: 800px;
    border-radius: 4px;
    position: relative;
}

.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    position: absolute;
    right: 10px;
    top: 5px;
}

.close:hover,
.close:focus {
    color: black;
}
</style>