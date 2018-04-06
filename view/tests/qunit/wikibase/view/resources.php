<?php

/**
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
return call_user_func( function() {
	$moduleTemplate = [
		'localBasePath' => __DIR__,
		'remoteExtPath' => 'Wikibase/view/tests/qunit/wikibase/view',
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

		'wikibase.view.ViewFactoryFactory.tests' => $moduleTemplate + [
			'scripts' => [
				'ViewFactoryFactory.tests.js',
			],
			'dependencies' => [
				'wikibase.view.ViewFactoryFactory'
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
