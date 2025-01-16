<?php
/*
Plugin Name: Team Management Plugin
Description: A plugin for managing teams and assigning tasks.
Version: 2.0
Author: Amey
*/

class TeamManagementPlugin {
    public function __construct() {
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);

        // Initialization
        $this->initialize_hooks();
    }

    private function initialize_hooks() {
        add_action('admin_menu', [AdminMenuHandler::class, 'add_admin_menu']);
        add_shortcode('display_tasks', [TaskDisplayHandler::class, 'display_tasks']);
    }

    public function activate() {
        RoleManager::add_custom_roles();
    }

    public function deactivate() {
        RoleManager::remove_custom_roles();
    }
}

class RoleManager {
    private static $roles = [
        'developer' => 'Developer',
        'content_engineer' => 'Web Content Engineer',
        'analyst' => 'Business Analyst',
        'hr' => 'HR',
    ];

    public static function add_custom_roles() {
        foreach (self::$roles as $role => $display_name) {
            if (!get_role($role)) { // Avoid re-adding roles if they already exist
                add_role($role, $display_name, []);
            }
        }
    }

    public static function remove_custom_roles() {
        foreach (array_keys(self::$roles) as $role) {
            remove_role($role);
        }
    }
}


class AdminMenuHandler {
    public static function add_admin_menu() {
        add_menu_page(
            'Team Management',
            'Team Management',
            'manage_options',
            'team-management',
            [self::class, 'render_main_page'],
            'dashicons-groups'
        );

        add_submenu_page(
            'team-management',
            'Manage Role Capabilities',
            'Role Capabilities',
            'manage_options',
            'manage-role-capabilities',
            [RoleCapabilityManager::class, 'render_role_capabilities_page']
        );

        add_submenu_page(
            'team-management',
            'Assign Task to Users',
            'Assign to Users',
            'manage_options',
            'assign-task-users',
            [TaskAssignmentManager::class, 'render_assign_to_users_page']
        );

        add_submenu_page(
            'team-management',
            'Clear All Tasks',
            'Clear Tasks',
            'manage_options',
            'clear-tasks',
            [TaskManager::class, 'render_clear_tasks_page']
        );
    }

    public static function render_main_page() {
        TaskManager::render_task_assignment_form();
    }
}

class RoleCapabilityManager {
    private static $capabilities = [
        'read' => 'Read',
        'edit_posts' => 'Edit Posts',
        'delete_posts' => 'Delete Posts',
        'publish_posts' => 'Publish Posts',
        'upload_files' => 'Upload Files',
    ];

    public static function render_role_capabilities_page() {
        $roles = ['developer', 'content_engineer', 'analyst', 'hr'];
        $capabilities = self::$capabilities;

        // Handle form submission
        self::handle_form_submission();

        // Include the view with the variables in scope
        include plugin_dir_path(__FILE__) . 'views/role_capabilities.php';
    }

    private static function handle_form_submission() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['role_capabilities_nonce'])) {
            if (!wp_verify_nonce($_POST['role_capabilities_nonce'], 'update_role_capabilities')) {
                wp_die('Nonce verification failed.');
            }

            if (isset($_POST['role_capabilities']) && is_array($_POST['role_capabilities'])) {
                foreach ($_POST['role_capabilities'] as $role => $capabilities) {
                    $role_obj = get_role($role);

                    if ($role_obj) {
                        // Remove all current capabilities for this role
                        foreach (self::$capabilities as $cap => $label) {
                            $role_obj->remove_cap($cap);
                        }

                        // Add only selected capabilities
                        foreach ($capabilities as $cap) {
                            if (array_key_exists($cap, self::$capabilities)) {
                                $role_obj->add_cap($cap);
                            }
                        }
                    }
                }
                echo '<div class="updated"><p>Capabilities updated successfully!</p></div>';
            }
        }
    }
}


class TaskManager {
    public static function render_task_assignment_form() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task'], $_POST['role'])) {
            $task = sanitize_text_field($_POST['task']);
            $role = sanitize_text_field($_POST['role']);

            $tasks = get_option('tmp_tasks', []);
            $tasks[] = ['task' => $task, 'role' => $role];
            update_option('tmp_tasks', $tasks);

            echo '<div class="updated"><p>Task assigned successfully!</p></div>';
        }

        include plugin_dir_path(__FILE__) . 'views/task_assignment.php';
    }

    public static function clear_all_tasks() {
        delete_option('tmp_tasks');
        $users = get_users(['fields' => ['ID']]);
        foreach ($users as $user) {
            delete_user_meta($user->ID, 'tmp_user_tasks');
        }

        echo '<div class="updated"><p>All tasks have been cleared successfully!</p></div>';
    }

    public static function render_clear_tasks_page() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_tasks'])) {
            self::clear_all_tasks();
        }

        include plugin_dir_path(__FILE__) . 'views/clear_tasks.php';
    }
}

class TaskAssignmentManager {
    public static function render_assign_to_users_page() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task'], $_POST['user_id'])) {
            $task = sanitize_text_field($_POST['task']);
            $user_id = intval($_POST['user_id']);

            $user_tasks = get_user_meta($user_id, 'tmp_user_tasks', true) ?: [];
            $user_tasks[] = $task;
            update_user_meta($user_id, 'tmp_user_tasks', $user_tasks);

            echo '<div class="updated"><p>Task assigned to user successfully!</p></div>';
        }

        $users = get_users(['fields' => ['ID', 'display_name']]);
        include plugin_dir_path(__FILE__) . 'views/assign_to_users.php';
    }
}

class TaskDisplayHandler {
    public static function display_tasks() {
        $current_user = wp_get_current_user();

        $user_tasks = get_user_meta($current_user->ID, 'tmp_user_tasks', true) ?: [];
        if (!empty($current_user->roles)) {
            $user_role = $current_user->roles[0];
            $role_tasks = get_option('tmp_tasks', []);
            foreach ($role_tasks as $task) {
                if ($task['role'] === $user_role) {
                    $user_tasks[] = $task['task'];
                }
            }
        }

        $user_tasks = array_unique($user_tasks);

        if (empty($user_tasks)) {
            return '<p>You do not have any assigned tasks.</p>';
        }

        $output = '<ul>';
        foreach ($user_tasks as $task) {
            $output .= '<li>' . esc_html($task) . '</li>';
        }
        $output .= '</ul>';

        return $output;
    }
}

new TeamManagementPlugin();
