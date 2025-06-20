<?php

class Task_Repository implements Task_Repository_Interface
{
    private $task_fields = [
        'description' => '_task_description',
        'due_date' => '_task_due_date',
        'due_date_ts' => '_task_due_date_ts', // Для сортировки
        'status' => '_task_status'
    ];

    public function get_task(int $post_id): array
    {
        $task = [];

        $post = get_post($post_id);

        if (!$post || $post->post_type !== 'task') {
            return [];
        }

        $task = [
            'id' => $post_id,
            'title' => $post->post_title,
            'created_at' => $post->post_date,
        ];

        foreach ($this->task_fields as $field => $meta_key) {
            $task[$field] = get_post_meta($post_id, $meta_key, true);
        }

        if (!empty($task['due_date_ts'])) {
            $task['formatted_due_date'] = date_i18n(
                get_option('date_format') . ' ' . get_option('time_format'),
                $task['due_date_ts']
            );
        }

        return $task;
    }

    public function update_task(array $task_data): bool
    {

        $post_id = $task_data['id'];

        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'task') {
            return false;
        }

        $post_args = [
            'ID' => $post_id,
        ];

        if (!empty($task_data['title'])) {
            $post_args['post_title'] = sanitize_text_field($task_data['title']);
        }

        if (count($post_args) > 1) {
            wp_update_post($post_args);
        }

        foreach ($this->task_fields as $field => $meta_key) {
            if (array_key_exists($field, $task_data)) {
                if ($field === 'due_date') {
                    $timestamp = !empty($task_data['due_date'])
                        ? strtotime($task_data['due_date'])
                        : 0;

                    update_post_meta($post_id, '_task_due_date_ts', $timestamp);
                    update_post_meta($post_id, $meta_key, Task_Repository::sanitize_field('due_date', $task_data['due_date']));
                } else {
                    $value = is_string($task_data[$field])
                        ? sanitize_text_field($task_data[$field])
                        : $task_data[$field];

                    update_post_meta($post_id, $meta_key, $value);
                }
            }
        }

        wp_cache_delete("task_{$post_id}", 'tasks');

        return true;
    }

    public function get_tasks(): array
    {
        $cached = get_transient('cached_tasks');
        if ($cached !== false) {
            return $cached;
        }

        $tasks = get_posts([
            'post_type' => 'task',
            'posts_per_page' => -1,
            'meta_key' => $this->task_fields['due_date_ts'],
            'orderby' => 'meta_value_num',
            'order' => 'ASC',
            'update_post_meta_cache' => true,
            'update_post_term_cache' => false
        ]);

        $result = array_map(function ($task) {
            return [
                'id' => $task->ID,
                'title' => $task->post_title,
                'description' => get_post_meta($task->ID, $this->task_fields['description'], true),
                'due_date' => get_post_meta($task->ID, $this->task_fields['due_date'], true),
                'status' => get_post_meta($task->ID, $this->task_fields['status'], true),
            ];
        }, $tasks);

        set_transient('cached_tasks', $result, HOUR_IN_SECONDS);
        return $result;
    }

    public function save_task_meta(int $post_id): void
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        foreach ($this->task_fields as $field => $meta_key) {
            if (isset($_POST["task_{$field}"])) {
                $value = $this->sanitize_field($field, $_POST["task_{$field}"]);
                update_post_meta($post_id, $meta_key, $value);

                if ($field === 'due_date') {
                    $timestamp = strtotime($value);
                    update_post_meta($post_id, $this->task_fields['due_date_ts'], $timestamp);
                }
            }
        }

        $this->clear_cache();
    }

    public function clear_cache(): void
    {
        delete_transient('cached_tasks');
    }

    public function update_task_status(int $post_id, string $status): void
    {
        update_post_meta($post_id, $this->task_fields['status'], $status);
        $this->clear_cache();
    }

    public function delete_task(int $post_id): void
    {
        wp_delete_post($post_id, true);
        $this->clear_cache();
    }

    public static function sanitize_field(string $field, $value)
    {
        switch ($field) {
            case 'description':
                return sanitize_textarea_field($value);

            case 'due_date':
                return date('Y-m-d H:i', strtotime($value));

            case 'status':
                return in_array($value, ['pending', 'completed']) ? $value : 'pending';

            default:
                return sanitize_text_field($value);
        }
    }
}
