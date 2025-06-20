<div class="task-manager-container">
    <h2><?php _e('Добавить задачу', 'task-manager'); ?></h2>
    <form id="task-form" class="task-form">
        <div class="form-group">
            <label for="task-title"><?php _e('Название:', 'task-manager'); ?></label>
            <input type="text" id="task-title" name="title" required>
        </div>

        <div class="form-group">
            <label for="task-description"><?php _e('Описание:', 'task-manager'); ?></label>
            <textarea id="task-description" name="description" rows="3"></textarea>
        </div>

        <div class="form-group">
            <label for="task-due-date"><?php _e('Дата и время:', 'task-manager'); ?></label>
            <input type="datetime-local" id="task-due-date" name="due_date" required>
        </div>

        <button type="submit" class="button button-primary">
            <?php _e('Добавить задачу', 'task-manager'); ?>
        </button>
    </form>

    <h2><?php _e('Список задач', 'task-manager'); ?></h2>
    <div id="task-list" class="task-list">
        <?php foreach ($this->repository->get_tasks() as $task): ?>
            <div class="task <?php echo esc_attr($task['status']); ?>" data-id="<?php echo esc_attr($task['id']); ?>">
                <h3><?php echo esc_html($task['title']); ?></h3>
                <p><?php echo esc_html($task['description']); ?></p>
                <p class="task-meta">
                    <small>
                        <?php _e('До:', 'task-manager'); ?>
                        <?php echo esc_html($task['due_date']); ?>
                    </small>
                </p>
                <div class="task-actions">
                    <button class="toggle-status button">
                        <?php echo $task['status'] === 'completed' ?
                            __('Вернуть в работу', 'task-manager') :
                            __('Завершить', 'task-manager'); ?>
                    </button>
                    <button class="edit-task" data-id="<?= esc_attr($task['id']) ?>">
                        <?php _e('Редактировать', 'task-manager') ?>
                    </button>
                    <button class="delete-task button button-link-delete">
                        <?php _e('Удалить', 'task-manager'); ?>
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>