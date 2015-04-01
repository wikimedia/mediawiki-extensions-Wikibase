<?php

/**
 * @licence GNU GPL v2+
 * @author Daniel Werner
 * @author H. Snater < mediawiki@snater.com >
 */
return call_user_func( function() {
	preg_match( '+' . preg_quote( DIRECTORY_SEPARATOR ) . '(?:vendor|extensions)'
		. preg_quote( DIRECTORY_SEPARATOR ) . '.*+', __DIR__, $remoteExtPath );

	$moduleTemplate = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' => '..' . $remoteExtPath[0],
	);

	$modules = array(

		'wikibase.getLanguageNameByCode' => $moduleTemplate + array(
			'scripts' => array(
				'wikibase.getLanguageNameByCode.js'
			),
			'dependencies' => array(
				'wikibase'
			)
		),

		'wikibase.RevisionStore' => $moduleTemplate + array(
			'scripts' => array(
				'wikibase.RevisionStore.js',
			),
			'dependencies' => array(
				'wikibase'
			)
		),

		'wikibase.templates' => $moduleTemplate + array(
			'class' => 'Wikibase\View\Module\TemplateModule',
			'scripts' => 'templates.js',
		),

		'wikibase.ValueViewBuilder' => $moduleTemplate + array(
			'scripts' => array(
				'wikibase.ValueViewBuilder.js',
			),
			'dependencies' => array(
				'wikibase',
				'jquery.valueview',
			),
		),

	);

	if ( defined( 'ULS_VERSION' ) ) {
		$modules['wikibase.getLanguageNameByCode']['dependencies'][] = 'ext.uls.mediawiki';
	}

	return $modules;
} );
