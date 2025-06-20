<?php

class Task_Ajax
{

    private $repository;

    public function __construct(Task_Repository_Interface $repository)
    {
        $this->repository = $repository;
    }

    public function init(): void
    {
        add_action('wp_ajax_task_manager_update_status', [$this, 'update_status']);
        add_action('wp_ajax_task_manager_delete_task', [$this, 'delete_task']);
        add_action('wp_ajax_task_manager_add_task', [$this, 'add_task']);
        add_action('wp_ajax_task_manager_update_task', [$this, 'update_task']);
        add_action('wp_ajax_task_manager_get_tasks', [$this, 'get_tasks']);
        add_action('wp_ajax_task_manager_get_task', [$this, 'get_task']);
    }

    public function update_status(): void
    {
        $this->verify_request();

        $post_id = (int)$_POST['post_id'];
        $status = get_post_meta($post_id, '_task_status', true);
        $new_status = $status === 'completed' ? 'pending' : 'completed';

        $this->repository->update_task_status($post_id, $new_status);
        wp_send_json_success(['new_status' => $new_status]);
    }

    public function delete_task(): void
    {
        $this->verify_request();

        $post_id = (int)$_POST['post_id'];
        $this->repository->delete_task($post_id);
        wp_send_json_success();
    }

    public function update_task(): void
    {
        $this->verify_request();

        $data = [
            'id'        => $_POST['post_id'] ?? 0,
            'title'     => sanitize_text_field($_POST['title']),
            'description' => sanitize_textarea_field($_POST['description']),
            'due_date'  => sanitize_text_field($_POST['due_date']),
        ];

        if ($this->repository->update_task($data)) {
            wp_send_json_success();
        }

        wp_send_json_error(['message' => 'Ошибка обновления']);
    }

    public function add_task(): void
    {
        $this->verify_request();

        $title = sanitize_text_field($_POST['title']);
        $description = sanitize_textarea_field($_POST['description']);
        $due_date = sanitize_text_field($_POST['due_date']);

        $factory = new Task_Factory($this->repository);
        $post_id = $factory->create($title, $description, $due_date);

        if ($post_id) {
            wp_send_json_success(['post_id' => $post_id]);
        } else {
            wp_send_json_error(['message' => 'Failed to create task']);
        }
    }

    public function get_tasks(): void
    {
        $tasks = $this->repository->get_tasks();
        wp_send_json_success(['tasks' => $tasks]);
    }

    public function get_task(): void
    {
        $id = $_POST['post_id'] ?? 0;
        $task = $this->repository->get_task($id);

        wp_send_json_success($task ?: []);
    }

    private function verify_request(): void
    {
        check_ajax_referer('task_manager_ajax', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Unauthorized'], 403);
        }
    }
}
