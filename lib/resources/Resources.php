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
	$remoteExtPathParts = explode(
		DIRECTORY_SEPARATOR . 'extensions' . DIRECTORY_SEPARATOR, __DIR__, 2
	);
	$moduleTemplate = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' => $remoteExtPathParts[1],
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

		'wikibase.templates' => $moduleTemplate + array(
			'class' => 'Wikibase\TemplateModule',
			'scripts' => 'templates.js',
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

		'wikibase.RevisionStore' => $moduleTemplate + array(
			'scripts' => array(
				'wikibase.RevisionStore.js',
			),
			'dependencies' => array(
				'wikibase'
			)
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

		'wikibase.ValueViewBuilder' => $moduleTemplate + array(
			'scripts' => array(
				'wikibase.ValueViewBuilder.js',
			),
			'dependencies' => array(
				'wikibase',
				'jquery.valueview',
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

		'jquery.ui.tagadata' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.ui/jquery.ui.tagadata.js',
			),
			'styles' => array(
				'jquery.ui/jquery.ui.tagadata.css',
			),
			'dependencies' => array(
				'jquery.event.special.eachchange',
				'jquery.effects.blind',
				'jquery.inputautoexpand',
				'jquery.ui.widget',
			),
		),

		'jquery.ui.TemplatedWidget' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.ui/jquery.ui.TemplatedWidget.js',
			),
			'dependencies' => array(
				'wikibase.templates',
				'jquery.ui.widget',
				'util.inherit',
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
		include( __DIR__ . '/entityChangers/resources.php' ),
		include( __DIR__ . '/experts/resources.php' ),
		include( __DIR__ . '/formatters/resources.php' ),
		include( __DIR__ . '/jquery.wikibase/resources.php' ),
		include( __DIR__ . '/parsers/resources.php' ),
		include( __DIR__ . '/wikibase.RepoApi/resources.php' ),
		include( __DIR__ . '/wikibase.store/resources.php' ),
		include( __DIR__ . '/wikibase.utilities/resources.php' )
	);

	if ( defined( 'ULS_VERSION' ) ) {
		$modules['wikibase']['dependencies'][] = 'ext.uls.mediawiki';
		$modules['wikibase.Site']['dependencies'][] = 'ext.uls.mediawiki';
	}

	return $modules;
} );
