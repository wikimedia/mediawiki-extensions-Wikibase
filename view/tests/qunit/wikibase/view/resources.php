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

		'wikibase.view.testController' => $moduleTemplate + array(
			'scripts' => array(
				'testController.js',
			),
			'dependencies' => array(
				'wikibase.view.Controller',
			),
		),

		'wikibase.view.ToolbarController.tests' => $moduleTemplate + array(
			'scripts' => array(
				'ToolbarController.tests.js',
			),
			'dependencies' => array(
				'wikibase.view.testController',
				'wikibase.view.ToolbarController',
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

	);

	return $modules;

} );
