<div class="wrap">
    <form method="post" action="">
        <h2><?php _e( 'TaxonomyEngine Reviewers', 'taxonomyengine' ); ?></h2>
        <?php settings_errors(); ?>
        <hr>
        <table class="wp-list-table widefat fixed striped table-view-list">
            <thead>
                <tr>
                    <th><?= _e("Reviewer", "taxonomyengine") ?></th>
                    <th><?= _e("Weight", "taxonomyengine") ?></th>
                    <th><?= _e("# Articles Reviewed", "taxonomyengine") ?></th>
                </tr>
            </thead>
            <tbody>
            <?php
                $reviewers = TaxonomyEngineReviewers::get_reviewer_list();
                foreach($reviewers as $key => $reviewer) { ?>
                <tr>
                    <td>
                        <?= $reviewer["ID"] ?>
                        <a href="<?= admin_url("user-edit.php?user_id=" . $key) ?>"><?= $reviewer["name"] ?></a>
                    </td>
                    <td>
                        <input type="number" name="taxonomyengine_reviewer_weight[<?= $key ?>]" id="taxonomyengine_reviewer_weight_<?= $key ?>" value="<?= $reviewer["taxonomyengine_reviewer_weight"] ?>" min="0" max="1" step="0.1" />
                    </td>
                    <td><?= $reviewer["articles_reviewed"] ?></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
        <?=	submit_button(); ?>
    </form>
</div>