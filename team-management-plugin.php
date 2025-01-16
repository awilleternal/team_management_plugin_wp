<?php
/*
Plugin Name: Team Management Plugin
Description: A plugin for managing teams and assigning tasks.
Version: 1.1
Author: Amey
*/

class TeamManagementPlugin {
    public function __construct() {
        register_activation_hook(__FILE__, [$this, 'add_custom_roles']);
        register_deactivation_hook(__FILE__, [$this, 'remove_custom_roles']);

        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_shortcode('display_tasks', [$this, 'display_tasks']);
    }

    public function add_custom_roles() {
        $roles = [
            'developer' => 'Developer',
            'content_engineer' => 'Web Content Engineer',
            'analyst' => 'Business Analyst',
            'hr' => 'HR',
        ];

        foreach ($roles as $role => $display_name) {
            add_role($role, $display_name, []); // Initially no capabilities
        }
    }

    public function remove_custom_roles() {
        $roles = ['developer', 'content_engineer', 'analyst', 'hr'];

        foreach ($roles as $role) {
            remove_role($role);
        }
    }

    public function add_admin_menu() {
        add_menu_page(
            'Team Management',
            'Team Management',
            'manage_options',
            'team-management',
            [$this, 'render_admin_page'],
            'dashicons-groups'
        );

        add_submenu_page(
            'team-management',
            'Manage Role Capabilities',
            'Role Capabilities',
            'manage_options',
            'manage-role-capabilities',
            [$this, 'render_role_capabilities_page']
        );

        add_submenu_page(
            'team-management',
            'Assign Task to Users',
            'Assign to Users',
            'manage_options',
            'assign-task-users',
            [$this, 'render_assign_to_users_page']
        );
        add_submenu_page(
            'team-management',
            'Clear All Tasks',
            'Clear Tasks',
            'manage_options',
            'clear-tasks',
            [$this, 'render_clear_tasks_page']
        );
        
    }

    public function render_admin_page() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task'], $_POST['role'])) {
            $task = sanitize_text_field($_POST['task']);
            $role = sanitize_text_field($_POST['role']);

            $tasks = get_option('tmp_tasks', []);
            $tasks[] = ['task' => $task, 'role' => $role];
            update_option('tmp_tasks', $tasks);

            echo '<div class="updated"><p>Task assigned successfully!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Team Management</h1>
            <form method="post" action="">
                <label for="task">Task:</label>
                <input type="text" name="task" id="task" required>
                <label for="role">Assign to Role:</label>
                <select name="role" id="role">
                    <option value="developer">Developer</option>
                    <option value="content_engineer">Web Content Engineer</option>
                    <option value="analyst">Business Analyst</option>
                    <option value="hr">HR</option>
                </select>
                <button type="submit">Assign Task</button>
            </form>
        </div>
        <?php
    }

    public function render_role_capabilities_page() {
        $roles = ['developer', 'content_engineer', 'analyst', 'hr'];
        $capabilities = [
            'read' => 'Read',
            'edit_posts' => 'Edit Posts',
            'delete_posts' => 'Delete Posts',
            'publish_posts' => 'Publish Posts',
            'upload_files' => 'Upload Files',
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['role_capabilities'])) {
            foreach ($_POST['role_capabilities'] as $role => $role_capabilities) {
                $role_obj = get_role($role);
                if ($role_obj) {
                    foreach (array_keys($capabilities) as $cap) {
                        $role_obj->remove_cap($cap);
                    }
                    foreach ($role_capabilities as $cap) {
                        $role_obj->add_cap($cap);
                    }
                }
            }
            echo '<div class="updated"><p>Capabilities updated successfully!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Manage Role Capabilities</h1>
            <form method="post" action="">
                <?php foreach ($roles as $role): ?>
                    <h2><?php echo esc_html(ucwords(str_replace('_', ' ', $role))); ?></h2>
                    <?php $role_obj = get_role($role); ?>
                    <fieldset>
                        <?php foreach ($capabilities as $cap => $label): ?>
                            <label>
                                <input type="checkbox" name="role_capabilities[<?php echo esc_attr($role); ?>][]" 
                                       value="<?php echo esc_attr($cap); ?>" 
                                       <?php echo ($role_obj && $role_obj->has_cap($cap)) ? 'checked' : ''; ?>>
                                <?php echo esc_html($label); ?>
                            </label><br>
                        <?php endforeach; ?>
                    </fieldset>
                <?php endforeach; ?>
                <button type="submit">Update Capabilities</button>
            </form>
        </div>
        <?php
    }

    public function render_assign_to_users_page() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task'], $_POST['user_id'])) {
            $task = sanitize_text_field($_POST['task']);
            $user_id = intval($_POST['user_id']);

            $user_tasks = get_user_meta($user_id, 'tmp_user_tasks', true) ?: [];
            $user_tasks[] = $task;
            update_user_meta($user_id, 'tmp_user_tasks', $user_tasks);

            echo '<div class="updated"><p>Task assigned to user successfully!</p></div>';
        }

        $users = get_users(['fields' => ['ID', 'display_name']]);
        ?>
        <div class="wrap">
            <h1>Assign Task to Users</h1>
            <form method="post" action="">
                <label for="task">Task:</label>
                <input type="text" name="task" id="task" required>
                <label for="user_id">Assign to User:</label>
                <select name="user_id" id="user_id" required>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo esc_attr($user->ID); ?>">
                            <?php echo esc_html($user->display_name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">Assign Task</button>
            </form>
        </div>
        <?php
    }

    public function display_tasks() {
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
    public function clear_all_tasks() {
        // Delete the option storing role-based tasks
        delete_option('tmp_tasks');
        
        // Get all users and clear their individual tasks
        $users = get_users(['fields' => ['ID']]);
        foreach ($users as $user) {
            delete_user_meta($user->ID, 'tmp_user_tasks');
        }
    
        echo '<div class="updated"><p>All tasks have been cleared successfully!</p></div>';
    }
    public function render_clear_tasks_page() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_tasks'])) {
            $this->clear_all_tasks();
        }
        ?>
        <div class="wrap">
            <h1>Clear All Tasks</h1>
            <p>Warning: This will delete all tasks for roles and users. This action cannot be undone.</p>
            <form method="post" action="">
                <input type="hidden" name="clear_tasks" value="1">
                <button type="submit" style="background-color: red; color: white; padding: 10px; border: none; border-radius: 5px; cursor: pointer;">Clear All Tasks</button>
            </form>
        </div>
        <?php
    }
    
    
}

new TeamManagementPlugin();
