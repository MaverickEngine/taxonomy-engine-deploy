<?php
$google_credentials = TaxonomyEngineAutoML::get_google_credentials();
?>
<div class="wrap">
    <h2><?php _e( 'TaxonomyEngine AutoML Settings', 'taxonomyengine' ); ?></h2>
    <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post" enctype="multipart/form-data">
        <?php
        if ($_GET['upload_google_credentials_file'] == 'success') {
            echo '<div id="message" class="updated notice is-dismissible"><p>Google credentials file uploaded successfully.</p></div>';
        }
        if ($_GET['upload_google_credentials_file'] == 'error') {
            echo '<div id="message" class="error notice is-dismissible"><p>Error uploading Google credentials file.</p></div>';
        }
        if ($_GET['upload_google_credentials_file'] == 'deleted') {
            echo '<div id="message" class="updated notice is-dismissible"><p>Google credentials file deleted.</p></div>';
        }
        ?>
        <input type="hidden" name="action" value="upload_google_credentials_file">
        <input type="hidden" name="_wp_nonce" value="<?php echo wp_create_nonce('upload_google_credentials_file'); ?>">
        <table class="form-table">
            <tbody>
                <tr>
                    <?php
                    if (!$google_credentials) {
                    ?>
                    <th scope="row"><?php _e("Google credentials", "taxonomyengine") ?></th>
                    <!-- Upload google credentials file -->
                    <td>
                        <input type="file" name="taxonomyengine_google_credentials_file" id="taxonomyengine_google_credentials_file" accept=".json"/>
                        <p class="description">
                            <?php _e("Upload your Google credentials file", "taxonomyengine") ?>
                        </p>
                    </td>
                    <?php
                    } else {
                    ?>
                    <td>
                        <p class="description">
                            <strong><?php _e("Project ID:", "taxonomyengine") ?></strong>
                            <?= $google_credentials["project_id"] ?>
                        </p>
                        <p class="description">
                            <strong><?php _e("Client email:", "taxonomyengine") ?></strong>
                            <?= $google_credentials["client_email"] ?>
                        </p>
                        <p class="description">
                            <strong><?php _e("Dataset ID:", "taxonomyengine") ?></strong>
                            <?= get_option("taxonomyengine_automl_dataset_id") ?>
                        </p>
                        <p>
                            <!-- Delete button -->
                            <input type="submit" name="taxonomyengine_delete_google_credentials" id="taxonomyengine_delete_google_credentials" class="button button-secondary" value="<?php _e("Delete Google credentials", "taxonomyengine") ?>" onclick="return(confirm('Are you sure you want to delete your Google credentials?'))" />
                        </p>
                    </td>
                    <?php
                    }
                    ?>
                </tr>
            </tbody>
        </table>
        <?=	submit_button(); ?>
    </form>
</div>