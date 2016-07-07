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

		'wikibase.view.testViewController' => $moduleTemplate + array(
			'scripts' => array(
				'testViewController.js',
			),
			'dependencies' => array(
				'wikibase.view.ViewController',
			),
		),

		'wikibase.view.ToolbarViewController.tests' => $moduleTemplate + array(
			'scripts' => array(
				'ToolbarViewController.tests.js',
			),
			'dependencies' => array(
				'wikibase.view.testViewController',
				'wikibase.view.ToolbarViewController',
			),
		),

		'wikibase.view.ViewFactory.tests' => $moduleTemplate + array(
			'scripts' => array(
				'ViewFactory.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.Claim',
				'wikibase.datamodel.EntityId',
				'wikibase.datamodel.Fingerprint',
				'wikibase.datamodel.Item',
				'wikibase.datamodel.PropertyNoValueSnak',
				'wikibase.datamodel.Statement',
				'wikibase.datamodel.StatementGroup',
				'wikibase.datamodel.StatementList',
				'wikibase.store.EntityStore',
				'wikibase.entityIdFormatter.EntityIdPlainFormatter',
				'wikibase.view.ViewFactory',
				'wikibase.ValueViewBuilder'
			),
		),

		'wikibase.view.ToolbarFactory.tests' => $moduleTemplate + array(
			'scripts' => array(
				'ToolbarFactory.tests.js',
			),
			'dependencies' => array(
				'wikibase.view.ToolbarFactory',
			)
		),

	);

	return $modules;

} );
