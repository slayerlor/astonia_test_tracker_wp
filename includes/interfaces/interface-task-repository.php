<?php

interface Task_Repository_Interface
{

    public function get_tasks(): array;

    public function get_task(int $post_id): array;

    public function update_task(array $task_data): bool;

    public function save_task_meta(int $post_id): void;

    public function clear_cache(): void;

    public function update_task_status(int $post_id, string $status): void;

    public function delete_task(int $post_id): void;
}
