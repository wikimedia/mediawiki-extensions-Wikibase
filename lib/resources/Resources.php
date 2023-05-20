<?php

use MediaWiki\MediaWikiServices;
use Wikibase\Client\WikibaseClient;
use Wikibase\Lib\LanguageNameLookupFactory;
use Wikibase\Lib\Modules\RepoAccessModule;
use Wikibase\Lib\Modules\SitesModule;
use Wikibase\Lib\WikibaseSettings;
use Wikibase\Repo\WikibaseRepo;

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

	$modules = [

		'mw.config.values.wbSiteDetails' => $moduleTemplate + [
			'factory' => function () {
				return new SitesModule(
					WikibaseSettings::isClientEnabled() ? WikibaseClient::getSettings() : null,
					WikibaseSettings::isRepoEnabled() ? WikibaseRepo::getSettings() : null,
					MediaWikiServices::getInstance()->getSiteStore(),
					MediaWikiServices::getInstance()->getLocalServerObjectCache(),
					new LanguageNameLookupFactory( MediaWikiServices::getInstance()->getLanguageNameUtils() )
				);
			},
		],
		'mw.config.values.wbRepo' => $moduleTemplate + [
			'class' => RepoAccessModule::class,
		],

		// all of the following modules should really be in view (repo-only),
		// but were temporarily moved to lib to unbreak T337081

		'wikibase' => $moduleTemplate + [
			'scripts' => [
				'wikibase.js',
			],
		],
		'wikibase.buildErrorOutput' => $moduleTemplate + [
			'scripts' => [
				'wikibase/wikibase.buildErrorOutput.js',
			],
			'dependencies' => [
				'wikibase',
			],
		],
		'jquery.wikibase.wbtooltip' => $moduleTemplate + [
			'scripts' => [
				'jquery/wikibase/jquery.tipsy/jquery.tipsy.js',
				'jquery/wikibase/jquery.wikibase.wbtooltip.js',
			],
			'styles' => [
				'jquery/wikibase/jquery.tipsy/jquery.tipsy.css',
				'jquery/wikibase/themes/default/jquery.wikibase.wbtooltip.css',
			],
			'dependencies' => [
				'jquery.ui',
				'wikibase.buildErrorOutput',
			],
		],
	];

	return $modules;
} );
