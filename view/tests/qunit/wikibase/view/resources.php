<?php

/**
 * @license GPL-2.0+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
return call_user_func( function() {
	preg_match( '+' . preg_quote( DIRECTORY_SEPARATOR ) . '(?:vendor|extensions)'
		. preg_quote( DIRECTORY_SEPARATOR ) . '.*+', __DIR__, $remoteExtPath );

	$moduleTemplate = [
		'localBasePath' => __DIR__,
		'remoteExtPath' => '..' . $remoteExtPath[0]
	];

	return [
		'wikibase.view.testViewController' => $moduleTemplate + [
			'scripts' => [
				'testViewController.js',
			],
			'dependencies' => [
				'wikibase.view.ViewController',
			],
		],

		'wikibase.view.ToolbarViewController.tests' => $moduleTemplate + [
			'scripts' => [
				'ToolbarViewController.tests.js',
			],
			'dependencies' => [
				'wikibase.view.testViewController',
				'wikibase.view.ToolbarViewController',
			],
		],

		'wikibase.view.ViewFactory.tests' => $moduleTemplate + [
			'scripts' => [
				'ViewFactory.tests.js',
			],
			'dependencies' => [
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
			],
		],

		'wikibase.view.ToolbarFactory.tests' => $moduleTemplate + [
			'scripts' => [
				'ToolbarFactory.tests.js',
			],
			'dependencies' => [
				'wikibase.view.ToolbarFactory',
			]
		],
	];
} );
