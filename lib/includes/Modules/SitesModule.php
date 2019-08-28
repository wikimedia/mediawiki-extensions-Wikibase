<?php

namespace Wikibase;

use BagOStuff;
use MediaWikiSite;
use ResourceLoader;
use ResourceLoaderContext;
use ResourceLoaderModule;
use Site;
use SiteLookup;
use Wikibase\Lib\LanguageNameLookup;

/**
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 * @author Marius Hoch < hoo@online.de >
 */
class SitesModule extends ResourceLoaderModule {

	/**
	 * How many seconds the result of getSiteDetails() is cached.
	 */
	const SITE_DETAILS_TTL = 3600; // 1 hour

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
	 * Used to propagate information about sites to JavaScript.
	 * Sites infos will be available in 'wbSiteDetails' config var.
	 * @see ResourceLoaderModule::getScript
	 *
	 * @param ResourceLoaderContext $context
	 * @return string JavaScript Code
	 */
	public function getScript( ResourceLoaderContext $context ) {
		$languageCode = $context->getLanguage();
		return $this->cache->getWithSetCallback(
			$this->cache->makeKey( 'wikibase-sites-module', 'script', $languageCode ),
			self::SITE_DETAILS_TTL,
			function () use ( $languageCode ) {
					return $this->makeScript( $languageCode );
			}
		);
	}

	/** @return bool */
	public function enableModuleContentVersion() {
		// Let ResourceLoaderModule::getVersionHash() invoke getScript() and hash that.
		return true;
	}

	/**
	 * @param string $languageCode
	 * @return string JavaScript Code
	 */
	protected function makeScript( $languageCode ) {
		$groups = $this->settings->getSetting( 'siteLinkGroups' );
		$specialGroups = $this->settings->getSetting( 'specialSiteLinkGroups' );
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
	 * @return string[]
	 */
	private function computeSiteDetails( MediaWikiSite $site, array $specialGroups, $languageCode ) {
		$languageNameLookup = new LanguageNameLookup();

		// FIXME: quickfix to allow a custom site-name / handling for the site groups which are
		// special according to the specialSiteLinkGroups setting
		$group = $site->getGroup();
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
	 * @return bool
	 */
	private function shouldSiteBeIncluded( Site $site, array $groups ) {
		return $site->getType() === Site::TYPE_MEDIAWIKI && in_array( $site->getGroup(), $groups );
	}

}
