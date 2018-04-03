<?php

/**
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
return call_user_func( function() {
	$moduleTemplate = [
		'localBasePath' => __DIR__,
		'remoteExtPath' => 'Wikibase/view/tests/qunit/wikibase/entityIdFormatter',
	];

	return [
		'wikibase.entityIdFormatter.testEntityIdHtmlFormatter' => $moduleTemplate + [
			'scripts' => [
				'testEntityIdHtmlFormatter.js',
			],
			'dependencies' => [
				'wikibase.entityIdFormatter.__namespace',
				'wikibase.entityIdFormatter.EntityIdHtmlFormatter',
			],
		],

		'wikibase.entityIdFormatter.DataValueBasedEntityIdHtmlFormatter.tests' => $moduleTemplate + [
			'scripts' => [
				'DataValueBasedEntityIdHtmlFormatter.tests.js',
			],
			'dependencies' => [
				'wikibase.entityIdFormatter.DataValueBasedEntityIdHtmlFormatter',
				'wikibase.entityIdFormatter.testEntityIdHtmlFormatter',
			],
		],

		'wikibase.entityIdFormatter.DataValueBasedEntityIdPlainFormatter.tests' => $moduleTemplate + [
			'scripts' => [
				'DataValueBasedEntityIdPlainFormatter.tests.js',
			],
			'dependencies' => [
				'wikibase.entityIdFormatter.DataValueBasedEntityIdPlainFormatter',
			],
		],
	];
} );
