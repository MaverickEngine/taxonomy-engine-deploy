<?php

class TaxonomyEngineReports {
    
    public function __construct($taxonomyengine_globals) {
        $this->taxonomyengine_globals = &$taxonomyengine_globals;
        add_action('admin_menu', [ $this, 'reports_page' ]);
        new TaxonomyEngineScripts();
        $this->taxonomyengine_db = new TaxonomyEngineDB($this->taxonomyengine_globals);
    }

    public function reports_page() {
        add_submenu_page(
            'taxonomyengine',
			'TaxonomyEngine Reports',
			'Reports',
			'manage_options',
			'taxonomyengine_reports',
			[ $this, 'taxonomyengine_reports' ],
		);
    }

    public function taxonomyengine_reports() {
		require_once plugin_dir_path( dirname( __FILE__ ) ).'admin/views/reports.php';
    }

    function check_administrator_access(WP_REST_Request $request) {
        return current_user_can('administrator');
    }
}