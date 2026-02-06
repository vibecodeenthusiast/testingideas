<div class="wrap">
    <h1>Question Bank</h1>
    
    <!-- Add New Question Button -->
    <div class="wp-header-end"></div>
    
    <div class="tablenav top">
        <div class="alignleft actions bulkactions">
            <button type="button" class="button button-primary" id="add-new-question">Add New Question</button>
        </div>
        
        <!-- Search and Filter Form -->
        <form method="get" class="search-form">
            <input type="hidden" name="page" value="quiz-system-questions" />
            
            <select name="type" id="filter-by-type">
                <option value="">All Types</option>
                <?php foreach (QuizSystemQuestionBank::get_question_types() as $type_key => $type_label): ?>
                    <option value="<?php echo esc_attr($type_key); ?>" <?php selected($type_filter, $type_key); ?>>
                        <?php echo esc_html($type_label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <input type="search" id="question-search-input" name="search" value="<?php echo esc_attr($search); ?>" placeholder="Search questions...">
            
            <?php submit_button('Filter', 'secondary', '', false, array('id' => 'question-search-submit')); ?>
        </form>
    </div>
    
    <!-- Questions Table -->
    <table class="wp-list-table widefat fixed striped table-view-list questions">
        <thead>
            <tr>
                <th scope="col" class="manage-column">ID</th>
                <th scope="col" class="manage-column">Title</th>
                <th scope="col" class="manage-column">Type</th>
                <th scope="col" class="manage-column">Actions</th>
            </tr>
        </thead>
        <tbody id="the-list">
            <?php if (!empty($questions)): ?>
                <?php foreach ($questions as $question): ?>
                    <tr id="question-<?php echo $question->id; ?>">
                        <td><?php echo $question->id; ?></td>
                        <td><?php echo esc_html($question->title); ?></td>
                        <td>
                            <?php 
                            $types = QuizSystemQuestionBank::get_question_types();
                            echo isset($types[$question->type]) ? $types[$question->type] : ucfirst(str_replace('_', ' ', $question->type));
                            ?>
                        </td>
                        <td>
                            <button type="button" class="button edit-question-btn" data-question-id="<?php echo $question->id; ?>">Edit</button>
                            <button type="button" class="button button-link-delete delete-question-btn" data-question-id="<?php echo $question->id; ?>">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4">No questions found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <th scope="col">ID</th>
                <th scope="col">Title</th>
                <th scope="col">Type</th>
                <th scope="col">Actions</th>
            </tr>
        </tfoot>
    </table>
    
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <span class="displaying-num"><?php echo $total_questions; ?> items</span>
                <span class="pagination-links">
                    <?php
                    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
                    
                    // Previous link
                    if ($current_page > 1) {
                        $prev_page = $current_page - 1;
                        echo '<a class="prev-page button" href="' . add_query_arg('paged', $prev_page) . '"><span aria-hidden="true">«</span></a>';
                    } else {
                        echo '<span class="prev-page button disabled">«</span>';
                    }
                    
                    // Page numbers
                    echo '<span class="paging-input">';
                    for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++) {
                        if ($i == $current_page) {
                            echo '<span class="current-page">' . $i . '</span>';
                        } else {
                            echo '<a class="page-numbers" href="' . add_query_arg('paged', $i) . '">' . $i . '</a>';
                        }
                    }
                    echo '</span>';
                    
                    // Next link
                    if ($current_page < $total_pages) {
                        $next_page = $current_page + 1;
                        echo '<a class="next-page button" href="' . add_query_arg('paged', $next_page) . '"><span aria-hidden="true">»</span></a>';
                    } else {
                        echo '<span class="next-page button disabled">»</span>';
                    }
                    ?>
                </span>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Add/Edit Question Modal -->
    <div id="question-modal" class="modal" style="display:none;">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2 id="modal-title">Add New Question</h2>
            <form id="question-form" method="post">
                <?php wp_nonce_field('quiz_system_nonce', 'nonce'); ?>
                <input type="hidden" id="question-id" name="question_id" value="">
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="question-title">Question Title *</label></th>
                        <td><input type="text" id="question-title" name="title" class="regular-text" required></td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="question-type">Question Type *</label></th>
                        <td>
                            <select id="question-type" name="type" required>
                                <option value="">Select Type</option>
                                <?php foreach (QuizSystemQuestionBank::get_question_types() as $type_key => $type_label): ?>
                                    <option value="<?php echo esc_attr($type_key); ?>"><?php echo esc_html($type_label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="question-content">Question Content</label></th>
                        <td><textarea id="question-content" name="content" class="large-text" rows="4"></textarea></td>
                    </tr>
                    
                    <tbody id="answers-container">
                        <!-- Answers will be dynamically added here based on question type -->
                    </tbody>
                </table>
                
                <p class="submit">
                    <input type="submit" id="submit-question" class="button-primary" value="Save Question">
                    <button type="button" id="add-answer-btn" class="button-secondary">Add Answer</button>
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

.answer-row {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.answer-row .answer-input {
    flex-grow: 1;
    margin-right: 10px;
}

.answer-row .answer-actions {
    display: flex;
    gap: 5px;
}

.delete-answer-btn {
    color: #dc3232;
    background: none;
    border: none;
    cursor: pointer;
    font-size: 16px;
}

.yes-no-answers {
    display: flex;
    gap: 20px;
}

.yes-no-answers label {
    display: flex;
    align-items: center;
    gap: 5px;
}
</style>