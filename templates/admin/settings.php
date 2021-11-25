<div class="wrap">
    <form method="post" action="options.php">
        <?php settings_fields( 'taxonomyengine-settings-group' ); ?>
        <?php do_settings_sections( 'taxonomyengine-settings-group' ); ?>
        <h2><?php _e( 'TaxonomyEngine Settings', 'taxonomyengine' ); ?></h2>
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
                    <th scope="row"><?php _e("Select article strategy", "taxonomyengine") ?></th>
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
                    <th scope="row"><?php _e("Developer mode", "taxonomyengine") ?></th>
                    <td>
                        <input type="checkbox" name="taxonomyengine_developer_mode" value="1" <?php echo get_option('taxonomyengine_developer_mode') ? 'checked' : '' ?>>
                    </td>
                </tr>
            </tbody>
        </table>
        <?=	submit_button(); ?>
    </form>
</div>