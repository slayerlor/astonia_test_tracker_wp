<?php

final class Task_Manager
{

    private static $instance = null;

    private $repository;

    private $ajax;

    public static function get_instance(): self
    {
        if (null === self::$instance) {
            $repository = new Task_Repository();
            self::$instance = new self($repository);
        }
        return self::$instance;
    }

    private function __construct(Task_Repository_Interface $repository)
    {
        $this->repository = $repository;
        $this->ajax = new Task_Ajax($repository);
    }

    public function init(): void
    {
        $this->register_hooks();
        $this->ajax->init();
    }

    private function register_hooks(): void
    {
        add_action('init', [$this, 'register_post_type']);
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post_task', [$this->repository, 'save_task_meta']);
        add_shortcode('task_manager', [$this, 'render_shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function register_post_type(): void
    {
        register_post_type('task', [
            'labels' => [
                'name' => __('Задачи', 'task-manager'),
                'singular_name' => __('Задача', 'task-manager'),
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'supports' => ['title'],
            'capability_type' => 'post',
            'capabilities' => [
                'create_posts' => 'edit_posts',
            ],
            'map_meta_cap' => true,
        ]);
    }

    public function add_meta_boxes(): void
    {
        add_meta_box(
            'task_details',
            __('Детали задачи', 'task-manager'),
            [$this, 'render_task_meta_box'],
            'task',
            'normal',
            'high'
        );
    }

    public function render_task_meta_box(WP_Post $post): void
    {
        wp_nonce_field('task_manager_meta_box', 'task_manager_nonce');

        $description = get_post_meta($post->ID, '_task_description', true);
        $due_date = get_post_meta($post->ID, '_task_due_date', true);
        $status = get_post_meta($post->ID, '_task_status', true);
?>
        <p>
            <label for="task_description"><?php _e('Описание:', 'task-manager'); ?></label>
            <textarea id="task_description" name="task_description" style="width:100%" rows="5"><?php echo esc_textarea($description); ?></textarea>
        </p>
        <p>
            <label for="task_due_date"><?php _e('Дата и время:', 'task-manager'); ?></label>
            <input type="datetime-local" id="task_due_date" name="task_due_date" value="<?php echo esc_attr($due_date); ?>">
        </p>
        <p>
            <label for="task_status"><?php _e('Статус:', 'task-manager'); ?></label>
            <select id="task_status" name="task_status">
                <option value="pending" <?php selected($status, 'pending'); ?>><?php _e('В работе', 'task-manager'); ?></option>
                <option value="completed" <?php selected($status, 'completed'); ?>><?php _e('Завершить', 'task-manager'); ?></option>
            </select>
        </p>
<?php
    }

    public function render_shortcode(): string
    {
        if (!current_user_can('edit_posts')) {
            return '<p>' . __('У вас нет прав для просмотра задач. Авторизуйтесь', 'task-manager') . '</p>';
        }

        ob_start();
        include __DIR__ . '/../views/task-manager.php';
        return ob_get_clean();
    }

    public function enqueue_assets(): void
    {
        wp_enqueue_style(
            'task-manager',
            plugins_url('assets/css/task-manager.css', __DIR__ . '/../task-manager.php'),
            [],
            filemtime(__DIR__ . '/../assets/css/task-manager.css')
        );

        wp_enqueue_script(
            'task-manager',
            plugins_url('assets/js/task-manager.js', __DIR__ . '/../task-manager.php'),
            [],
            filemtime(__DIR__ . '/../assets/js/task-manager.js'),
            true
        );

        wp_localize_script('task-manager', 'taskManager', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('task_manager_ajax')
        ]);
    }
}
