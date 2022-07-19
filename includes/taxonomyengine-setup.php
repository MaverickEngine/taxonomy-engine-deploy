<?php
class TaxonomyEngineSetup {
    function __construct() {
        add_action( "init", array( $this, "taxonomy_setup" ), 10 );
        add_action( "init", array( $this, "db_setup" ), 10 );
        add_action( "admin_init", array( $this, "ensure_roles" ), 10 );
        add_action('profile_update', [ $this, 'set_reviewer_weight' ], 20, 2 );
        add_action('user_register', [ $this, 'set_reviewer_weight' ], 20, 2 );
        add_action("admin_init", [ $this, "check_setup_tasks" ], 10);
    }

    public static function has_terms() {
        // Check if TaxonomyEngine has terms
        $terms = get_terms("taxonomyengine", [
            'parent' => 0,
            'hide_empty' => false,
        ]);
        return !empty($terms);
    }

    public function taxonomy_setup() {
        $post_types = get_option('taxonomyengine_post_types');
        register_taxonomy( "taxonomyengine", $post_types, [
            "hierarchical" => true,
            "label" => "TaxonomyEngine",
            "show_ui" => true,
            "show_admin_column" => false,
            'show_in_rest' => true,
            "query_var" => true,
            "rewrite" => array( "slug" => "taxonomyengine" ),
            "public" => true,
            "show_in_menu" => false,
            "show_in_quick_edit" => true,
        ]);
    }

    public function ensure_roles() {
        add_role( TAXONOMYENGINE_REVIEWER_ROLE, __(TAXONOMYENGINE_REVIEWER_ROLE_NAME));
    }

    public function db_setup() {
        $taxonomyengine_db_version = get_option("taxonomyengine_db_version", 0 );
        if ($taxonomyengine_db_version == TAXONOMYENGINE_DB_VERSION) {
            return;
        }
        global $wpdb;
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $charset_collate = $wpdb->get_charset_collate();
        $reviews_table_name = $wpdb->prefix . "taxonomyengine_reviews";
        $reviews_sql = "CREATE TABLE $reviews_table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id mediumint(9) NOT NULL,
            user_id mediumint(9) NOT NULL,
            user_score float NOT NULL,
            review_start datetime DEFAULT now() NOT NULL,
            review_end datetime DEFAULT NULL,
            UNIQUE KEY id (id),
            KEY post_id (post_id),
            KEY user_id (user_id),
            KEY review_end (review_end)
        ) $charset_collate;";
        dbDelta( $reviews_sql );
        
        $reviews_taxonomy_table_name = $wpdb->prefix . "taxonomyengine_reviews_taxonomy";
        $reviews_taxonomy_sql = "CREATE TABLE $reviews_taxonomy_table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            taxonomyengine_review_id mediumint(9) NOT NULL,
            taxonomy_id mediumint(9) NOT NULL,
            user_score float NOT NULL,
            timestamp datetime DEFAULT now() NOT NULL,
            UNIQUE KEY id (id),
            KEY taxonomyengine_review_id (taxonomyengine_review_id),
            key timestamp (timestamp)
        ) $charset_collate;";
        $result = dbDelta( $reviews_taxonomy_sql );

        $passed_posts_table_name = $wpdb->prefix . "taxonomyengine_passed_posts";
        $passed_posts_sql = "CREATE TABLE $passed_posts_table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id mediumint(9) NOT NULL,
            score mediumint(9) NOT NULL,
            date_complete datetime DEFAULT now() NOT NULL,
            UNIQUE KEY id (id),
            UNIQUE KEY post_id (post_id),
            KEY date_complete (date_complete)
        ) $charset_collate;";
        $result = dbDelta( $passed_posts_sql );

        update_option( "taxonomyengine_db_version", TAXONOMYENGINE_DB_VERSION );
    }

    public function set_reviewer_weight($user_id, $user) {
        if (empty($user->roles)) {
            $user = get_user_by('id', $user_id);
        }
        if ( in_array( TAXONOMYENGINE_REVIEWER_ROLE, (array) $user->roles ) ) {
            $existing_weight = get_user_meta( $user_id, "taxonomyengine_reviewer_weight", true );
            if (empty($existing_weight)) {
                update_user_meta( $user_id, "taxonomyengine_reviewer_weight", TAXONOMYENGINE_DEFAULT_STARTING_WEIGHT );
            }
        }
    }

    public static function check_setup_tasks() {
        if (isset($_GET["taxonomyengine_predefined_terms"])) {
            self::create_predefined_terms();
        }
        if (isset($_GET["taxonomyengine_reset_terms"])) {
            self::reset_terms();
        }
        $redirect = false;
        if (isset($_GET["delete_terms"])) {
            self::delete_terms();
        }
        if (isset($_GET["taxonomyengine_set_post_type"])) {
            update_option( "taxonomyengine_post_types", explode(",", $_GET["taxonomyengine_set_post_type"]) );
            $redirect = true;
        }
        if (isset($_GET["taxonomyengine_set_article_strategy"])) {
            update_option( "taxonomyengine_article_strategy", $_GET["taxonomyengine_set_article_strategy"] );
            $redirect = true;
        }
        if (isset($_GET["taxonomyengine_set_percentage_pass"])) {
            update_option( "taxonomyengine_percentage_pass", $_GET["taxonomyengine_set_percentage_pass"] );
            $redirect = true;
        }
        if (isset($_GET["taxonomyengine_set_pass_score"])) {
            update_option( "taxonomyengine_pass_score", $_GET["taxonomyengine_set_pass_score"] );
            $redirect = true;
        }
        if ($redirect) {
            wp_redirect(admin_url("admin.php?page=taxonomyengine"));
            exit;
        }
    }

    // For testing, don't show on front end
    private static function delete_terms() {
        $terms = get_terms("taxonomyengine", [
            // 'parent' => 0,
            'hide_empty' => false,
        ]);
        foreach ($terms as $term) {
            wp_delete_term( $term->term_id, "taxonomyengine" );
        }
    }

    private static function _convert_taxonomy($taxonomy) {
        foreach($taxonomy as $key => $item) {
            $parent = term_exists( $item->parent, "taxonomyengine" );
            if ($parent) {
                $parent_id = $parent["term_id"];
            } else {
                $parent_id = 0;
            }
            if (!term_exists($item->slug, "taxonomyengine")) {
                wp_insert_term(
                    $item->name,
                    'taxonomyengine',
                    array(
                        'description'=> $item->description,
                        'slug' => $item->slug,
                        'parent' => $parent_id
                    )
                );
            }
            if (isset($item->children)) {
                self::_convert_taxonomy($item->children);
            }
        }
        delete_option('taxonomyengine_children');
    }

    private static function create_predefined_terms() {
        $existing_terms = get_terms("taxonomyengine", [
            'parent' => 0,
            'hide_empty' => false,
        ]);
        if (!empty($existing_terms)) {
            return;
        }
        $fname = plugin_dir_path( dirname( __FILE__ ) ).'/data/default_taxonomy.json';
        self::_convert_taxonomy(json_decode(file_get_contents($fname)));
    }

    private static function reset_terms() {
        $fname = plugin_dir_path( dirname( __FILE__ ) ).'/data/default_taxonomy.json';
        self::_convert_taxonomy(json_decode(file_get_contents($fname)));
    }
}