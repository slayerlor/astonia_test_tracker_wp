<?php

class Task_Factory
{
    private $repository;

    public function __construct(Task_Repository_Interface $repository)
    {
        $this->repository = $repository;
    }

    public function create(string $title, string $description, string $due_date): int
    {
        $timestamp = strtotime($due_date);

        $post_id = wp_insert_post([
            'post_title' => sanitize_text_field($title),
            'post_type' => 'task',
            'post_status' => 'publish',
            'meta_input' => [
                '_task_description' => sanitize_textarea_field($description),
                '_task_due_date' => Task_Repository::sanitize_field('due_date', $due_date),
                '_task_due_date_ts' => $timestamp,
                '_task_status' => 'pending'
            ]
        ]);

        if ($post_id) {
            $this->repository->clear_cache();
        }

        return $post_id;
    }
}
