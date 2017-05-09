<?php

/**
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

	return array(
		'wikibase.entityIdFormatter.testEntityIdHtmlFormatter' => $moduleTemplate + array(
			'scripts' => array(
				'testEntityIdHtmlFormatter.js',
			),
			'dependencies' => array(
				'wikibase.entityIdFormatter.__namespace',
				'wikibase.entityIdFormatter.EntityIdHtmlFormatter',
			),
		),

		'wikibase.entityIdFormatter.DataValueBasedEntityIdHtmlFormatter.tests' => $moduleTemplate + array(
			'scripts' => array(
				'DataValueBasedEntityIdHtmlFormatter.tests.js',
			),
			'dependencies' => array(
				'wikibase.entityIdFormatter.DataValueBasedEntityIdHtmlFormatter',
				'wikibase.entityIdFormatter.testEntityIdHtmlFormatter',
			),
		),

		'wikibase.entityIdFormatter.DataValueBasedEntityIdPlainFormatter.tests' => $moduleTemplate + array(
			'scripts' => array(
				'DataValueBasedEntityIdPlainFormatter.tests.js',
			),
			'dependencies' => array(
				'wikibase.entityIdFormatter.DataValueBasedEntityIdPlainFormatter',
			),
		),
	);
} );
