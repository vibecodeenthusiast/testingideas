<?php
/**
 * Интеграция с Oxygen Builder для предварительного просмотра шорткодов квиза
 */

// Проверяем, активирован ли Oxygen Builder
if (!defined('CT_VERSION')) {
    return;
}

// Функция для обработки шорткода в Oxygen Builder
function quiz_builder_oxygen_shortcode_handler($shortcode, $output, $atts, $original_content) {
    // Проверяем, является ли шорткод шорткодом нашего квиза
    if (strpos($shortcode, '[quiz') !== false) {
        // Получаем ID квиза из атрибутов
        $atts = shortcode_atts(array(
            'id' => 0,
        ), $atts);
        
        $quiz_id = intval($atts['id']);
        
        if ($quiz_id) {
            // Получаем информацию о квизе для отображения в редакторе
            $quiz = get_post($quiz_id);
            if ($quiz && $quiz->post_type === 'quiz') {
                // Для предварительного просмотра в Oxygen Builder
                return '<div class="oxygen-quiz-placeholder">[Квиз: ' . $quiz->post_title . ']</div>';
            } else {
                return '<div class="oxygen-quiz-placeholder">[Квиз не найден]</div>';
            }
        } else {
            return '<div class="oxygen-quiz-placeholder">[Шорткод квиза - укажите ID]</div>';
        }
    }
    
    return $output;
}

// Регистрируем обработчик шорткода для Oxygen
add_filter('ct_shortcode_return', 'quiz_builder_oxygen_shortcode_handler', 10, 4);

// Функция для добавления квизов в список элементов Oxygen (если потребуется)
function quiz_builder_add_to_oxygen_elements() {
    // Этот код добавит элемент квиза в панель элементов Oxygen, если потребуется
}

// Дополнительная интеграция с Oxygen Builder
function quiz_builder_oxygen_integration() {
    // Добавляем CSS для предварительного просмотра в редакторе
    function add_oxygen_quiz_styles() {
        if (isset($_GET['ct_builder']) || defined('OXYGEN_IFRAME')) {
            echo '<style>
                .oxygen-quiz-placeholder {
                    border: 2px dashed #ccc;
                    padding: 20px;
                    text-align: center;
                    background-color: #f9f9f9;
                    color: #666;
                    font-style: italic;
                    min-height: 100px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
            </style>';
        }
    }
    
    add_action('wp_head', 'add_oxygen_quiz_styles');
    add_action('admin_head', 'add_oxygen_quiz_styles');
}

// Запускаем интеграцию
add_action('init', 'quiz_builder_oxygen_integration');