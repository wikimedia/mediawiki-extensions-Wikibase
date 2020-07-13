<?php

use MediaWiki\MediaWikiServices;
use Wikibase\Lib\Modules\RepoAccessModule;
use Wikibase\Lib\Modules\SitesModule;
use Wikibase\Lib\WikibaseSettings;

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
					WikibaseSettings::isClientEnabled() ? WikibaseSettings::getClientSettings() : null,
					WikibaseSettings::isRepoEnabled() ? WikibaseSettings::getRepoSettings() : null,
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

		'wikibase.api.RepoApi' => $wikibaseApiPaths + [
			'scripts' => [
				'namespace.js',
				'RepoApi.js',
				'getLocationAgnosticMwApi.js',
				'RepoApiError.js',
			],
			'dependencies' => [
				'mediawiki.api',
				'mediawiki.ForeignApi',
			],
			'messages' => [
				'wikibase-error-unexpected',
				'wikibase-error-unknown',
				'wikibase-error-save-generic',
				'wikibase-error-remove-generic',
				'wikibase-error-save-timeout',
				'wikibase-error-remove-timeout',
				'wikibase-error-ui-no-external-page',
				'wikibase-error-ui-edit-conflict',
			],
			'targets' => [
				'desktop',
				'mobile'
			]
		],

		'wikibase.api.ValueCaller' => $wikibaseApiPaths + [
			'scripts' => [
				'namespace.js',
				'ParseValueCaller.js',
				'FormatValueCaller.js',
			],
			'dependencies' => [
				'wikibase.api.RepoApi',
				'dataValues.DataValue',
			]
		],

	];

	return $modules;
} );
