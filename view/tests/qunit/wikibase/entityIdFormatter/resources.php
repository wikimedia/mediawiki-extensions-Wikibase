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
