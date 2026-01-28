jQuery(document).ready(function($) {
    // Admin panel JavaScript functionality
    
    // Color picker initialization
    $('.qmp-color-picker').wpColorPicker();
    
    // Handle adding new questions
    $('#qmp-add-question').on('click', function(e) {
        e.preventDefault();
        addNewQuestionField();
    });
    
    // Handle question type changes
    $(document).on('change', '.qmp-question-type', function() {
        var questionType = $(this).val();
        var questionRow = $(this).closest('.qmp-question-item');
        updateQuestionOptions(questionRow, questionType);
    });
    
    function addNewQuestionField() {
        console.log('Adding new question field');
        // Implementation for adding new question fields
    }
    
    function updateQuestionOptions(questionRow, questionType) {
        console.log('Updating options for question type:', questionType);
        // Implementation for showing appropriate options based on question type
    }
    
    // Toggle advanced settings
    $('.qmp-toggle-advanced').on('click', function(e) {
        e.preventDefault();
        $(this).next('.qmp-advanced-settings').slideToggle();
    });
});