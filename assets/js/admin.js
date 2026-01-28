jQuery(document).ready(function($) {
    // Add/Edit Question Modal
    var questionModal = $('#question-modal');
    var quizModal = $('#quiz-modal');
    
    // Open Add Question Modal
    $('#add-new-question').on('click', function() {
        $('#modal-title').text('Add New Question');
        $('#question-form')[0].reset();
        $('#question-id').val('');
        $('#answers-container').empty();
        questionModal.show();
    });
    
    // Open Edit Question Modal
    $(document).on('click', '.edit-question-btn', function() {
        var questionId = $(this).data('question-id');
        
        $.post(ajaxurl, {
            action: 'load_question_form',
            question_id: questionId,
            nonce: quiz_system_ajax.nonce
        }, function(response) {
            if (response.success) {
                var question = response.data.question;
                
                $('#modal-title').text('Edit Question');
                $('#question-id').val(question.id);
                $('#question-title').val(question.title);
                $('#question-type').val(question.type);
                $('#question-content').val(question.content);
                
                // Populate answers based on question type
                populateAnswersForEdit(question);
                
                questionModal.show();
            } else {
                alert('Error loading question: ' + response.data);
            }
        });
    });
    
    // Close modals
    $('.close').on('click', function() {
        questionModal.hide();
        quizModal.hide();
    });
    
    $(window).on('click', function(event) {
        if (event.target.id == 'question-modal') {
            questionModal.hide();
        }
        if (event.target.id == 'quiz-modal') {
            quizModal.hide();
        }
    });
    
    // Handle question type change
    $('#question-type').on('change', function() {
        var questionType = $(this).val();
        populateAnswersContainer(questionType);
    });
    
    // Add answer button
    $('#add-answer-btn').on('click', function() {
        var questionType = $('#question-type').val();
        addAnswerRow(questionType);
    });
    
    // Delete answer button
    $(document).on('click', '.delete-answer-btn', function() {
        $(this).closest('.answer-row').remove();
    });
    
    // Submit question form
    $('#question-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        formData += '&action=save_question&nonce=' + quiz_system_ajax.nonce;
        
        $.post(ajaxurl, formData, function(response) {
            if (response.success) {
                alert(response.data.message);
                questionModal.hide();
                location.reload(); // Refresh the page to show updated list
            } else {
                alert('Error: ' + response.data);
            }
        });
    });
    
    // Delete question
    $(document).on('click', '.delete-question-btn', function() {
        if (confirm('Are you sure you want to delete this question?')) {
            var questionId = $(this).data('question-id');
            
            $.post(ajaxurl, {
                action: 'delete_question',
                question_id: questionId,
                nonce: quiz_system_ajax.nonce
            }, function(response) {
                if (response.success) {
                    alert(response.data.message);
                    $('#question-' + questionId).remove();
                } else {
                    alert('Error: ' + response.data);
                }
            });
        }
    });
    
    // Open Add Quiz Modal
    $('#add-new-quiz').on('click', function() {
        $('#quiz-modal #modal-title').text('Add New Quiz');
        $('#quiz-form')[0].reset();
        $('#quiz-id').val('');
        quizModal.show();
    });
    
    // Open Edit Quiz Modal
    $(document).on('click', '.edit-quiz-btn', function() {
        var quizId = $(this).data('quiz-id');
        
        $.post(ajaxurl, {
            action: 'load_quiz_form',
            quiz_id: quizId,
            nonce: quiz_system_ajax.nonce
        }, function(response) {
            if (response.success) {
                var quiz = response.data.quiz;
                
                $('#quiz-modal #modal-title').text('Edit Quiz');
                $('#quiz-id').val(quiz.id);
                $('#quiz-title').val(quiz.title);
                $('#quiz-description').val(quiz.description);
                
                // Set checkbox values
                if (quiz.settings && quiz.settings.show_progress) {
                    $('#show-progress').prop('checked', true);
                }
                if (quiz.settings && quiz.settings.random_questions) {
                    $('#random-questions').prop('checked', true);
                }
                if (quiz.settings && quiz.settings.allow_back) {
                    $('#allow-back').prop('checked', true);
                }
                if (quiz.settings && quiz.settings.require_login) {
                    $('#require-login').prop('checked', true);
                }
                
                quizModal.show();
            } else {
                alert('Error loading quiz: ' + response.data);
            }
        });
    });
    
    // Submit quiz form
    $('#quiz-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        formData += '&action=save_quiz&nonce=' + quiz_system_ajax.nonce;
        
        $.post(ajaxurl, formData, function(response) {
            if (response.success) {
                alert(response.data.message);
                quizModal.hide();
                location.reload(); // Refresh the page to show updated list
            } else {
                alert('Error: ' + response.data);
            }
        });
    });
    
    // Delete quiz
    $(document).on('click', '.delete-quiz-btn', function() {
        if (confirm('Are you sure you want to delete this quiz?')) {
            var quizId = $(this).data('quiz-id');
            
            $.post(ajaxurl, {
                action: 'delete_quiz',
                quiz_id: quizId,
                nonce: quiz_system_ajax.nonce
            }, function(response) {
                if (response.success) {
                    alert(response.data.message);
                    $('#quiz-' + quizId).remove();
                } else {
                    alert('Error: ' + response.data);
                }
            });
        }
    });
    
    // Functions
    
    function populateAnswersContainer(questionType) {
        var container = $('#answers-container');
        container.empty();
        
        if (questionType === 'yes_no') {
            container.append(`
                <tr>
                    <th scope="row">Answers</th>
                    <td>
                        <div class="yes-no-answers">
                            <label><input type="radio" name="answers[0][is_correct]" value="1"> Yes</label>
                            <input type="hidden" name="answers[0][content]" value="Yes">
                            <label><input type="radio" name="answers[1][is_correct]" value="1"> No</label>
                            <input type="hidden" name="answers[1][content]" value="No">
                        </div>
                    </td>
                </tr>
            `);
        } else if (questionType === 'text') {
            container.append(`
                <tr>
                    <th scope="row">Answer Instructions</th>
                    <td>
                        <p>No predefined answers needed for text questions.</p>
                    </td>
                </tr>
            `);
        } else {
            // Single choice or multiple choice
            container.append(`
                <tr>
                    <th scope="row">Answers</th>
                    <td>
                        <div id="answers-list">
                            <!-- Answers will be added here -->
                        </div>
                        <button type="button" id="add-answer-btn" class="button-secondary">Add Answer</button>
                    </td>
                </tr>
            `);
            
            // Add initial answer rows
            addAnswerRow(questionType);
            addAnswerRow(questionType);
        }
    }
    
    function populateAnswersForEdit(question) {
        var container = $('#answers-container');
        container.empty();
        
        if (question.type === 'yes_no') {
            container.append(`
                <tr>
                    <th scope="row">Answers</th>
                    <td>
                        <div class="yes-no-answers">
                            <label><input type="radio" name="answers[0][is_correct]" value="1" ${question.answers.length > 0 && question.answers[0].is_correct ? 'checked' : ''}> Yes</label>
                            <label><input type="radio" name="answers[1][is_correct]" value="1" ${question.answers.length > 1 && question.answers[1].is_correct ? 'checked' : ''}> No</label>
                        </div>
                        <input type="hidden" name="answers[0][content]" value="Yes">
                        <input type="hidden" name="answers[1][content]" value="No">
                    </td>
                </tr>
            `);
        } else if (question.type === 'text') {
            container.append(`
                <tr>
                    <th scope="row">Answer Instructions</th>
                    <td>
                        <p>No predefined answers needed for text questions.</p>
                    </td>
                </tr>
            `);
        } else {
            // Single choice or multiple choice
            container.append(`
                <tr>
                    <th scope="row">Answers</th>
                    <td>
                        <div id="answers-list">
                            ${question.answers.map((answer, index) => `
                                <div class="answer-row">
                                    <input type="text" name="answers[${index}][content]" value="${answer.content}" class="answer-input regular-text" placeholder="Answer text">
                                    ${question.type === 'single_choice' ? 
                                        `<input type="radio" name="answers[${index}][is_correct]" value="1" ${answer.is_correct ? 'checked' : ''}> Correct` : 
                                        `<input type="checkbox" name="answers[${index}][is_correct]" value="1" ${answer.is_correct ? 'checked' : ''}> Correct`
                                    }
                                    <input type="number" name="answers[${index}][weight]" value="${answer.weight}" placeholder="Weight" style="width: 60px; margin-left: 10px;">
                                    <button type="button" class="delete-answer-btn">×</button>
                                </div>
                            `).join('')}
                        </div>
                        <button type="button" id="add-answer-btn" class="button-secondary">Add Answer</button>
                    </td>
                </tr>
            `);
        }
    }
    
    function addAnswerRow(questionType) {
        var answersList = $('#answers-list');
        // Find the highest current index to avoid conflicts
        var currentIndex = -1;
        answersList.find('.answer-row input[name*="[content]"]').each(function() {
            var name = $(this).attr('name');
            var matches = name.match(/answers\[([0-9]+)\]\[content\]/);
            if (matches && matches[1]) {
                var idx = parseInt(matches[1]);
                if (idx > currentIndex) {
                    currentIndex = idx;
                }
            }
        });
        
        var nextIndex = currentIndex + 1;
        
        var isCorrectField = '';
        if (questionType === 'single_choice') {
            isCorrectField = '<input type="radio" name="answers[' + nextIndex + '][is_correct]" value="1"> Correct';
        } else if (questionType === 'multiple_choice') {
            isCorrectField = '<input type="checkbox" name="answers[' + nextIndex + '][is_correct]" value="1"> Correct';
        }
        
        var answerRow = `
            <div class="answer-row">
                <input type="text" name="answers[` + nextIndex + `][content]" class="answer-input regular-text" placeholder="Answer text">
                ` + isCorrectField + `
                <input type="number" name="answers[` + nextIndex + `][weight]" value="0" placeholder="Weight" style="width: 60px; margin-left: 10px;">
                <button type="button" class="delete-answer-btn">×</button>
            </div>
        `;
        
        answersList.append(answerRow);
    }
});