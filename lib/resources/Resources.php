<?php

use Wikibase\Client\WikibaseClient;
use Wikibase\Repo\WikibaseRepo;

/**
 * @licence GNU GPL v2+
 * @author Daniel Werner
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {
	preg_match(
		'+' . preg_quote( DIRECTORY_SEPARATOR, '+' ) . '((?:vendor|extensions)' .
			preg_quote( DIRECTORY_SEPARATOR, '+' ) . '.*)$+',
		__DIR__,
		$remoteExtPathParts
	);
	$moduleTemplate = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' => '..' . DIRECTORY_SEPARATOR . $remoteExtPathParts[1],
	);

	$modules = array(

		'mw.config.values.wbDataTypes' => $moduleTemplate + array(
			'class' => 'DataTypes\DataTypesModule',
			'datatypefactory' => function() {
				// TODO: relative uglynes here! Get rid of this method!
				if ( defined( 'WB_VERSION' ) ) { // repo mode
					$wikibase = WikibaseRepo::getDefaultInstance();
				} elseif ( defined( 'WBC_VERSION' ) ) { // client mode
					$wikibase = WikibaseClient::getDefaultInstance();
				} else {
					throw new \RuntimeException( "Neither repo nor client found!" );
				}
				return $wikibase->getDataTypeFactory();
			},
			'datatypesconfigvarname' => 'wbDataTypes',
		),

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
				'wikibase.common',
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

		'jquery.removeClassByRegex' => $moduleTemplate + array(
			'scripts' => array(
				'jquery/jquery.removeClassByRegex.js',
			),
		),

		'jquery.sticknode' => $moduleTemplate + array(
			'scripts' => array(
				'jquery/jquery.sticknode.js',
			),
			'dependencies' => array(
				'jquery.throttle-debounce',
			),
		),

		'wikibase.dataTypes' => $moduleTemplate + array(
			'scripts' => array(
				'wikibase.dataTypes/wikibase.dataTypes.js',
			),
			'dependencies' => array(
				'dataTypes.DataType',
				'dataTypes.DataTypeStore',
				'mw.config.values.wbDataTypes',
				'wikibase',
			),
		),

	);

	$modules = array_merge(
		$modules,
		include( __DIR__ . '/api/resources.php' ),
		include( __DIR__ . '/deprecated/resources.php' ),
		include( __DIR__ . '/experts/resources.php' ),
		include( __DIR__ . '/formatters/resources.php' ),
		include( __DIR__ . '/jquery.wikibase/resources.php' ),
		include( __DIR__ . '/parsers/resources.php' ),
		include( __DIR__ . '/wikibase.RepoApi/resources.php' )
	);

	if ( defined( 'ULS_VERSION' ) ) {
		$modules['wikibase']['dependencies'][] = 'ext.uls.mediawiki';
		$modules['wikibase.Site']['dependencies'][] = 'ext.uls.mediawiki';
	}

	return $modules;
} );
