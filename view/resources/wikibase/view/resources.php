<?php

/**
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
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

		'wikibase.view.ViewController' => $moduleTemplate + array(
			'scripts' => 'ViewController.js',
			'dependencies' => array(
				'util.inherit',
				'wikibase.view.__namespace',
			)
		),

		'wikibase.view.ToolbarViewController' => $moduleTemplate + array(
			'scripts' => 'ToolbarViewController.js',
			'dependencies' => array(
				'util.inherit',
				'wikibase.view.__namespace',
				'wikibase.view.ViewController',
			),
			'messages' => array(
				'wikibase-save-inprogress',
			)
		),

		'wikibase.view.ViewFactory' => $moduleTemplate + array(
			'scripts' => array(
				'ViewFactory.js'
			),
			'dependencies' => array(
				'jquery.util.EventSingletonManager',
				'jquery.wikibase.entitytermsview',
				'jquery.wikibase.itemview',
				'jquery.wikibase.listview', // For ListItemAdapter
				'jquery.wikibase.propertyview',
				'jquery.wikibase.sitelinkgroupview',
				'jquery.wikibase.sitelinklistview',
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
