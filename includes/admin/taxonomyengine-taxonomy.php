<?php

class TaxonomyEngineTaxonomy {
    
    function __construct($taxonomyengine_globals) {
        $this->taxonomyengine_globals = &$taxonomyengine_globals;
        add_action('admin_menu', [ $this, 'taxonomy_page' ]);
    }

    function taxonomy_page() {
        add_submenu_page(
            'taxonomyengine',
			'TaxonomyEngine Taxonomy',
			'Taxonomy',
			'manage_options',
			'taxonomyengine_taxonomy',
			[ $this, 'taxonomyengine_taxonomy' ],
		);
    }

    function taxonomyengine_taxonomy() {
		require_once plugin_dir_path( dirname( __FILE__ ) ).'../templates/admin/taxonomy.php';
    }
}