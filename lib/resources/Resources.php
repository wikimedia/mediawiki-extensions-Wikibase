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
	];

	return $modules;
} );
