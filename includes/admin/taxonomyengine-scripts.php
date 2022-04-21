<?php
class TaxonomyEngineScripts {

    public function __construct() {
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
        add_action( 'admin_footer', array( $this, 'print_scripts' ) );
    }

    /**
     * Enqueue scripts
     */
    public function enqueue_scripts() {
        if (get_option('taxonomyengine_developer_mode')) {
            wp_enqueue_script( 'taxonomyengine', plugins_url( '../../dist/taxonomyengine.js', __FILE__ ), array( 'jquery' ), '1.2.0', true );
        } else {
            wp_enqueue_script( 'taxonomyengine', plugins_url( '../../dist/taxonomyengine.min.js', __FILE__ ), array( 'jquery' ), '1.2.0', true );
        }
    }

    /**
     * Enqueue styles
     */
    public function enqueue_styles() {
        if (get_option('taxonomyengine_developer_mode')) {
            wp_enqueue_style( 'taxonomyengine', plugins_url( '../../dist/taxonomyengine.css', __FILE__ ), array(), '1.2.0' );
        } else {
            wp_enqueue_style( 'taxonomyengine', plugins_url( '../../dist/taxonomyengine.min.css', __FILE__ ), array(), '1.2.0' );
        }
    }

    /**
     * Print scripts
     */
    public function print_scripts() {
        $_wpnonce = wp_create_nonce( 'wp_rest' );
        ?>
        <script type="text/javascript">
            var taxonomyengine_wpnonce = "<?= $_wpnonce; ?>";
            var taxonomyengine_reviewer_role = "<?= TAXONOMYENGINE_REVIEWER_ROLE; ?>";
            var taxonomyengine_default_starting_weight = "<?= get_option('taxonomyengine_default_starting_weight', TAXONOMYENGINE_DEFAULT_STARTING_WEIGHT); ?>";
        </script>
        <?php
    }
}