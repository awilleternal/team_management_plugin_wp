<div class="wrap">
    <h1>Assign Task to Roles</h1>
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
        
        <button type="submit" class="button button-primary">Assign Task</button>
    </form>
</div>
