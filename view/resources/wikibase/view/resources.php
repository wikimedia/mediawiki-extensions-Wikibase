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
		'wikibase.view.__namespace' => $moduleTemplate + array(
			'scripts' => array(
				'namespace.js'
			),
			'dependencies' => array(
				'wikibase'
			)
		),
		'wikibase.view.ViewFactory' => $moduleTemplate + array(
			'scripts' => array(
				'ViewFactory.js'
			),
			'dependencies' => array(
				'jquery.wikibase.entitytermsview',
				'jquery.wikibase.itemview',
				'jquery.wikibase.propertyview',
				'jquery.wikibase.statementgrouplistview',
				'wikibase.datamodel.MultiTerm',
				'wikibase.datamodel.Term',
				'wikibase.utilities.ClaimGuidGenerator',
				'wikibase.view.__namespace',
				'wikibase.ValueViewBuilder'
			),
			'messages' => array(
				'wikibase-entitytermsview-input-help-message',
			)
		),
	);

	return $modules;
} );
