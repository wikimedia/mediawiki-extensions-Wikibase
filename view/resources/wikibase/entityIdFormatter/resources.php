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
		'wikibase.entityIdFormatter.__namespace' => $moduleTemplate + array(
			'scripts' => array(
				'namespace.js'
			),
			'dependencies' => array(
				'wikibase.view.__namespace',
			)
		),
		'wikibase.entityIdFormatter.CachingEntityIdHtmlFormatter' => $moduleTemplate + array(
			'scripts' => array(
				'CachingEntityIdHtmlFormatter.js'
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.entityIdFormatter.__namespace',
				'wikibase.entityIdFormatter.EntityIdHtmlFormatter',
			)
		),
		'wikibase.entityIdFormatter.CachingEntityIdPlainFormatter' => $moduleTemplate + array(
			'scripts' => array(
				'CachingEntityIdPlainFormatter.js'
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.entityIdFormatter.__namespace',
				'wikibase.entityIdFormatter.EntityIdPlainFormatter',
			)
		),
		'wikibase.entityIdFormatter.DataValueBasedEntityIdHtmlFormatter' => $moduleTemplate + array(
			'scripts' => array(
				'DataValueBasedEntityIdHtmlFormatter.js'
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.entityIdFormatter.__namespace',
				'wikibase.entityIdFormatter.EntityIdHtmlFormatter',
			)
		),
		'wikibase.entityIdFormatter.DataValueBasedEntityIdPlainFormatter' => $moduleTemplate + array(
			'scripts' => array(
				'DataValueBasedEntityIdPlainFormatter.js'
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.entityIdFormatter.__namespace',
				'wikibase.entityIdFormatter.EntityIdPlainFormatter',
			)
		),
		'wikibase.entityIdFormatter.EntityIdHtmlFormatter' => $moduleTemplate + array(
			'scripts' => array(
				'EntityIdHtmlFormatter.js'
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.entityIdFormatter.__namespace',
			)
		),
		'wikibase.entityIdFormatter.EntityIdPlainFormatter' => $moduleTemplate + array(
			'scripts' => array(
				'EntityIdPlainFormatter.js'
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.entityIdFormatter.__namespace',
			)
		),
	);

	return $modules;
} );
