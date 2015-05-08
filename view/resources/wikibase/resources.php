<?php

/**
 * @licence GNU GPL v2+
 * @author Daniel Werner
 * @author H. Snater < mediawiki@snater.com >
 */
return call_user_func( function() {
	preg_match( '+' . preg_quote( DIRECTORY_SEPARATOR ) . '(?:vendor|extensions)'
		. preg_quote( DIRECTORY_SEPARATOR ) . '.*+', __DIR__, $remoteExtPath );

	$moduleTemplate = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' => '..' . $remoteExtPath[0],
	);

	$modules = array(

		// common styles independent from JavaScript being enabled or disabled
		'wikibase.common' => $moduleTemplate + array(
			'position' => 'top',
			'styles' => array(
				// Order must be hierarchical, do not order alphabetically
				'wikibase.css',
				'../jquery/wikibase/themes/default/jquery.wikibase.aliasesview.css',
				'../jquery/wikibase/themes/default/jquery.wikibase.descriptionview.css',
				'../jquery/wikibase/themes/default/jquery.wikibase.entityview.css',
				'../jquery/wikibase/themes/default/jquery.wikibase.entitytermsview.css',
				'../jquery/wikibase/themes/default/jquery.wikibase.entitytermsforlanguagelistview.css',
				'../jquery/wikibase/themes/default/jquery.wikibase.entitytermsforlanguageview.css',
				'../jquery/wikibase/themes/default/jquery.wikibase.labelview.css',
				'../jquery/wikibase/themes/default/jquery.wikibase.sitelinkgrouplistview.css',
				'../jquery/wikibase/themes/default/jquery.wikibase.sitelinkgroupview.css',
				'../jquery/wikibase/themes/default/jquery.wikibase.sitelinklistview.css',
				'../jquery/wikibase/themes/default/jquery.wikibase.sitelinkview.css',
				'../jquery/wikibase/themes/default/jquery.wikibase.statementgroupview.css',
			)
		),

		'wikibase.getLanguageNameByCode' => $moduleTemplate + array(
			'scripts' => array(
				'wikibase.getLanguageNameByCode.js'
			),
			'dependencies' => array(
				'wikibase'
			)
		),

		'wikibase.RevisionStore' => $moduleTemplate + array(
			'scripts' => array(
				'wikibase.RevisionStore.js',
			),
			'dependencies' => array(
				'wikibase'
			)
		),

		'wikibase.templates' => $moduleTemplate + array(
			'class' => 'Wikibase\View\Module\TemplateModule',
			'scripts' => 'templates.js',
		),

		'wikibase.ValueViewBuilder' => $moduleTemplate + array(
			'scripts' => array(
				'wikibase.ValueViewBuilder.js',
			),
			'dependencies' => array(
				'wikibase',
				'jquery.valueview',
			),
		),

		// This declaration is redundant for the sake of addModuleStyles
		'jquery.ui.core.styles' => array(
			'position' => 'top',
			'skinStyles' => array(
				'default' => array(
					'resources/lib/jquery.ui/themes/smoothness/jquery.ui.core.css',
					'resources/lib/jquery.ui/themes/smoothness/jquery.ui.theme.css',
				),
			),
			'group' => 'jquery.ui',
		),

	);

	if ( defined( 'ULS_VERSION' ) ) {
		$modules['wikibase.getLanguageNameByCode']['dependencies'][] = 'ext.uls.mediawiki';
	}

	return $modules;
} );
