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
	];

	return $modules;
} );
