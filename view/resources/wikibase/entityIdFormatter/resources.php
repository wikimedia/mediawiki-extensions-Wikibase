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
		'wikibase.entityIdFormatter.__namespace' => $moduleTemplate + array(
			'scripts' => array(
				'namespace.js'
			),
			'dependencies' => array(
				'wikibase.view.__namespace',
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
		'wikibase.entityIdFormatter.SimpleEntityIdHtmlFormatter' => $moduleTemplate + array(
			'scripts' => array(
				'SimpleEntityIdHtmlFormatter.js'
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.store.EntityStore',
				'wikibase.utilities',
				'wikibase.entityIdFormatter.__namespace',
				'wikibase.entityIdFormatter.EntityIdHtmlFormatter',
			)
		),
		'wikibase.entityIdFormatter.SimpleEntityIdPlainFormatter' => $moduleTemplate + array(
			'scripts' => array(
				'SimpleEntityIdPlainFormatter.js'
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.store.EntityStore',
				'wikibase.utilities',
				'wikibase.entityIdFormatter.__namespace',
				'wikibase.entityIdFormatter.EntityIdPlainFormatter',
			)
		),
	);

	return $modules;
} );
