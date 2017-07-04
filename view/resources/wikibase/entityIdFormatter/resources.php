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

	$modules = [
		'wikibase.entityIdFormatter.__namespace' => $moduleTemplate + [
			'scripts' => [
				'namespace.js'
			],
			'dependencies' => [
				'wikibase.view.__namespace',
			]
		],
		'wikibase.entityIdFormatter.CachingEntityIdHtmlFormatter' => $moduleTemplate + [
			'scripts' => [
				'CachingEntityIdHtmlFormatter.js'
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.entityIdFormatter.__namespace',
				'wikibase.entityIdFormatter.EntityIdHtmlFormatter',
			]
		],
		'wikibase.entityIdFormatter.CachingEntityIdPlainFormatter' => $moduleTemplate + [
			'scripts' => [
				'CachingEntityIdPlainFormatter.js'
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.entityIdFormatter.__namespace',
				'wikibase.entityIdFormatter.EntityIdPlainFormatter',
			]
		],
		'wikibase.entityIdFormatter.DataValueBasedEntityIdHtmlFormatter' => $moduleTemplate + [
			'scripts' => [
				'DataValueBasedEntityIdHtmlFormatter.js'
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.entityIdFormatter.__namespace',
				'wikibase.entityIdFormatter.EntityIdHtmlFormatter',
			]
		],
		'wikibase.entityIdFormatter.DataValueBasedEntityIdPlainFormatter' => $moduleTemplate + [
			'scripts' => [
				'DataValueBasedEntityIdPlainFormatter.js'
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.entityIdFormatter.__namespace',
				'wikibase.entityIdFormatter.EntityIdPlainFormatter',
			]
		],
		'wikibase.entityIdFormatter.EntityIdHtmlFormatter' => $moduleTemplate + [
			'scripts' => [
				'EntityIdHtmlFormatter.js'
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.entityIdFormatter.__namespace',
			]
		],
		'wikibase.entityIdFormatter.EntityIdPlainFormatter' => $moduleTemplate + [
			'scripts' => [
				'EntityIdPlainFormatter.js'
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.entityIdFormatter.__namespace',
			]
		],
	];

	return $modules;
} );
