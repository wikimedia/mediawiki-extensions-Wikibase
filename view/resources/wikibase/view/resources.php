<?php

/**
 * @license GNU GPL v2+
 * @author Adrian Heine < adrian.heine@wikimedia.de >
 */
return call_user_func( function() {
	preg_match( '+' . preg_quote( DIRECTORY_SEPARATOR ) . '(?:vendor|extensions)'
		. preg_quote( DIRECTORY_SEPARATOR ) . '.*+', __DIR__, $remoteExtPath );

	$moduleTemplate = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' => '..' . $remoteExtPath[0]
	);

	$modules = array(
		'wikibase.view.__namespace' => $moduleTemplate + array(
			'scripts' => array(
				'namespace.js'
			),
			'dependencies' => array(
				'wikibase'
			)
		),

		'wikibase.view.Controller' => $moduleTemplate + array(
			'scripts' => 'Controller.js',
			'dependencies' => array(
				'util.inherit',
				'wikibase.view.__namespace',
			)
		),

		'wikibase.view.ToolbarController' => $moduleTemplate + array(
			'scripts' => 'ToolbarController.js',
			'dependencies' => array(
				'util.inherit',
				'wikibase.view.__namespace',
				'wikibase.view.Controller',
			)
		),

		'wikibase.view.ViewFactory' => $moduleTemplate + array(
			'scripts' => array(
				'ViewFactory.js'
			),
			'dependencies' => array(
				'jquery.wikibase.entitytermsview',
				'jquery.wikibase.itemview',
				'jquery.wikibase.listview', // For ListItemAdapter
				'jquery.wikibase.propertyview',
				'jquery.wikibase.statementgrouplistview',
				'jquery.wikibase.statementgroupview',
				'jquery.wikibase.statementlistview',
				'jquery.wikibase.statementview',
				'wikibase.datamodel.MultiTerm',
				'wikibase.datamodel.Term',
				'wikibase.utilities.ClaimGuidGenerator',
				'wikibase.view.__namespace',
				'wikibase.ValueViewBuilder'
			),
			'messages' => array(
				'wikibase-entitytermsview-input-help-message',
			)
		),
	);

	return $modules;
} );
