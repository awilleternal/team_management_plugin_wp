<?php if (!empty($roles) && is_array($roles)): ?>
    <div class="wrap">
        <h1>Manage Role Capabilities</h1>
        <form method="post" action="">
            <?php wp_nonce_field('update_role_capabilities', 'role_capabilities_nonce'); // Add a security nonce ?>
            <?php foreach ($roles as $role): ?>
                <h2><?php echo esc_html(ucwords(str_replace('_', ' ', $role))); ?></h2>
                <?php $role_obj = get_role($role); ?>
                <fieldset>
                    <?php foreach ($capabilities as $cap => $label): ?>
                        <label>
                            <input type="checkbox" 
                                   name="role_capabilities[<?php echo esc_attr($role); ?>][]" 
                                   value="<?php echo esc_attr($cap); ?>" 
                                   <?php echo ($role_obj && $role_obj->has_cap($cap)) ? 'checked' : ''; ?>>
                            <?php echo esc_html($label); ?>
                        </label><br>
                    <?php endforeach; ?>
                </fieldset>
            <?php endforeach; ?>
            <button type="submit" class="button button-primary">Update Capabilities</button>
        </form>
    </div>
<?php else: ?>
    <p>No roles available to manage.</p>
<?php endif; ?>
