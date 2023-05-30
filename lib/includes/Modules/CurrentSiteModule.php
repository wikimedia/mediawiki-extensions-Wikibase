<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Modules;

use BagOStuff;
// phpcs:disable MediaWiki.Classes.FullQualifiedClassName -- T308814
use MediaWiki\ResourceLoader as RL;
use MediaWiki\ResourceLoader\ResourceLoader;
use MediaWikiSite;
use MessageLocalizer;
use RuntimeException;
use SiteLookup;
use Wikibase\Lib\LanguageNameLookupFactory;
use Wikibase\Lib\SettingsArray;

/**
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 * @author Marius Hoch < hoo@online.de >
 */
class CurrentSiteModule extends SitesModuleBase {

	/**
	 * How many seconds the result of getSiteDetails() is cached.
	 */
	private const SITE_DETAILS_TTL = 3600; // 1 hour

	/**
	 * @var SiteLookup
	 */
	private $siteLookup;

	/**
	 * @var BagOStuff
	 */
	private $cache;

	/**
	 * @param SettingsArray|null $clientSettings The Client settings, if Client is enabled, else null.
	 * @param SettingsArray|null $repoSettings The Repo settings, if Repo is enabled, else null.
	 * @param SiteLookup $siteLookup
	 * @param BagOStuff $cache
	 * @param LanguageNameLookupFactory $languageNameLookupFactory
	 */
	public function __construct(
		?SettingsArray $clientSettings,
		?SettingsArray $repoSettings,
		SiteLookup $siteLookup,
		BagOStuff $cache,
		LanguageNameLookupFactory $languageNameLookupFactory
	) {
		parent::__construct(
			$clientSettings,
			$repoSettings,
			$languageNameLookupFactory
		);
		$this->siteLookup = $siteLookup;
		$this->cache = $cache;
	}

	/**
	 * Used to propagate information about sites to JavaScript.
	 * Sites infos will be available in 'wbCurrentSiteDetails' config var.
	 * @see RL\Module::getScript
	 *
	 * @param RL\Context $context
	 * @return string JavaScript Code
	 */
	public function getScript( RL\Context $context ): string {
		$languageCode = $context->getLanguage();
		return $this->cache->getWithSetCallback(
			$this->cache->makeKey( 'wikibase-current-site-module', 'script', $languageCode ),
			self::SITE_DETAILS_TTL,
			function () use ( $context ) {
				return $this->makeScript( $context );
			}
		);
	}

	/**
	 * @param MessageLocalizer $localizer
	 * @return string JavaScript Code
	 */
	protected function makeScript( MessageLocalizer $localizer ): string {

		$globalId = $this->getSetting( 'siteGlobalID' );

		$site = $this->siteLookup->getSite( $globalId );

		if ( $site === null ) {
			// provide empty site details if site is null, this is needed for freshly
			// installed MediaWikis and for tests that will otherwise crash if the site
			// is null
			$currentSiteDetails = [ 'id' => $globalId ];
		} elseif ( !( $site instanceof MediaWikiSite ) ) {
			throw new RuntimeException( 'the current site has to be of type MediaWikiSite' );
		} else {
			$specialGroups = $this->getSetting( 'specialSiteLinkGroups' );
			$currentSiteDetails = $this->computeSiteDetails(
				$site,
				$specialGroups,
				$localizer,
			);
		}
		return ResourceLoader::makeConfigSetScript( [ 'wbCurrentSiteDetails' => $currentSiteDetails ] );
	}

}
