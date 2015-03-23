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

		'wikibase.entityIdFormatter.testEntityIdHtmlFormatter' => $moduleTemplate + array(
			'scripts' => array(
				'testEntityIdHtmlFormatter.js',
			),
			'dependencies' => array(
				'wikibase.entityIdFormatter.__namespace',
				'wikibase.entityIdFormatter.EntityIdHtmlFormatter',
			),
		),

		'wikibase.entityIdFormatter.SimpleEntityIdHtmlFormatter.tests' => $moduleTemplate + array(
			'scripts' => array(
				'SimpleEntityIdHtmlFormatter.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.Item',
				'wikibase.datamodel.Term',
				'wikibase.store.FetchedContent',
				'wikibase.entityIdFormatter.SimpleEntityIdHtmlFormatter',
				'wikibase.entityIdFormatter.testEntityIdHtmlFormatter',
			),
		),

		'wikibase.entityIdFormatter.SimpleEntityIdPlainFormatter.tests' => $moduleTemplate + array(
			'scripts' => array(
				'SimpleEntityIdPlainFormatter.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.Item',
				'wikibase.datamodel.Term',
				'wikibase.store.FetchedContent',
				'wikibase.entityIdFormatter.SimpleEntityIdPlainFormatter',
			),
		),

	);

	return $modules;

} );
