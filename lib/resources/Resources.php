<?php

/**
 * @licence GNU GPL v2+
 * @author Daniel Werner
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {
	preg_match( '+' . preg_quote( DIRECTORY_SEPARATOR ) . '(?:vendor|extensions)'
		. preg_quote( DIRECTORY_SEPARATOR ) . '.*+', __DIR__, $remoteExtPath );

	$moduleTemplate = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' => '..' . $remoteExtPath[0],
	);

	$modules = array(

		'mw.config.values.wbSiteDetails' => $moduleTemplate + array(
			'class' => 'Wikibase\SitesModule',
		),

		'mw.config.values.wbRepo' => $moduleTemplate + array(
			'class' => 'Wikibase\RepoAccessModule',
		),

		'wikibase' => $moduleTemplate + array(
			'scripts' => array(
				'wikibase.js',
			),
			'dependencies' => array(
			),
			'messages' => array(
				'special-createitem',
				'wb-special-newitem-new-item-notification',
			),
		),

		'wikibase.Site' => $moduleTemplate + array(
			'scripts' => array(
				'wikibase.Site.js',
			),
			'dependencies' => array(
				'mediawiki.util',
				'util.inherit',
				'wikibase',
			),
		),

		'wikibase.sites' => $moduleTemplate + array(
			'scripts' => array(
				'wikibase.sites.js',
			),
			'dependencies' => array(
				'mw.config.values.wbSiteDetails',
				'wikibase',
				'wikibase.Site',
			),
		),

	);

	$modules = array_merge(
		$modules,
		include( __DIR__ . '/api/resources.php' ),
		include( __DIR__ . '/deprecated/resources.php' ),
		include( __DIR__ . '/jquery.wikibase/resources.php' )
	);

	if ( defined( 'ULS_VERSION' ) ) {
		$modules['wikibase.Site']['dependencies'][] = 'ext.uls.mediawiki';
	}

	return $modules;
} );
