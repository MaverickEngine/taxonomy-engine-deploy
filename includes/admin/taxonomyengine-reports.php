<?php

class TaxonomyEngineReports {
    
    function __construct($taxonomyengine_globals) {
        $this->taxonomyengine_globals = &$taxonomyengine_globals;
        add_action('admin_menu', [ $this, 'reports_page' ]);
    }

    function reports_page() {
        add_submenu_page(
            'taxonomyengine',
			'TaxonomyEngine Reports',
			'Reports',
			'manage_options',
			'taxonomyengine_reports',
			[ $this, 'taxonomyengine_reports' ],
		);
    }

    function taxonomyengine_reports() {
		require_once plugin_dir_path( dirname( __FILE__ ) ).'../templates/admin/reports.php';
    }
}