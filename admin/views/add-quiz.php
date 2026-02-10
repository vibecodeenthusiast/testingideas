<div class="wrap">
    <h1><?php _e('Add New Quiz', 'quiz-maker-pro'); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('qmp_add_quiz', 'qmp_nonce'); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row"><label for="title"><?php _e('Title', 'quiz-maker-pro'); ?></label></th>
                <td><input type="text" id="title" name="title" class="regular-text" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="description"><?php _e('Description', 'quiz-maker-pro'); ?></label></th>
                <td><textarea id="description" name="description" class="large-text"></textarea></td>
            </tr>
        </table>
        
        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Create Quiz', 'quiz-maker-pro'); ?>">
        </p>
    </form>
</div>