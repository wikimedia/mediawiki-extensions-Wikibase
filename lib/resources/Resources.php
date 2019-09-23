<?php

use MediaWiki\MediaWikiServices;
use Wikibase\RepoAccessModule;
use Wikibase\Settings;
use Wikibase\SitesModule;

/**
 * @license GPL-2.0-or-later
 * @author Daniel Werner
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {
	$moduleTemplate = [
		'localBasePath' => __DIR__,
		'remoteExtPath' => 'Wikibase/lib/resources',
	];

	$wikibaseApiPaths = [
		'localBasePath' => __DIR__ . '/wikibase-api/src',
		'remoteExtPath' => 'Wikibase/lib/resources/wikibase-api/src',
	];

	$libPaths = [
		'localBasePath' => __DIR__ . '/lib',
		'remoteExtPath' => 'Wikibase/lib/resources/lib',
	];

	$modules = [

		'mw.config.values.wbSiteDetails' => $moduleTemplate + [
			'factory' => function () {
				return new SitesModule(
					Settings::singleton(),
					MediaWikiServices::getInstance()->getSiteStore(),
					MediaWikiServices::getInstance()->getLocalServerObjectCache()
				);
			},
		],

		'mw.config.values.wbRepo' => $moduleTemplate + [
			'class' => RepoAccessModule::class,
		],

		'wikibase' => $moduleTemplate + [
			'scripts' => [
				'wikibase.js',
			],
			'targets' => [ 'desktop', 'mobile' ],
		],

		'wikibase.buildErrorOutput' => $moduleTemplate + [
			'scripts' => [
				'wikibase.buildErrorOutput.js',
			],
			'dependencies' => [
				'wikibase',
			],
		],

		'wikibase.sites' => $moduleTemplate + [
			'scripts' => [
				'wikibase.sites.js',
			],
			'dependencies' => [
				'mw.config.values.wbSiteDetails',
				'wikibase',
				'wikibase.Site',
			],
		],

		'wikibase.api.RepoApi' => $wikibaseApiPaths + [
			'scripts' => [
				'namespace.js',
				'RepoApi.js',
			],
			'targets' => [
				'desktop',
				'mobile'
			]
		],

		'wikibase.api.RepoApiError' => $wikibaseApiPaths + [
			'scripts' => [
				'namespace.js',
				'RepoApiError.js',
			],
			'messages' => [
				'wikibase-error-unexpected',
				'wikibase-error-save-generic',
				'wikibase-error-remove-generic',
				'wikibase-error-save-timeout',
				'wikibase-error-remove-timeout',
				'wikibase-error-ui-no-external-page',
				'wikibase-error-ui-edit-conflict',
			],
			'dependencies' => [
				'util.inherit',
			],
			'targets' => [
				'desktop',
				'mobile'
			],
		],

		'wikibase.api.FormatValueCaller' => $wikibaseApiPaths + [
			'scripts' => [
				'namespace.js',
				'FormatValueCaller.js',
			],
			'dependencies' => [
				'dataValues.DataValue',
				'wikibase.api.RepoApiError',
			]
		],

		'wikibase.api.getLocationAgnosticMwApi' => $wikibaseApiPaths + [
			'scripts' => [
				'namespace.js',
				'getLocationAgnosticMwApi.js',
			],
			'dependencies' => [
				'mediawiki.api',
				'mediawiki.ForeignApi',
			],
			'targets' => [
				'desktop',
				'mobile'
			],
		],

		'wikibase.api.ParseValueCaller' => $wikibaseApiPaths + [
			'scripts' => [
				'namespace.js',
				'ParseValueCaller.js',
			],
			'dependencies' => [
				'wikibase.api.RepoApiError',
			]
		],

		'vue2' => $moduleTemplate + [
			'scripts' => 'vendor/vue2.common.prod.js',
			'targets' => [ 'desktop', 'mobile' ],
		],

		'jquery.event.special.eachchange' => $libPaths + [
			'scripts' => [
				'jquery.event/jquery.event.special.eachchange.js'
			],
			'dependencies' => [
				'jquery.client',
			],
		],

		'jquery.ui.suggester' => $libPaths + [
			'packageFiles' => [
				'jquery.ui/jquery.ui.suggester.js',
				'jquery.ui/jquery.ui.ooMenu.js',
				'jquery.util/jquery.util.getscrollbarwidth.js',
			],
			'styles' => [
				'jquery.ui/jquery.ui.suggester.css',
				'jquery.ui/jquery.ui.ooMenu.css',
			],
			'dependencies' => [
				'jquery.ui.core',
				'jquery.ui.widget',
				'jquery.ui.position',
				'jquery.ui.widget',
				'util.inherit',
			],
		],

		'util.highlightSubstring' => $libPaths + [
			'scripts' => [
				'util/util.highlightSubstring.js',
			],
		],

		'util.inherit' => $libPaths + [
			'scripts' => [
				'util/util.inherit.js',
			],
			'targets' => [ 'desktop', 'mobile' ],
		],

	];

	$modules = array_merge(
		$modules,
		require __DIR__ . '/jquery.wikibase/resources.php'
	);

	return $modules;
} );
