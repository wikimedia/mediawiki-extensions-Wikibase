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

		// common styles independent from JavaScript being enabled or disabled
		'wikibase.common' => $moduleTemplate + array(
			'styles' => array(
				// Order must be hierarchical, do not order alphabetically
				'wikibase.css',
				'jquery.wikibase/themes/default/jquery.wikibase.aliasesview.css',
				'jquery.wikibase/themes/default/jquery.wikibase.descriptionview.css',
				'jquery.wikibase/themes/default/jquery.wikibase.entityview.css',
				'jquery.wikibase/themes/default/jquery.wikibase.fingerprintgroupview.css',
				'jquery.wikibase/themes/default/jquery.wikibase.fingerprintlistview.css',
				'jquery.wikibase/themes/default/jquery.wikibase.fingerprintview.css',
				'jquery.wikibase/themes/default/jquery.wikibase.labelview.css',
				'jquery.wikibase/themes/default/jquery.wikibase.sitelinkgrouplistview.css',
				'jquery.wikibase/themes/default/jquery.wikibase.sitelinkgroupview.css',
				'jquery.wikibase/themes/default/jquery.wikibase.sitelinklistview.css',
				'jquery.wikibase/themes/default/jquery.wikibase.sitelinkview.css',
			)
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
		include( __DIR__ . '/experts/resources.php' ),
		include( __DIR__ . '/jquery.wikibase/resources.php' ),
		include( __DIR__ . '/jquery.wikibase-shared/resources.php' )
	);

	if ( defined( 'ULS_VERSION' ) ) {
		$modules['wikibase.Site']['dependencies'][] = 'ext.uls.mediawiki';
	}

	return $modules;
} );
