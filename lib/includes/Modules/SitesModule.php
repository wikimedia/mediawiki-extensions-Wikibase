<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Modules;

use MediaWiki\ResourceLoader as RL;
use MediaWiki\Site\MediaWikiSite;
use MediaWiki\Site\Site;
use MediaWiki\Site\SiteLookup;
use Wikibase\Lib\LanguageNameLookupFactory;
use Wikibase\Lib\SettingsArray;
use Wikimedia\ObjectCache\BagOStuff;

// phpcs:disable MediaWiki.Classes.FullQualifiedClassName -- T308814

/**
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 * @author Marius Hoch < hoo@online.de >
 * @author Guergana Tzatchkova < pestanias@yahoo.com >
 */
class SitesModule extends SitesModuleBase {

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
	 * Sites infos will be available in 'wbSiteDetails' config var.
	 * @see RL\Module::getScript
	 *
	 * @param RL\Context $context
	 * @return string JavaScript Code
	 */
	public function getScript( RL\Context $context ): string {
		$languageCode = $context->getLanguage();
		return $this->cache->getWithSetCallback(
			$this->cache->makeKey( 'wikibase-sites-module', 'script', $languageCode ),
			self::SITE_DETAILS_TTL,
			function () use ( $context ) {
				return $this->makeScript( $context );
			}
		);
	}

	/**
	 * @param RL\Context $context
	 * @return string JavaScript Code
	 */
	protected function makeScript( RL\Context $context ): string {
		$groups = $this->getSetting( 'siteLinkGroups' );
		$specialGroups = $this->getSetting( 'specialSiteLinkGroups' );
		$specialPos = array_search( 'special', $groups );
		if ( $specialPos !== false ) {
			// The "special" group actually maps to multiple groups
			array_splice( $groups, $specialPos, 1, $specialGroups );
		}

		$siteDetails = [];
		/**
		 * @var MediaWikiSite $site
		 */
		foreach ( $this->siteLookup->getSites() as $site ) {
			if ( $this->shouldSiteBeIncluded( $site, $groups ) ) {
				$siteDetails[$site->getGlobalId()] = $this->computeSiteDetails(
					$site,
					$specialGroups,
					$context,
				);
			}
		}

		return 'mw.config.set('
				. $context->encodeJson( [ 'wbSiteDetails' => $siteDetails ] )
				. ');';
	}

	/**
	 * Whether it's needed to add a Site to the JS variable.
	 *
	 * @param Site $site
	 * @param string[] $groups
	 * @return bool
	 */
	private function shouldSiteBeIncluded( Site $site, array $groups ): bool {
		return $site->getType() === Site::TYPE_MEDIAWIKI && in_array( $site->getGroup(), $groups );
	}

}
