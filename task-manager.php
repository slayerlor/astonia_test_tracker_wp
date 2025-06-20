<?php

/**
 * Plugin Name: Таск трекер тестовое
 * Description: Таск менеджер для тестового задания.
 * Version: 1.0.3
 * Author: Даниил
 * Text Domain: task-manager
 * Domain Path: /languages
 * Requires PHP: 7.4
 */

defined('ABSPATH') || exit;

$required_classes = [
    'interfaces/interface-task-repository.php',
    'class-task-manager.php',
    'class-task-repository.php',
    'class-task-ajax.php',
    'class-task-factory.php',
];

foreach ($required_classes as $file) {
    $path = __DIR__ . '/includes/' . $file;
    if (file_exists($path)) {
        require_once $path;
    } else {
        $error_msg = "Файл не найден - $path";
        wp_die($error_msg);
    }
}

if (!class_exists('Task_Manager')) {
    $error_msg = 'Класс таск трекера не был найден';
    wp_die($error_msg);
}

add_action('plugins_loaded', function () {
    try {
        if (!method_exists('Task_Manager', 'init')) {
            throw new Exception('Инит не найден');
        }

        Task_Manager::get_instance()->init();
    } catch (Exception $e) {
        $error_msg = 'Ошибка инита таск трекера: ' . $e->getMessage();

        add_action('admin_notices', function () use ($error_msg) {
            echo '<div class="notice notice-error is-dismissible"><p>';
            echo esc_html($error_msg);
            echo '</p></div>';
        });
    }
});

register_activation_hook(__FILE__, function () {});

register_deactivation_hook(__FILE__, function () {
    delete_transient('cached_tasks');
});
