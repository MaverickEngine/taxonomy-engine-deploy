<div class="wrap">
    <form method="post" action="options.php">
        <?php settings_fields( 'taxonomyengine-settings-group' ); ?>
        <?php do_settings_sections( 'taxonomyengine-settings-group' ); ?>
        <h1><?php _e( 'TaxonomyEngine Settings', 'taxonomyengine' ); ?></h1>
        <?php settings_errors(); ?>
        <hr>
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row"><?php _e("Select post types", "taxonomyengine") ?></th>
                    <td>
                        <?php
                            $post_types = get_post_types(array('public' => true), 'objects');
                            foreach($post_types as $post_type) {
                                $checked = (get_option('taxonomyengine_post_types') && in_array($post_type->name, get_option('taxonomyengine_post_types'))) ? 'checked' : '';
                                echo '<input type="checkbox" name="taxonomyengine_post_types[]" value="' . $post_type->name . '" ' . $checked . '> ' . $post_type->label . '<br>';
                            }
                        ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e("Require answer before moving to next question", "taxonomyengine") ?></th>
                    <td>
                        <input type="checkbox" name="taxonomyengine_require_answer" value="1" <?php echo get_option('taxonomyengine_require_answer') ? 'checked' : '' ?>>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e("Instruction text", "taxonomyengine") ?></th>
                    <td>
                        <textarea name="taxonomyengine_instruction_text" rows="5" cols="50"><?php echo get_option('taxonomyengine_instruction_text') ?></textarea>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e("Next article strategy", "taxonomyengine") ?></th>
                    <td>
                        <?php
                            $article_strategies = TaxonomyEngineSettings::get_article_strategies();
                            foreach($article_strategies as $article_strategy) {
                                $checked = (get_option('taxonomyengine_article_strategy') == $article_strategy) ? 'checked' : '';
                                echo '<input type="radio" name="taxonomyengine_article_strategy" value="' . $article_strategy . '" ' . $checked . '> ' . $article_strategy . '<br>';
                            }
                        ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e("Next article start date limit", "taxonomyengine") ?></th>
                    <td>
                        <input type="date" name="taxonomyengine_next_article_start_date_limit" id="taxonomyengine_next_article_start_date_limit" value="<?= get_option('taxonomyengine_next_article_start_date_limit') ?>">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e("Next article end date limit", "taxonomyengine") ?></th>
                    <td>
                        <input type="date" name="taxonomyengine_next_article_end_date_limit" id="taxonomyengine_next_article_end_date_limit" value="<?= get_option('taxonomyengine_next_article_end_date_limit') ?>">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <?php _e("RevEngine Wordpress API URL", "taxonomyengine") ?>
                    </th>
                    <td>
                        <input type="url" name="taxonomyengine_revengine_wordpress_api_url" id="taxonomyengine_revengine_wordpress_api_url" value="<?= get_option('taxonomyengine_revengine_wordpress_api_url') ?>">
                        <p><?php _e("Required for popular article strategy", "taxonomyengine") ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <?php _e("Random Article Jitter Factgor", "taxonomyengine") ?>
                    </th>
                    <td>
                        <input type="number" name="taxonomyengine_jitter_factor" id="taxonomyengine_jitter_factor" value="<?= get_option('taxonomyengine_jitter_factor') ?>" min="1" max="20">
                        <p><?php _e("Required for popular article strategy", "taxonomyengine") ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e("What percentage of matched tags are required to pass?", "taxonomyengine") ?></th>
                    <td>
                        <input type="number" name="taxonomyengine_percentage_pass" value="<?php echo get_option('taxonomyengine_percentage_pass') ?>" min="0" max="100">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e("What score is needed to pass?", "taxonomyengine") ?></th>
                    <td>
                        <input type="number" name="taxonomyengine_pass_score" value="<?php echo get_option('taxonomyengine_pass_score') ?>" min="0" max="1" step="0.1">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e("What is the default starting weight for new reviewers?", "taxonomyengine") ?></th>
                    <td>
                        <input type="number" name="taxonomyengine_default_starting_weight" value="<?php echo get_option('taxonomyengine_default_starting_weight', TAXONOMYENGINE_DEFAULT_STARTING_WEIGHT) ?>" min="0" max="1" step="0.1">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e("Developer mode", "taxonomyengine") ?></th>
                    <td>
                        <input type="checkbox" name="taxonomyengine_developer_mode" value="1" <?php echo get_option('taxonomyengine_developer_mode') ? 'checked' : '' ?>>
                    </td>
                </tr>
                <?php if (get_option('taxonomyengine_developer_mode')) { ?>
                <tr>
                    <th scope="row"><?php _e("Reset Taxonomy", "taxonomyengine") ?></th>
                    <td>
                        <!-- //button -->
                        <input type="button" name="taxonomyengine_reset_taxonomy" value="<?php _e("Reset Taxonomy", "taxonomyengine") ?>" class="button button-danger" style="background-color: #d63638; border-color: #d63638; color: white" onclick="if (confirm('This will reset the taxonomy to default. Are you sure?')) { location.href='/wp-admin/admin.php?page=taxonomyengine&taxonomyengine_reset_terms=true'; }">
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
        <?=	submit_button(); ?>
    </form>
</div>