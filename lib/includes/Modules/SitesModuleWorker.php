<?php

namespace Wikibase\Lib;

use BagOStuff;
use MediaWikiSite;
use ResourceLoader;
use Site;
use SiteList;
use SiteLookup;
use Wikibase\SettingsArray;

/**
 * @license GPL-2.0-or-later
 */
class SitesModuleWorker {

	/**
	 * How many seconds the result of getSiteDetails() is cached.
	 */
	const SITE_DETAILS_TTL = 600; // 10 minutes

	/**
	 * @var SettingsArray
	 */
	private $settings;

	/**
	 * @var SiteLookup
	 */
	private $siteLookup;

	/**
	 * @var BagOStuff
	 */
	private $cache;

	public function __construct( SettingsArray $settings, SiteLookup $siteLookup, BagOStuff $cache ) {
		$this->settings = $settings;
		$this->siteLookup = $siteLookup;
		$this->cache = $cache;
	}

	/**
	 * @return string[]
	 */
	private function getSiteLinkGroups() {
		return $this->settings->getSetting( 'siteLinkGroups' );
	}

	/**
	 * @return string[]
	 */
	private function getSpecialSiteLinkGroups() {
		return $this->settings->getSetting( 'specialSiteLinkGroups' );
	}

	/**
	 * @return SiteList
	 */
	private function getSites() {
		return $this->siteLookup->getSites();
	}

	/**
	 * Used to propagate information about sites to JavaScript.
	 * Sites infos will be available in 'wbSiteDetails' config var.
	 *
	 * @param string $languageCode
	 * @return string JavaScript Code
	 */
	public function getScript( $languageCode ) {
		return $this->cache->getWithSetCallback(
			$this->cache->makeKey( 'wikibase-sites-module', 'script', $languageCode ),
			self::SITE_DETAILS_TTL,
			function () use ( $languageCode ) {
					return $this->makeScript( $languageCode );
			}
		);
	}

	/**
	 * @see ResourceLoaderModule::getScript
	 * @param string $languageCode
	 * @return string JavaScript Code
	 */
	protected function makeScript( $languageCode ) {
		$groups = $this->getSiteLinkGroups();
		$specialGroups = $this->getSpecialSiteLinkGroups();
		$specialPos = array_search( 'special', $groups );
		if ( $specialPos !== false ) {
			// The "special" group actually maps to multiple groups
			array_splice( $groups, $specialPos, 1, $specialGroups );
		}

		$siteDetails = [];
		/**
		 * @var MediaWikiSite $site
		 */
		foreach ( $this->getSites() as $site ) {
			if ( $this->shouldSiteBeIncluded( $site, $groups ) ) {
				$siteDetails[$site->getGlobalId()] = $this->computeSiteDetails(
					$site,
					$specialGroups,
					$languageCode
				);
			}
		}

		return ResourceLoader::makeConfigSetScript( [ 'wbSiteDetails' => $siteDetails ] );
	}

	/**
	 * @param MediaWikiSite $site
	 * @param string[] $specialGroups
	 * @param string $languageCode
	 *
	 * @return string[]
	 */
	private function computeSiteDetails( MediaWikiSite $site, array $specialGroups, $languageCode ) {
		$languageNameLookup = new LanguageNameLookup();

		$group = $site->getGroup();

		// FIXME: quickfix to allow a custom site-name / handling for the site groups which are
		// special according to the specialSiteLinkGroups setting
		if ( in_array( $group, $specialGroups ) ) {
			$languageName = $this->getSpecialSiteLanguageName( $site, $languageCode );
			$groupName = 'special';
		} else {
			$languageName = $languageNameLookup->getName( $site->getLanguageCode() );
			$groupName = $group;
		}

		// Use protocol relative URIs, as it's safe to assume that all wikis support the same protocol
		list( $pageUrl, $apiUrl ) = preg_replace(
			"/^https?:/i",
			'',
			[
				$site->getPageUrl(),
				$site->getFileUrl( 'api.php' )
			]
		);

		//TODO: figure out which name is best
		//$localIds = $site->getLocalIds();
		//$name = empty( $localIds['equivalent'] ) ? $site->getGlobalId() : $localIds['equivalent'][0];

		return [
				'shortName' => $languageName,
				'name' => $languageName, // use short name for both, for now
				'id' => $site->getGlobalId(),
				'pageUrl' => $pageUrl,
				'apiUrl' => $apiUrl,
				'languageCode' => $site->getLanguageCode(),
				'group' => $groupName
		];
	}

	/**
	 * @param Site $site
	 * @param string $languageCode
	 *
	 * @return string
	 */
	private function getSpecialSiteLanguageName( Site $site, $languageCode ) {
		$siteId = $site->getGlobalId();
		$messageKey = 'wikibase-sitelinks-sitename-' . $siteId;

		// @note: inLanguage needs to be called before exists and parse. See: T127872.
		$languageNameMsg = wfMessage( $messageKey )->inLanguage( $languageCode );

		return $languageNameMsg->exists() ? $languageNameMsg->parse() : $siteId;
	}

	/**
	 * Whether it's needed to add a Site to the JS variable.
	 *
	 * @param Site $site
	 * @param string[] $groups
	 *
	 * @return bool
	 */
	private function shouldSiteBeIncluded( Site $site, array $groups ) {
		return $site->getType() === Site::TYPE_MEDIAWIKI && in_array( $site->getGroup(), $groups );
	}

}
