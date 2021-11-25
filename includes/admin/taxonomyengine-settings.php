<?php

class TaxonomyEngineSettings {
    private $options = [
        "taxonomyengine_post_types",
        "taxonomyengine_article_strategy",
        "taxonomyengine_percentage_pass",
        "taxonomyengine_pass_score",
        "taxonomyengine_developer_mode",
    ];

    const TAXONOMYENGINE_ARTICLE_SELECTION_STRATEGIES = [
        "taxonomyengine-article-strategy-random" => "Random",
        "taxonomyengine-article-strategy-latest" => "Latest",
        "taxonomyengine-article-strategy-popular" => "Popular",
    ];

    const TAXONOMYENGINE_TAGS_PASS = [
        "taxonomyengine-tags-pass-none" => "None",
        "taxonomyengine-tags-pass-all" => "All",
        "taxonomyengine-tags-pass-custom" => "Custom",
    ];
    
    public function __construct($taxonomyengine_globals) {
        $this->taxonomyengine_globals = &$taxonomyengine_globals;
        add_action('admin_menu', [ $this, 'settings_page' ]);
        add_action('admin_init', [ $this, 'register_settings' ]);
        
    }

    public function settings_page() {
        add_submenu_page(
            'taxonomyengine',
			'TaxonomyEngine Settings',
			'Settings',
			'manage_options',
			'taxonomyengine',
			[ $this, 'taxonomyengine_settings' ]
		);
    }

    public function taxonomyengine_settings() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        // TaxonomyEngineSetup::check_setup_tasks();
        if (!TaxonomyEngineSetup::has_terms()) {
            echo '<div class="notice notice-error"><p>TaxonomyEngine has no terms set. Please <a href="/wp-admin/edit-tags.php?taxonomy=taxonomyengine">set some terms</a> or <a href="/wp-admin/admin.php?page=taxonomyengine&taxonomyengine_predefined_terms=true">use our pre-defined terms</a>.</p></div>';
        }
        if (empty(get_option('taxonomyengine_post_types'))) {
            echo '<div class="notice notice-error"><p>TaxonomyEngine has no post types set. Set <a href="/wp-admin/admin.php?page=taxonomyengine&taxonomyengine_set_post_type=post">Post</a> as a post type?</p></div>';
        }
        if (empty(get_option('taxonomyengine_article_strategy'))) {
            echo '<div class="notice notice-error"><p>TaxonomyEngine has no article strategy set. Set <a href="/wp-admin/admin.php?page=taxonomyengine&taxonomyengine_set_article_strategy=Latest">Latest</a> as the article strategy?</p></div>';
        }
        if (empty(get_option('taxonomyengine_percentage_pass'))) {
            echo '<div class="notice notice-error"><p>TaxonomyEngine has no percentage pass set. Set <a href="/wp-admin/admin.php?page=taxonomyengine&taxonomyengine_set_percentage_pass=50">50%</a> as the percentage pass?</p></div>';
        }
        if (empty(get_option('taxonomyengine_pass_score'))) {
            echo '<div class="notice notice-error"><p>TaxonomyEngine has no pass score set. Set <a href="/wp-admin/admin.php?page=taxonomyengine&taxonomyengine_set_pass_score=0.8">0.8</a> as the pass score?</p></div>';
        }
		require_once plugin_dir_path( dirname( __FILE__ ) ).'../templates/admin/settings.php';
    }

    public function register_settings() {
        foreach($this->options as $option) {
            register_setting( 'taxonomyengine-settings-group', $option );
        }
    }

    static function get_article_strategies() {
        return self::TAXONOMYENGINE_ARTICLE_SELECTION_STRATEGIES;
    }

    public function get_author_list($search = "") {
        $users = get_users(array( 'search' => $search, 'role__in' => array( 'author', 'editor', 'administrator' ) ) );
        $user_list = [];
        foreach($users as $user) {
            $user_list[$user->ID] = $user->display_name;
        }
        return $user_list;
    }
}