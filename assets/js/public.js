jQuery(document).ready(function($) {
    // Handle quiz form submission
    $('.quiz-form').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var quizId = form.closest('.quiz-system-container').data('quiz-id');
        
        // Collect answers
        var answers = {};
        form.find('.quiz-question').each(function() {
            var questionId = $(this).data('question-id');
            var questionType = $(this).data('question-type');
            
            if (questionType === 'yes_no' || questionType === 'single_choice') {
                var selectedValue = $(this).find('input[type="radio"]:checked').val();
                answers[questionId] = selectedValue ? selectedValue : '';
            } 
            else if (questionType === 'multiple_choice') {
                var selectedValues = [];
                $(this).find('input[type="checkbox"]:checked').each(function() {
                    selectedValues.push($(this).val());
                });
                answers[questionId] = selectedValues;
            } 
            else if (questionType === 'text') {
                var textValue = $(this).find('textarea, input[type="text"]').val();
                answers[questionId] = textValue ? textValue.trim() : '';
            }
        });
        
        // Validate that all questions are answered (optional - could make this configurable)
        var allAnswered = true;
        var unansweredCount = 0;
        for (var qId in answers) {
            if (answers[qId] === '' || (Array.isArray(answers[qId]) && answers[qId].length === 0)) {
                allAnswered = false;
                unansweredCount++;
            }
        }
        
        if (!allAnswered) {
            if (confirm('Some questions are not answered. Do you still want to submit?')) {
                submitQuiz(quizId, answers);
            }
        } else {
            submitQuiz(quizId, answers);
        }
    });
    
    // Function to submit quiz
    function submitQuiz(quizId, answers) {
        $.post(ajaxurl, {
            action: 'submit_quiz',
            quiz_id: quizId,
            answers: answers,
            nonce: quiz_system_ajax.nonce
        }, function(response) {
            if (response.success) {
                // Hide the form and show results
                $('.quiz-form').hide();
                $('.quiz-results').show();
                $('.quiz-results p').text(response.data.message);
            } else {
                alert('Error submitting quiz: ' + response.data);
            }
        }).fail(function() {
            alert('There was an error processing your request.');
        });
    }
    
    // Auto-save progress as user answers questions
    $('.quiz-question input, .quiz-question textarea').on('change', function() {
        var container = $(this).closest('.quiz-system-container');
        var quizId = container.data('quiz-id');
        
        // Collect current answers
        var answers = {};
        container.find('.quiz-question').each(function() {
            var questionId = $(this).data('question-id');
            var questionType = $(this).data('question-type');
            
            if (questionType === 'yes_no' || questionType === 'single_choice') {
                var selectedValue = $(this).find('input[type="radio"]:checked').val();
                answers[questionId] = selectedValue ? selectedValue : '';
            } 
            else if (questionType === 'multiple_choice') {
                var selectedValues = [];
                $(this).find('input[type="checkbox"]:checked').each(function() {
                    selectedValues.push($(this).val());
                });
                answers[questionId] = selectedValues;
            } 
            else if (questionType === 'text') {
                var textValue = $(this).find('textarea, input[type="text"]').val();
                answers[questionId] = textValue ? textValue.trim() : '';
            }
        });
        
        // Find current question position
        var currentQuestionIndex = 0;
        var currentQuestion = $(this).closest('.quiz-question');
        var allQuestions = container.find('.quiz-question');
        
        allQuestions.each(function(index) {
            if ($(this).data('question-id') == currentQuestion.data('question-id')) {
                currentQuestionIndex = index + 1;
                return false;
            }
        });
        
        // Save progress
        $.post(ajaxurl, {
            action: 'save_quiz_progress',
            quiz_id: quizId,
            current_question: currentQuestionIndex,
            answers: answers,
            nonce: quiz_system_ajax.nonce
        });
    });
    
    // Update progress bar as user moves through questions
    function updateProgressBar() {
        $('.quiz-system-container').each(function() {
            var container = $(this);
            var totalQuestions = container.find('.quiz-question').length;
            
            if (totalQuestions > 0) {
                // For now, just show a simple percentage based on how many questions have answers
                var answeredCount = 0;
                container.find('.quiz-question').each(function() {
                    var questionId = $(this).data('question-id');
                    var questionType = $(this).data('question-type');
                    
                    if (questionType === 'yes_no' || questionType === 'single_choice') {
                        if ($(this).find('input[type="radio"]:checked').length > 0) {
                            answeredCount++;
                        }
                    } 
                    else if (questionType === 'multiple_choice') {
                        if ($(this).find('input[type="checkbox"]:checked').length > 0) {
                            answeredCount++;
                        }
                    } 
                    else if (questionType === 'text') {
                        var textValue = $(this).find('textarea, input[type="text"]').val();
                        if (textValue && textValue.trim() !== '') {
                            answeredCount++;
                        }
                    }
                });
                
                var percentage = Math.round((answeredCount / totalQuestions) * 100);
                
                container.find('.progress-fill').css('width', percentage + '%');
                container.find('.current-question').text(answeredCount);
                container.find('.total-questions').text(totalQuestions);
            }
        });
    }
    
    // Update progress bar when answers change
    $('.quiz-question input, .quiz-question textarea').on('change', function() {
        setTimeout(updateProgressBar, 100); // Small delay to ensure DOM updates
    });
    
    // Initial progress bar update
    updateProgressBar();
});