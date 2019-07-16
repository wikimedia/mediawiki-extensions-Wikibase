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
				'RepoApi.js',
			],
			'dependencies' => [
				'wikibase.api.__namespace',
			],
			'targets' => [
				'desktop',
				'mobile'
			]
		],

		'wikibase.api.RepoApiError' => $wikibaseApiPaths + [
			'scripts' => [
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
				'wikibase.api.__namespace',
			],
			'targets' => [
				'desktop',
				'mobile'
			],
		],
		'wikibase.api.__namespace' => $wikibaseApiPaths + [
			'scripts' => [
				'namespace.js'
			],
			'targets' => [
				'desktop',
				'mobile'
			],
		],

		'wikibase.api.FormatValueCaller' => $wikibaseApiPaths + [
			'scripts' => [
				'FormatValueCaller.js',
			],
			'dependencies' => [
				'dataValues.DataValue',
				'wikibase.api.__namespace',
				'wikibase.api.RepoApiError',
			]
		],

		'wikibase.api.getLocationAgnosticMwApi' => $wikibaseApiPaths + [
			'scripts' => [
				'getLocationAgnosticMwApi.js',
			],
			'dependencies' => [
				'mediawiki.api',
				'mediawiki.ForeignApi',
				'wikibase.api.__namespace',
			],
			'targets' => [
				'desktop',
				'mobile'
			],
		],

		'wikibase.api.ParseValueCaller' => $wikibaseApiPaths + [
			'scripts' => [
				'ParseValueCaller.js',
			],
			'dependencies' => [
				'wikibase.api.__namespace',
				'wikibase.api.RepoApiError',
			]
		],
	];

	$modules = array_merge(
		$modules,
		require __DIR__ . '/jquery.wikibase/resources.php'
	);

	return $modules;
} );
