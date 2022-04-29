<?php

class TaxonomyEngineFrontendReviewer {

    /**
     * Constructor
     */
    public function __construct($globals) {
        $this->developer_mode = get_option('taxonomyengine_developer_mode');
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
        add_action( 'wp_footer', array( $this, 'print_scripts' ) );
        add_filter( 'the_content', array( $this, 'append_reviewer_content') );
        require_once(plugin_basename('taxonomyengine-navigation.php' ) );
        $this->taxonomyengine_navigation = new TaxonomyEngineNavigation([]);
    }

    /**
     * Enqueue scripts
     */
    public function enqueue_scripts() {
        if ($this->_show_content()) {
            if ($this->developer_mode) {
                wp_enqueue_script( 'taxonomyengine', plugins_url( '../../dist/taxonomyengine.js', __FILE__ ), array( 'jquery' ), '1.0.0', true );
            } else {
                wp_enqueue_script( 'taxonomyengine', plugins_url( '../../dist/taxonomyengine.min.js', __FILE__ ), array( 'jquery' ), '1.0.0', true );
            }
        }
    }

    /**
     * Enqueue styles
     */
    public function enqueue_styles() {
        if ($this->_show_content()) {
            if ($this->developer_mode) {
                wp_enqueue_style( 'taxonomyengine', plugins_url( '../../dist/taxonomyengine.css', __FILE__ ), array(), '1.0.0' );
            } else {
                wp_enqueue_style( 'taxonomyengine', plugins_url( '../../dist/taxonomyengine.min.css', __FILE__ ), array(), '1.0.0' );
            }
        }
    }

    /**
     * Print scripts
     */
    public function print_scripts() {
        if ($this->_show_content()) {
            $id = get_the_ID();
            $_wpnonce = wp_create_nonce( 'wp_rest' );
            $next_article = $this->taxonomyengine_navigation->get_next_article();
            ?>
            <script type="text/javascript">
                var taxonomyengine_post_id = <?= $id; ?>;
                var taxonomyengine_wpnonce = "<?= $_wpnonce; ?>";
                var taxonomyengine_next_article_url = "<?= get_permalink($next_article->ID); ?>";
                var taxonomyengine_require_answer = <?= get_option('taxonomyengine_require_answer') ?: "0"; ?>;
                var taxonomyengine_instruction_text = "<?= htmlentities(get_option('taxonomyengine_instruction_text'), ENT_COMPAT, "UTF-8"); ?>";
            </script>
            <?php
        }
    }

    private function _show_content() {
        // Only show on individual post pages
        if (!is_single()) {
            return false;
        }
        // Only show if user is logged in
        if (!is_user_logged_in()) {
            return false;
        }
        // Only show if post matches post type
        if (!in_array(get_post_type(), get_option('taxonomyengine_post_types'))) {
            return false;
        }
        // Only show if user has role TAXONOMYENGINE_REVIEWER_ROLE
        $user = wp_get_current_user();
        if (!in_array(TAXONOMYENGINE_REVIEWER_ROLE, $user->roles)) {
            return false;
        }
        return true;
    }

    public function append_reviewer_content( $content ) {
        if ($this->_show_content()) {
            ob_start();
            require_once(plugin_dir_path( dirname( __FILE__ ) ).'../templates/frontend/reviewer_post.php');
            $new_content .= ob_get_clean();
            return $content . $new_content;
        } else {
            return $content;
        }
    }
}
