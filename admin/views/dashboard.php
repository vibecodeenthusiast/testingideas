<div class="wrap">
    <h1>Quiz System Dashboard</h1>
    
    <div class="quiz-system-dashboard-widgets">
        <div class="widget-box">
            <h3>Quick Stats</h3>
            <ul>
                <li>Total Quizzes: <?php echo count(QuizSystemDatabase::get_quizzes()); ?></li>
                <li>Total Questions: <?php echo count(QuizSystemDatabase::get_all_questions()); ?></li>
            </ul>
        </div>
        
        <div class="widget-box">
            <h3>Quick Actions</h3>
            <ul>
                <li><a href="<?php echo admin_url('admin.php?page=quiz-system'); ?>">Manage Quizzes</a></li>
                <li><a href="<?php echo admin_url('admin.php?page=quiz-system-questions'); ?>">Manage Questions</a></li>
            </ul>
        </div>
    </div>
    
    <div class="quiz-system-info">
        <h2>About Quiz System</h2>
        <p>This plugin provides a comprehensive quiz system with support for various question types, question banks, and modern UI.</p>
        <p>Features include:</p>
        <ul>
            <li>Multiple question types (Yes/No, Single Choice, Multiple Choice, Text)</li>
            <li>Question bank for reusing questions across quizzes</li>
            <li>Modern, responsive design</li>
            <li>Progress saving functionality</li>
        </ul>
    </div>
</div>