<?php
/**
 * Plugin Name: Quiz Builder
 * Description: Плагин для создания и управления интерактивными квизами
 * Version: 1.0
 * Author: Your Name
 */

// Запрет прямого доступа
if (!defined('ABSPATH')) {
    exit;
}

// Основной класс плагина
class QuizBuilderPlugin {
    
    public function __construct() {
        // Регистрация хука для инициализации
        add_action('init', array($this, 'init'));
        
        // Регистрация хука для административной части
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Регистрация шорткода
        add_shortcode('quiz', array($this, 'quiz_shortcode'));
        
        // Подключение CSS и JS
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // AJAX обработчики
        add_action('wp_ajax_save_quiz', array($this, 'save_quiz'));
        add_action('wp_ajax_get_quizzes', array($this, 'get_quizzes'));
        add_action('wp_ajax_delete_quiz', array($this, 'delete_quiz'));
        add_action('wp_ajax_save_question', array($this, 'save_question'));
        add_action('wp_ajax_get_questions', array($this, 'get_questions'));
        add_action('wp_ajax_delete_question', array($this, 'delete_question'));
        
        // Создание таблиц в БД при активации
        register_activation_hook(__FILE__, array($this, 'create_tables'));
    }
    
    public function init() {
        // Регистрация типа записи для квизов
        $this->register_quiz_post_type();
        
        // Регистрация типа записи для вопросов
        $this->register_question_post_type();
    }
    
    // Регистрация типа записи для квизов
    private function register_quiz_post_type() {
        $args = array(
            'public' => false,
            'label'  => 'Квизы',
            'supports' => array('title'),
            'show_in_rest' => true,
        );
        register_post_type('quiz', $args);
    }
    
    // Регистрация типа записи для вопросов
    private function register_question_post_type() {
        $args = array(
            'public' => false,
            'label'  => 'Вопросы',
            'supports' => array('title'),
            'show_in_rest' => true,
        );
        register_post_type('question', $args);
    }
    
    // Метод для добавления пунктов меню в админке
    public function add_admin_menu() {
        add_menu_page(
            'Quiz Builder',
            'Квизы',
            'manage_options',
            'quiz-builder',
            array($this, 'admin_dashboard'),
            'dashicons-media-document',
            30
        );
        
        add_submenu_page(
            'quiz-builder',
            'Все квизы',
            'Все квизы',
            'manage_options',
            'quiz-builder',
            array($this, 'admin_dashboard')
        );
        
        add_submenu_page(
            'quiz-builder',
            'Добавить квиз',
            'Добавить квиз',
            'manage_options',
            'add-quiz',
            array($this, 'add_quiz_page')
        );
        
        add_submenu_page(
            'quiz-builder',
            'Банк вопросов',
            'Банк вопросов',
            'manage_options',
            'question-bank',
            array($this, 'question_bank_page')
        );
    }
    
    // Главная страница админки
    public function admin_dashboard() {
        echo '<div class="wrap">';
        echo '<h1>Quiz Builder</h1>';
        echo '<p>Добро пожаловать в Quiz Builder. Здесь вы можете создавать и управлять своими квизами.</p>';
        
        // Вывод списка квизов
        $quizzes = get_posts(array(
            'post_type' => 'quiz',
            'numberposts' => -1,
            'post_status' => 'any'
        ));
        
        echo '<div class="quiz-list">';
        echo '<h2>Ваши квизы</h2>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>ID</th><th>Название</th><th>Дата создания</th><th>Действия</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($quizzes as $quiz) {
            echo '<tr>';
            echo '<td>' . $quiz->ID . '</td>';
            echo '<td><a href="' . admin_url('admin.php?page=add-quiz&id=' . $quiz->ID) . '">' . $quiz->post_title . '</a></td>';
            echo '<td>' . date('d.m.Y H:i', strtotime($quiz->post_date)) . '</td>';
            echo '<td>';
            echo '<a href="' . admin_url('admin.php?page=add-quiz&id=' . $quiz->ID) . '" class="button button-primary">Редактировать</a> ';
            echo '<a href="#" onclick="deleteQuiz(' . $quiz->ID . ')" class="button button-secondary">Удалить</a>';
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
        
        echo '<a href="' . admin_url('admin.php?page=add-quiz') . '" class="button button-primary" style="margin-top: 20px;">Создать новый квиз</a>';
        
        echo '</div>';
        
        // JavaScript для удаления квиза
        ?>
        <script>
        function deleteQuiz(id) {
            if (confirm('Вы уверены, что хотите удалить этот квиз?')) {
                jQuery.post(ajaxurl, {
                    action: 'delete_quiz',
                    quiz_id: id,
                    nonce: '<?php echo wp_create_nonce('delete_quiz_nonce'); ?>'
                }, function(response) {
                    if(response.success) {
                        location.reload();
                    } else {
                        alert('Ошибка при удалении квиза');
                    }
                });
            }
        }
        </script>
        <?php
    }
    
    // Страница добавления/редактирования квиза
    public function add_quiz_page() {
        $quiz_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $quiz_data = null;
        
        if ($quiz_id) {
            $quiz = get_post($quiz_id);
            if ($quiz && $quiz->post_type === 'quiz') {
                $quiz_meta = get_post_meta($quiz_id, '_quiz_data', true);
                $quiz_data = array(
                    'id' => $quiz->ID,
                    'title' => $quiz->post_title,
                    'data' => $quiz_meta ? json_decode($quiz_meta, true) : array()
                );
            }
        }
        
        echo '<div class="wrap">';
        echo '<h1>' . ($quiz_id ? 'Редактировать квиз' : 'Создать новый квиз') . '</h1>';
        
        echo '<div id="quiz-editor">';
        echo '<form id="quiz-form">';
        echo '<table class="form-table">';
        echo '<tr><th><label for="quiz-title">Название квиза</label></th>';
        echo '<td><input type="text" id="quiz-title" name="quiz_title" value="' . ($quiz_data ? esc_attr($quiz_data['title']) : '') . '" class="regular-text" required /></td></tr>';
        echo '</table>';
        
        echo '<h2>Вопросы</h2>';
        echo '<div id="questions-container">';
        // Здесь будут динамически добавляться вопросы
        echo '</div>';
        echo '<button type="button" id="add-question" class="button">Добавить вопрос</button>';
        
        echo '<p class="submit">';
        echo '<input type="hidden" id="quiz-id" name="quiz_id" value="' . $quiz_id . '" />';
        echo '<input type="hidden" id="nonce" name="nonce" value="' . wp_create_nonce('save_quiz_nonce') . '" />';
        echo '<input type="submit" id="save-quiz" class="button button-primary" value="Сохранить квиз" />';
        echo '</p>';
        echo '</form>';
        echo '</div>';
        
        echo '</div>';
        
        // Шаблоны для JavaScript
        ?>
        <script type="text/template" id="question-template">
            <div class="question-item" data-question-id="{{id}}">
                <h3>Вопрос <span class="question-number">{{number}}</span> <button type="button" class="remove-question button">Удалить</button></h3>
                <table class="form-table">
                    <tr>
                        <th><label>Текст вопроса</label></th>
                        <td><textarea name="questions[{{id}}][text]" class="large-text" rows="3">{{text}}</textarea></td>
                    </tr>
                    <tr>
                        <th><label>Категория</label></th>
                        <td>
                            <select name="questions[{{id}}][category]">
                                <option value="">Выберите категорию</option>
                                <option value="I" {{#if_I_selected}}>Исследовательский (I)</option>
                                <option value="R" {{#if_R_selected}}>Реалистичный (R)</option>
                                <option value="A" {{#if_A_selected}}>Артистический (A)</option>
                                <option value="S" {{#if_S_selected}}>Социальный (S)</option>
                                <option value="E" {{#if_E_selected}}>Предпринимательский (E)</option>
                                <option value="C" {{#if_C_selected}}>Конвенциональный (C)</option>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <h4>Ответы</h4>
                <div class="answers-container">
                    {{#answers}}
                    <div class="answer-item">
                        <input type="text" name="questions[{{../id}}][answers][]" value="{{text}}" placeholder="Текст ответа" class="regular-text" />
                        <select name="questions[{{../id}}][answer_categories][]">
                            <option value="">Категория</option>
                            <option value="I" {{#if_I_selected_ans}}>I</option>
                            <option value="R" {{#if_R_selected_ans}}>R</option>
                            <option value="A" {{#if_A_selected_ans}}>A</option>
                            <option value="S" {{#if_S_selected_ans}}>S</option>
                            <option value="E" {{#if_E_selected_ans}}>E</option>
                            <option value="C" {{#if_C_selected_ans}}>C</option>
                        </select>
                        <button type="button" class="remove-answer button">Удалить</button>
                    </div>
                    {{/answers}}
                    {{^answers}}
                    <div class="answer-item">
                        <input type="text" name="questions[{{id}}][answers][]" placeholder="Текст ответа" class="regular-text" />
                        <select name="questions[{{id}}][answer_categories][]">
                            <option value="">Категория</option>
                            <option value="I">I</option>
                            <option value="R">R</option>
                            <option value="A">A</option>
                            <option value="S">S</option>
                            <option value="E">E</option>
                            <option value="C">C</option>
                        </select>
                        <button type="button" class="remove-answer button">Удалить</button>
                    </div>
                    <div class="answer-item">
                        <input type="text" name="questions[{{id}}][answers][]" placeholder="Текст ответа" class="regular-text" />
                        <select name="questions[{{id}}][answer_categories][]">
                            <option value="">Категория</option>
                            <option value="I">I</option>
                            <option value="R">R</option>
                            <option value="A">A</option>
                            <option value="S">S</option>
                            <option value="E">E</option>
                            <option value="C">C</option>
                        </select>
                        <button type="button" class="remove-answer button">Удалить</button>
                    </div>
                    {{/answers}}
                </div>
                <button type="button" class="add-answer button">Добавить ответ</button>
            </div>
        </script>
        <?php
        
        ?>
        <script>
        jQuery(document).ready(function($) {
            let questionCounter = 0;
            
            // Загрузка существующих вопросов если редактируем квиз
            <?php if ($quiz_data && !empty($quiz_data['data']['questions'])): ?>
            const existingQuestions = <?php echo json_encode($quiz_data['data']['questions']); ?>;
            loadExistingQuestions(existingQuestions);
            questionCounter = existingQuestions.length;
            <?php endif; ?>
            
            // Добавление нового вопроса
            $('#add-question').click(function() {
                const questionId = 'q_' + Date.now() + '_' + questionCounter++;
                const template = $('#question-template').html()
                    .replace(/\{\{id\}\}/g, questionId)
                    .replace(/\{\{number\}\}/g, $('.question-item').length + 1)
                    .replace(/\{\{text\}\}/g, '')
                    .replace(/\{\{#answers\}\}(.*?)\{\{\/answers\}\}/gs, '')
                    .replace(/\{\{^answers\}\}(.*?)\{\{\/answers\}\}/gs, '$1')
                    .replace(/\{\{..\//g, '{{')
                    .replace(/selected/g, '');
                
                $('#questions-container').append(template);
            });
            
            // Добавление ответа к вопросу
            $(document).on('click', '.add-answer', function() {
                const questionDiv = $(this).closest('.question-item');
                const questionId = questionDiv.data('question-id');
                
                const answerTemplate = `
                    <div class="answer-item">
                        <input type="text" name="questions[${questionId}][answers][]" placeholder="Текст ответа" class="regular-text" />
                        <select name="questions[${questionId}][answer_categories][]">
                            <option value="">Категория</option>
                            <option value="I">I</option>
                            <option value="R">R</option>
                            <option value="A">A</option>
                            <option value="S">S</option>
                            <option value="E">E</option>
                            <option value="C">C</option>
                        </select>
                        <button type="button" class="remove-answer button">Удалить</button>
                    </div>
                `;
                
                questionDiv.find('.answers-container').append(answerTemplate);
            });
            
            // Удаление ответа
            $(document).on('click', '.remove-answer', function() {
                $(this).closest('.answer-item').remove();
            });
            
            // Удаление вопроса
            $(document).on('click', '.remove-question', function() {
                $(this).closest('.question-item').remove();
                // Обновляем номера вопросов
                $('.question-number').each(function(index) {
                    $(this).text(index + 1);
                });
            });
            
            // Сохранение квиза
            $('#quiz-form').submit(function(e) {
                e.preventDefault();
                
                const formData = $(this).serialize();
                
                $.post(ajaxurl, {
                    action: 'save_quiz',
                    data: formData
                }, function(response) {
                    if(response.success) {
                        alert('Квиз успешно сохранен!');
                        window.location.href = 'admin.php?page=quiz-builder';
                    } else {
                        alert('Ошибка при сохранении квиза: ' + response.data);
                    }
                });
            });
            
            function loadExistingQuestions(questions) {
                questions.forEach(function(question, index) {
                    const questionId = question.id || ('q_' + Date.now() + '_' + index);
                    let answersHtml = '';
                    
                    if (question.answers && question.answers.length > 0) {
                        question.answers.forEach(function(answer) {
                            let selectedCat = answer.category ? ' selected' : '';
                            answersHtml += `
                                <div class="answer-item">
                                    <input type="text" name="questions[${questionId}][answers][]" value="${answer.text || ''}" placeholder="Текст ответа" class="regular-text" />
                                    <select name="questions[${questionId}][answer_categories][]">
                                        <option value="">Категория</option>
                                        <option value="I"${answer.category === 'I' ? ' selected' : ''}>I</option>
                                        <option value="R"${answer.category === 'R' ? ' selected' : ''}>R</option>
                                        <option value="A"${answer.category === 'A' ? ' selected' : ''}>A</option>
                                        <option value="S"${answer.category === 'S' ? ' selected' : ''}>S</option>
                                        <option value="E"${answer.category === 'E' ? ' selected' : ''}>E</option>
                                        <option value="C"${answer.category === 'C' ? ' selected' : ''}>C</option>
                                    </select>
                                    <button type="button" class="remove-answer button">Удалить</button>
                                </div>
                            `;
                        });
                    } else {
                        answersHtml = `
                            <div class="answer-item">
                                <input type="text" name="questions[${questionId}][answers][]" placeholder="Текст ответа" class="regular-text" />
                                <select name="questions[${questionId}][answer_categories][]">
                                    <option value="">Категория</option>
                                    <option value="I">I</option>
                                    <option value="R">R</option>
                                    <option value="A">A</option>
                                    <option value="S">S</option>
                                    <option value="E">E</option>
                                    <option value="C">C</option>
                                </select>
                                <button type="button" class="remove-answer button">Удалить</button>
                            </div>
                            <div class="answer-item">
                                <input type="text" name="questions[${questionId}][answers][]" placeholder="Текст ответа" class="regular-text" />
                                <select name="questions[${questionId}][answer_categories][]">
                                    <option value="">Категория</option>
                                    <option value="I">I</option>
                                    <option value="R">R</option>
                                    <option value="A">A</option>
                                    <option value="S">S</option>
                                    <option value="E">E</option>
                                    <option value="C">C</option>
                                </select>
                                <button type="button" class="remove-answer button">Удалить</button>
                            </div>
                        `;
                    }
                    
                    let selectedCat = question.category ? ' selected' : '';
                    const questionTemplate = $('#question-template').html()
                        .replace(/\{\{id\}\}/g, questionId)
                        .replace(/\{\{number\}\}/g, index + 1)
                        .replace(/\{\{text\}\}/g, question.text || '')
                        .replace(/\{\{#if_I_selected\}\}/g, question.category === 'I' ? 'selected' : '')
                        .replace(/\{\{#if_R_selected\}\}/g, question.category === 'R' ? 'selected' : '')
                        .replace(/\{\{#if_A_selected\}\}/g, question.category === 'A' ? 'selected' : '')
                        .replace(/\{\{#if_S_selected\}\}/g, question.category === 'S' ? 'selected' : '')
                        .replace(/\{\{#if_E_selected\}\}/g, question.category === 'E' ? 'selected' : '')
                        .replace(/\{\{#if_C_selected\}\}/g, question.category === 'C' ? 'selected' : '')
                        .replace(/\{\{#answers\}\}(.*?)\{\{\/answers\}\}/gs, answersHtml)
                        .replace(/\{\{^answers\}\}(.*?)\{\{\/answers\}\}/gs, '')
                        .replace(/\{\{..\//g, '{{')
                        .replace(/\{\{#if_I_selected_ans\}\}/g, '')
                        .replace(/\{\{#if_R_selected_ans\}\}/g, '')
                        .replace(/\{\{#if_A_selected_ans\}\}/g, '')
                        .replace(/\{\{#if_S_selected_ans\}\}/g, '')
                        .replace(/\{\{#if_E_selected_ans\}\}/g, '')
                        .replace(/\{\{#if_C_selected_ans\}\}/g, '');
                    
                    $('#questions-container').append(questionTemplate);
                });
            }
        });
        </script>
        <?php
    }
    
    // Страница банка вопросов
    public function question_bank_page() {
        echo '<div class="wrap">';
        echo '<h1>Банк вопросов</h1>';
        
        // Форма добавления вопроса
        echo '<div id="add-question-section">';
        echo '<h2>Добавить новый вопрос</h2>';
        echo '<form id="add-question-form">';
        echo '<table class="form-table">';
        echo '<tr><th><label for="question-text">Текст вопроса</label></th>';
        echo '<td><textarea id="question-text" name="question_text" class="large-text" rows="3" required></textarea></td></tr>';
        echo '<tr><th><label for="question-category">Категория</label></th>';
        echo '<td><select id="question-category" name="question_category">';
        echo '<option value="">Выберите категорию</option>';
        echo '<option value="I">Исследовательский (I)</option>';
        echo '<option value="R">Реалистичный (R)</option>';
        echo '<option value="A">Артистический (A)</option>';
        echo '<option value="S">Социальный (S)</option>';
        echo '<option value="E">Предпринимательский (E)</option>';
        echo '<option value="C">Конвенциональный (C)</option>';
        echo '</select></td></tr>';
        echo '</table>';
        echo '<h3>Ответы</h3>';
        echo '<div id="answers-list">';
        echo '<div class="answer-item">';
        echo '<input type="text" name="answers[]" placeholder="Текст ответа" class="regular-text" required /> ';
        echo '<select name="answer_categories[]">';
        echo '<option value="">Категория</option>';
        echo '<option value="I">I</option>';
        echo '<option value="R">R</option>';
        echo '<option value="A">A</option>';
        echo '<option value="S">S</option>';
        echo '<option value="E">E</option>';
        echo '<option value="C">C</option>';
        echo '</select> ';
        echo '<button type="button" class="remove-answer button">Удалить</button>';
        echo '</div>';
        echo '<div class="answer-item">';
        echo '<input type="text" name="answers[]" placeholder="Текст ответа" class="regular-text" required /> ';
        echo '<select name="answer_categories[]">';
        echo '<option value="">Категория</option>';
        echo '<option value="I">I</option>';
        echo '<option value="R">R</option>';
        echo '<option value="A">A</option>';
        echo '<option value="S">S</option>';
        echo '<option value="E">E</option>';
        echo '<option value="C">C</option>';
        echo '</select> ';
        echo '<button type="button" class="remove-answer button">Удалить</button>';
        echo '</div>';
        echo '</div>';
        echo '<button type="button" id="add-answer-btn" class="button">Добавить ответ</button>';
        echo '<p class="submit">';
        echo '<input type="hidden" name="nonce" value="' . wp_create_nonce('save_question_nonce') . '" />';
        echo '<input type="submit" id="save-question-btn" class="button button-primary" value="Сохранить вопрос" />';
        echo '</p>';
        echo '</form>';
        echo '</div>';
        
        // Список существующих вопросов
        echo '<div id="questions-list-section" style="margin-top: 40px;">';
        echo '<h2>Существующие вопросы</h2>';
        $questions = get_posts(array(
            'post_type' => 'question',
            'numberposts' => -1,
            'post_status' => 'any'
        ));
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>ID</th><th>Текст вопроса</th><th>Категория</th><th>Дата создания</th><th>Действия</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($questions as $question) {
            $question_meta = get_post_meta($question->ID, '_question_data', true);
            $question_data = $question_meta ? json_decode($question_meta, true) : array();
            $category = isset($question_data['category']) ? $question_data['category'] : '';
            
            echo '<tr>';
            echo '<td>' . $question->ID . '</td>';
            echo '<td>' . esc_html($question->post_title) . '</td>';
            echo '<td>' . esc_html($category) . '</td>';
            echo '<td>' . date('d.m.Y H:i', strtotime($question->post_date)) . '</td>';
            echo '<td>';
            echo '<a href="#" onclick="useQuestion(' . $question->ID . ')" class="button button-small">Использовать в квизе</a> ';
            echo '<a href="#" onclick="deleteQuestion(' . $question->ID . ')" class="button button-secondary button-small">Удалить</a>';
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
        
        echo '</div>';
        
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Добавление нового ответа
            $('#add-answer-btn').click(function() {
                const answerTemplate = `
                    <div class="answer-item">
                        <input type="text" name="answers[]" placeholder="Текст ответа" class="regular-text" required />
                        <select name="answer_categories[]">
                            <option value="">Категория</option>
                            <option value="I">I</option>
                            <option value="R">R</option>
                            <option value="A">A</option>
                            <option value="S">S</option>
                            <option value="E">E</option>
                            <option value="C">C</option>
                        </select>
                        <button type="button" class="remove-answer button">Удалить</button>
                    </div>
                `;
                $('#answers-list').append(answerTemplate);
            });
            
            // Удаление ответа
            $(document).on('click', '.remove-answer', function() {
                if($('.answer-item').length > 1) {
                    $(this).closest('.answer-item').remove();
                } else {
                    alert('Должен быть хотя бы один ответ');
                }
            });
            
            // Сохранение вопроса
            $('#add-question-form').submit(function(e) {
                e.preventDefault();
                
                const formData = $(this).serialize();
                
                $.post(ajaxurl, {
                    action: 'save_question',
                    data: formData
                }, function(response) {
                    if(response.success) {
                        alert('Вопрос успешно сохранен!');
                        location.reload();
                    } else {
                        alert('Ошибка при сохранении вопроса: ' + response.data);
                    }
                });
            });
        });
        
        function useQuestion(questionId) {
            // Здесь будет логика добавления вопроса в текущий квиз
            alert('Вопрос ID ' + questionId + ' будет добавлен в текущий квиз');
            // На практике, здесь нужно будет отправить вопрос в редактор квиза
        }
        
        function deleteQuestion(id) {
            if (confirm('Вы уверены, что хотите удалить этот вопрос?')) {
                jQuery.post(ajaxurl, {
                    action: 'delete_question',
                    question_id: id,
                    nonce: '<?php echo wp_create_nonce('delete_question_nonce'); ?>'
                }, function(response) {
                    if(response.success) {
                        location.reload();
                    } else {
                        alert('Ошибка при удалении вопроса');
                    }
                });
            }
        }
        </script>
        <?php
    }
    
    // Метод для обработки шорткода [quiz id="123"]
    public function quiz_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
        ), $atts);
        
        $quiz_id = intval($atts['id']);
        
        if (!$quiz_id) {
            return '<p>Не указан ID квиза</p>';
        }
        
        $quiz = get_post($quiz_id);
        if (!$quiz || $quiz->post_type !== 'quiz') {
            return '<p>Квиз не найден</p>';
        }
        
        $quiz_meta = get_post_meta($quiz_id, '_quiz_data', true);
        $quiz_data = $quiz_meta ? json_decode($quiz_meta, true) : array();
        
        ob_start();
        ?>
        <div id="quizApp-<?php echo $quiz_id; ?>" class="quiz-app-container">
            <!-- Контент будет сгенерирован JS -->
        </div>
        
        <script>
        (function() {
            const quizData = <?php echo json_encode($quiz_data); ?>;
            const quizInstanceId = <?php echo $quiz_id; ?>;
            
            if (quizData && quizData.questions && quizData.questions.length > 0) {
                // Инициализация квиза
                window['quizInstance_' + quizInstanceId] = new CareerQuiz(
                    quizInstanceId,
                    quizData.questions
                );
            }
        })();
        
        class CareerQuiz {
            constructor(instanceId, questions) {
                this.instanceId = instanceId;
                this.questions = questions;
                this.answers = JSON.parse(localStorage.getItem('careerQuizAnswers_' + this.instanceId)) || {};
                this.currentIndex = this._getFirstUnansweredIndex();
                this.init();
            }
            
            init() {
                this._renderUI();
                this._renderQuestion();
            }
            
            _renderUI() {
                const container = document.getElementById('quizApp-' + this.instanceId);
                container.innerHTML = `
                    <div class="quiz-header">
                        <div class="progress-bar">
                            <div class="progress-fill" id="progressFill-${this.instanceId}"></div>
                        </div>
                        <div class="question-counter" id="questionCounter-${this.instanceId}">Вопрос 1 из ${this.questions.length}</div>
                    </div>
                    <div id="questionContainer-${this.instanceId}"></div>
                `;
            }
            
            _renderQuestion() {
                const q = this.questions[this.currentIndex];
                if (!q) {
                    this._generateReport();
                    return;
                }
                
                const progressPercent = (this.currentIndex / this.questions.length) * 100;
                document.getElementById('progressFill-' + this.instanceId).style.width = `${progressPercent}%`;
                document.getElementById('questionCounter-' + this.instanceId).textContent = `Вопрос ${this.currentIndex + 1} из ${this.questions.length}`;
                
                let optionsHtml = '';
                if (q.answers && q.answers.length > 0) {
                    q.answers.forEach((opt, idx) => {
                        optionsHtml += `
                            <label>
                                <input type="radio" name="answer-${this.instanceId}" value="${idx}" onchange="window['quizInstance_${this.instanceId}']._handleAnswer(${idx})">
                                ${opt.text}
                            </label>
                        `;
                    });
                }
                
                document.getElementById('questionContainer-' + this.instanceId).innerHTML = `<h2>${q.text}</h2>${optionsHtml}`;
            }
            
            _handleAnswer(optionIndex) {
                const q = this.questions[this.currentIndex];
                this.answers[q.id] = optionIndex;
                localStorage.setItem('careerQuizAnswers_' + this.instanceId, JSON.stringify(this.answers));
                this.currentIndex++;
                setTimeout(() => this._renderQuestion(), 300);
            }
            
            _getFirstUnansweredIndex() {
                for (let i = 0; i < this.questions.length; i++) {
                    if (this.answers[this.questions[i].id] === undefined) {
                        return i;
                    }
                }
                return this.questions.length;
            }
            
            _calculateProfile() {
                const scores = { I: 0, R: 0, A: 0, S: 0, E: 0, C: 0 };
                
                this.questions.forEach(q => {
                    const answerIdx = this.answers[q.id];
                    if (answerIdx === undefined) return;
                    
                    if (q.answers && q.answers[answerIdx]) {
                        const opt = q.answers[answerIdx];
                        if (opt.category) {
                            const cat = opt.category;
                            if (scores.hasOwnProperty(cat)) scores[cat] += 1;
                        }
                    }
                });
                
                const total = this.questions.filter(q => this.answers[q.id] !== undefined).length || this.questions.length;
                const normalized = {};
                for (const cat in scores) {
                    normalized[cat] = total > 0 ? Math.round((scores[cat] / total) * 100) : 0;
                }
                
                // Расчет профиля на основе двух самых высоких категорий
                const cats = Object.keys(scores).filter(cat => scores[cat] > 0);
                cats.sort((a, b) => scores[b] - scores[a]);
                
                let key = '';
                if (cats.length >= 2) {
                    key = [cats[0], cats[1]].sort().join(',');
                } else if (cats.length === 1) {
                    key = cats[0] + ',' + cats[0]; // временное решение для одной категории
                }
                
                // Возвращаем результаты
                const fullNameMap = { 
                    I: 'Исследовательский', 
                    R: 'Реалистичный', 
                    A: 'Артистический', 
                    S: 'Социальный', 
                    E: 'Предпринимательский', 
                    C: 'Конвенциональный' 
                };
                
                const strengths = Object.keys(scores).map(cat => ({
                    key: cat,
                    name: fullNameMap[cat],
                    score: normalized[cat]
                })).sort((a, b) => b.score - a.score);
                
                // Проверяем наличие данных о типах профилей в quizData
                const profileData = typeof quizData !== 'undefined' && quizData.profiles ? quizData.profiles[key] : null;
                
                // Временные рекомендации, если данные отсутствуют
                let recommendations = {
                    education: ['Пример образовательного направления'],
                    career: ['Пример профессии'],
                    soulAreas: ['Пример сферы для души'],
                    strengthen: ['Пример развития сильных сторон'],
                    develop: ['Пример проработки слабых сторон']
                };
                
                // Если есть данные о профиле, используем их
                if (profileData) {
                    recommendations = {
                        education: profileData.education || [],
                        career: profileData.career || [],
                        soulAreas: profileData.soulAreas || [],
                        strengthen: profileData.strengthen || [],
                        develop: profileData.develop || []
                    };
                }
                
                return { 
                    strengths, 
                    typeName: profileData ? profileData.name : 'Ваш тип профиля', 
                    recommendations 
                };
            }
            
            _buildReportHTML(profile) {
                const { strengths, typeName, recommendations } = profile;
                
                const bars = strengths.map(s => `
                    <div class="category-bar">
                        <div class="category-label">${s.name}</div>
                        <div class="bar-container">
                            <div class="bar-fill bar-${s.key}" style="width: ${s.score}%"></div>
                        </div>
                    </div>
                `).join('');
                
                const eduItems = recommendations.education.map(i => `<li>${i}</li>`).join('');
                const careerItems = recommendations.career.map(i => `<li>${i}</li>`).join('');
                const soulItems = recommendations.soulAreas.map(i => `<li>${i}</li>`).join('');
                const strengthenItems = recommendations.strengthen.map(i => `<li>${i}</li>`).join('');
                const developItems = recommendations.develop.map(i => `<li>${i}</li>`).join('');
                
                return `
                    <div class="career-report">
                        <h1>Ваш результат</h1>
                        <h2>Ваши сильные стороны</h2>
                        <div id="riasecBars">${bars}</div>
                        <h2>Вы — ${typeName}</h2>
                        <h3>Образование</h3>
                        <ul>${eduItems}</ul>
                        <h3>Профессии</h3>
                        <ul>${careerItems}</ul>
                        <h3>Сферы для души</h3>
                        <ul>${soulItems}</ul>
                        <h3>Как усилить ваши сильные стороны</h3>
                        <ul>${strengthenItems}</ul>
                        <h3>Как (если хотите) мягко проработать слабые стороны</h3>
                        <ul>${developItems}</ul>
                        <button id="restartQuiz-${this.instanceId}" style="margin-top: 30px; padding: 12px 24px; font-size: 16px; background: #000; color: #fff; border: none; border-radius: 6px; cursor: pointer;">Пройти заново</button>
                    </div>
                `;
            }
            
            _generateReport() {
                const profile = this._calculateProfile();
                const container = document.getElementById('quizApp-' + this.instanceId);
                container.innerHTML = this._buildReportHTML(profile);
                
                document.getElementById('restartQuiz-' + this.instanceId).addEventListener('click', () => {
                    localStorage.removeItem('careerQuizAnswers_' + this.instanceId);
                    this.answers = {};
                    this.currentIndex = 0;
                    this.init();
                });
            }
        }
        </script>
        <?php
        return ob_get_clean();
    }
    
    // Подключение CSS и JS для фронтенда
    public function enqueue_frontend_assets() {
        wp_enqueue_style('quiz-frontend-style', plugin_dir_url(__FILE__) . 'assets/css/frontend.css', array(), '1.0');
        wp_enqueue_script('quiz-frontend-script', plugin_dir_url(__FILE__) . 'assets/js/frontend.js', array('jquery'), '1.0', true);
    }
    
    // Подключение CSS и JS для админки
    public function enqueue_admin_assets() {
        global $pagenow, $plugin_page;
        
        if (($pagenow === 'admin.php' && in_array($plugin_page, array('quiz-builder', 'add-quiz', 'question-bank')))) {
            wp_enqueue_style('quiz-admin-style', plugin_dir_url(__FILE__) . 'assets/css/admin.css', array(), '1.0');
            wp_enqueue_script('quiz-admin-script', plugin_dir_url(__FILE__) . 'assets/js/admin.js', array('jquery'), '1.0', true);
            wp_localize_script('quiz-admin-script', 'ajax_object', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('quiz_nonce')
            ));
        }
    }
    
    // Создание таблиц в базе данных
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Таблица для дополнительных данных квизов
        $quiz_table = $wpdb->prefix . 'quiz_builder_quizzes';
        $sql = "CREATE TABLE $quiz_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            quiz_id bigint(20) NOT NULL,
            data longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY quiz_id (quiz_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Таблица для вопросов
        $question_table = $wpdb->prefix . 'quiz_builder_questions';
        $sql = "CREATE TABLE $question_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            question_id bigint(20) NOT NULL,
            data longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY question_id (question_id)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    // AJAX обработчик для сохранения квиза
    public function save_quiz() {
        // Проверка прав и nonce
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['nonce'], 'save_quiz_nonce')) {
            wp_die('Недостаточно прав');
        }
        
        // Разбор данных формы
        parse_str($_POST['data'], $form_data);
        
        $quiz_id = intval($form_data['quiz_id']);
        $quiz_title = sanitize_text_field($form_data['quiz_title']);
        $questions_data = array();
        
        if (isset($form_data['questions']) && is_array($form_data['questions'])) {
            foreach ($form_data['questions'] as $question_id => $question_data) {
                $question = array(
                    'id' => sanitize_text_field($question_id),
                    'text' => sanitize_textarea_field($question_data['text']),
                    'category' => sanitize_text_field($question_data['category']),
                    'answers' => array()
                );
                
                if (isset($question_data['answers']) && is_array($question_data['answers'])) {
                    foreach ($question_data['answers'] as $index => $answer_text) {
                        $answer = array(
                            'text' => sanitize_textarea_field($answer_text),
                            'category' => sanitize_text_field($question_data['answer_categories'][$index])
                        );
                        $question['answers'][] = $answer;
                    }
                }
                
                $questions_data[] = $question;
            }
        }
        
        $quiz_data = array(
            'title' => $quiz_title,
            'questions' => $questions_data
        );
        
        if ($quiz_id) {
            // Обновление существующего квиза
            $post = array(
                'ID' => $quiz_id,
                'post_title' => $quiz_title,
                'post_type' => 'quiz',
                'post_status' => 'publish'
            );
            
            $result = wp_update_post($post);
            if (is_wp_error($result)) {
                wp_send_json_error('Ошибка при обновлении квиза: ' . $result->get_error_message());
            }
        } else {
            // Создание нового квиза
            $post = array(
                'post_title' => $quiz_title,
                'post_type' => 'quiz',
                'post_status' => 'publish'
            );
            
            $quiz_id = wp_insert_post($post);
            if (is_wp_error($quiz_id)) {
                wp_send_json_error('Ошибка при создании квиза: ' . $quiz_id->get_error_message());
            }
        }
        
        // Сохранение данных квиза в мета-поля
        update_post_meta($quiz_id, '_quiz_data', json_encode($quiz_data));
        
        wp_send_json_success('Квиз успешно сохранен');
    }
    
    // AJAX обработчик для получения квизов
    public function get_quizzes() {
        if (!current_user_can('manage_options')) {
            wp_die('Недостаточно прав');
        }
        
        $quizzes = get_posts(array(
            'post_type' => 'quiz',
            'numberposts' => -1,
            'post_status' => 'any'
        ));
        
        $quiz_list = array();
        foreach ($quizzes as $quiz) {
            $quiz_meta = get_post_meta($quiz->ID, '_quiz_data', true);
            $quiz_list[] = array(
                'id' => $quiz->ID,
                'title' => $quiz->post_title,
                'data' => $quiz_meta ? json_decode($quiz_meta, true) : array()
            );
        }
        
        wp_send_json_success($quiz_list);
    }
    
    // AJAX обработчик для удаления квиза
    public function delete_quiz() {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['nonce'], 'delete_quiz_nonce')) {
            wp_die('Недостаточно прав');
        }
        
        $quiz_id = intval($_POST['quiz_id']);
        
        if ($quiz_id) {
            wp_delete_post($quiz_id, true); // Удалить полностью, не в корзину
            wp_send_json_success('Квиз удален');
        } else {
            wp_send_json_error('Неверный ID квиза');
        }
    }
    
    // AJAX обработчик для сохранения вопроса
    public function save_question() {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['nonce'], 'save_question_nonce')) {
            wp_die('Недостаточно прав');
        }
        
        $question_text = sanitize_textarea_field($_POST['question_text']);
        $question_category = sanitize_text_field($_POST['question_category']);
        $answers = array();
        
        if (isset($_POST['answers']) && is_array($_POST['answers'])) {
            foreach ($_POST['answers'] as $index => $answer_text) {
                $answer = array(
                    'text' => sanitize_textarea_field($answer_text),
                    'category' => sanitize_text_field($_POST['answer_categories'][$index])
                );
                if (!empty($answer['text'])) {
                    $answers[] = $answer;
                }
            }
        }
        
        $question_data = array(
            'text' => $question_text,
            'category' => $question_category,
            'answers' => $answers
        );
        
        // Создание нового вопроса как custom post type
        $post = array(
            'post_title' => $question_text,
            'post_content' => '', // Можно использовать для дополнительных данных
            'post_type' => 'question',
            'post_status' => 'publish'
        );
        
        $question_id = wp_insert_post($post);
        if (is_wp_error($question_id)) {
            wp_send_json_error('Ошибка при создании вопроса: ' . $question_id->get_error_message());
        }
        
        // Сохранение данных вопроса в мета-поля
        update_post_meta($question_id, '_question_data', json_encode($question_data));
        
        wp_send_json_success('Вопрос успешно сохранен');
    }
    
    // AJAX обработчик для получения вопросов
    public function get_questions() {
        if (!current_user_can('manage_options')) {
            wp_die('Недостаточно прав');
        }
        
        $questions = get_posts(array(
            'post_type' => 'question',
            'numberposts' => -1,
            'post_status' => 'any'
        ));
        
        $question_list = array();
        foreach ($questions as $question) {
            $question_meta = get_post_meta($question->ID, '_question_data', true);
            $question_list[] = array(
                'id' => $question->ID,
                'title' => $question->post_title,
                'data' => $question_meta ? json_decode($question_meta, true) : array()
            );
        }
        
        wp_send_json_success($question_list);
    }
    
    // AJAX обработчик для удаления вопроса
    public function delete_question() {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['nonce'], 'delete_question_nonce')) {
            wp_die('Недостаточно прав');
        }
        
        $question_id = intval($_POST['question_id']);
        
        if ($question_id) {
            wp_delete_post($question_id, true); // Удалить полностью, не в корзину
            wp_send_json_success('Вопрос удален');
        } else {
            wp_send_json_error('Неверный ID вопроса');
        }
    }
}

// Инициализация плагина
new QuizBuilderPlugin();

// Подключение интеграции с Oxygen Builder, если плагин активен
if (defined('CT_VERSION')) {
    include_once plugin_dir_path(__FILE__) . 'oxygen-integration.php';
}