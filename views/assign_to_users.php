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
