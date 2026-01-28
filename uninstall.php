<?php
/**
 * Файл для очистки данных при удалении плагина Quiz Builder
 */

// Если файл вызывается напрямую, завершаем работу
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Удаление всех квизов и вопросов
$quizzes = get_posts(array(
    'post_type' => 'quiz',
    'numberposts' => -1,
    'post_status' => 'any'
));

foreach ($quizzes as $quiz) {
    wp_delete_post($quiz->ID, true);
}

$questions = get_posts(array(
    'post_type' => 'question',
    'numberposts' => -1,
    'post_status' => 'any'
));

foreach ($questions as $question) {
    wp_delete_post($question->ID, true);
}

// Удаление опций плагина (если есть)
// delete_option('quiz_builder_settings');

// Удаление пользовательских capability (если были созданы)
// $role = get_role('administrator');
// if ($role) {
//     $role->remove_cap('manage_quiz_builder');
// }