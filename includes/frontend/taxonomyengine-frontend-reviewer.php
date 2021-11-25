<?php

class TaxonomyEngineFrontendReviewer {

    /**
     * Constructor
     */
    public function __construct() {
        $this->developer_mode = get_option('taxonomyengine_developer_mode');
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
        add_action( 'wp_footer', array( $this, 'print_scripts' ) );
        add_filter( 'the_content', array( $this, 'append_reviewer_content') );
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
            wp_enqueue_style( 'taxonomyengine', plugins_url( '../../dist/taxonomyengine.css', __FILE__ ), array(), '1.0.0' );
        }
    }

    /**
     * Print scripts
     */
    public function print_scripts() {
        if ($this->_show_content()) {
            $id = get_the_ID();
            $_wpnonce = wp_create_nonce( 'wp_rest' );
            ?>
            <script type="text/javascript">
                var taxonomyengine_post_id = <?= $id; ?>;
                var _wpnonce = "<?= $_wpnonce; ?>";
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
