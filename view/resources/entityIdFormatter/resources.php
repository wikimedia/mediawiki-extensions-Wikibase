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
		'wikibase.view.entityIdFormatter.__namespace' => $moduleTemplate + array(
			'scripts' => array(
				'namespace.js'
			),
			'dependencies' => array(
				'wikibase.view.__namespace',
			)
		),
		'wikibase.view.entityIdFormatter.EntityIdHtmlFormatter' => $moduleTemplate + array(
			'scripts' => array(
				'EntityIdHtmlFormatter.js'
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.view.entityIdFormatter.__namespace',
			)
		),
		'wikibase.view.entityIdFormatter.EntityIdPlainFormatter' => $moduleTemplate + array(
			'scripts' => array(
				'EntityIdPlainFormatter.js'
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.view.entityIdFormatter.__namespace',
			)
		),
		'wikibase.view.entityIdFormatter.SimpleEntityIdHtmlFormatter' => $moduleTemplate + array(
			'scripts' => array(
				'SimpleEntityIdHtmlFormatter.js'
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.store.EntityStore',
				'wikibase.utilities',
				'wikibase.view.entityIdFormatter.__namespace',
				'wikibase.view.entityIdFormatter.EntityIdHtmlFormatter',
			)
		),
		'wikibase.view.entityIdFormatter.SimpleEntityIdPlainFormatter' => $moduleTemplate + array(
			'scripts' => array(
				'SimpleEntityIdPlainFormatter.js'
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.store.EntityStore',
				'wikibase.utilities',
				'wikibase.view.entityIdFormatter.__namespace',
				'wikibase.view.entityIdFormatter.EntityIdPlainFormatter',
			)
		),
	);

	return $modules;
} );
