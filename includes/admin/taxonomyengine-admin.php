<?php

class TaxonomyEngineAdmin {

    function __construct($taxonomyengine_globals) {
        $this->taxonomyengine_globals = &$taxonomyengine_globals;
        add_action('admin_menu', [ $this, 'menu' ]);
        require_once('taxonomyengine-settings.php' );
        $taxonomyengine_settings = new TaxonomyEngineSettings($this->taxonomyengine_globals);
        require_once('taxonomyengine-reviewers.php' );
        $taxonomyengine_reviewers = new TaxonomyEngineReviewers($this->taxonomyengine_globals);
        require_once('taxonomyengine-reports.php' );
        $taxonomyengine_reports = new TaxonomyEngineReports($this->taxonomyengine_globals);
        require_once('taxonomyengine-taxonomy.php' );
        $taxonomyengine_taxonomy = new TaxonomyEngineTaxonomy($this->taxonomyengine_globals);
    }

    function menu() {
        add_menu_page(
            'TaxonomyEngine',
			'TaxonomyEngine',
			'manage_options',
			'taxonomyengine',
			null,
            "",
            30
        );
    }
}