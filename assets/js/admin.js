// Админ JavaScript для квиза
jQuery(document).ready(function($) {
    // Здесь будет код для работы с квизом в админке
    console.log('Quiz admin panel loaded');
    
    // Функция для загрузки существующих вопросов
    function loadQuestions() {
        $.post(ajax_object.ajax_url, {
            action: 'get_questions',
            nonce: ajax_object.nonce
        }, function(response) {
            if(response.success) {
                console.log('Questions loaded:', response.data);
            }
        });
    }
    
    // Вызов функции загрузки вопросов при загрузке страницы
    if (typeof get_questions !== 'undefined') {
        loadQuestions();
    }
});