<?php

namespace Wikibase\Lib;

use MediaWikiSite;
use ResourceLoaderModule;
use Site;
use SiteList;
use SiteStore;
use Wikibase\SettingsArray;
use Xml;

/**
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 * @author Marius Hoch < hoo@online.de >
 * @author Adrian Heine < adrian.heine@wikimedia.de >
 */
class SitesModuleWorker {

	/**
	 * @var SettingsArray
	 */
	private $settings;

	/**
	 * @var SiteStore
	 */
	private $siteStore;

	/**
	 * @param SettingsArray $settings
	 * @param SiteStore $siteStore
	 */
	public function __construct( SettingsArray $settings, SiteStore $siteStore ) {
		$this->settings = $settings;
		$this->siteStore = $siteStore;
	}

	/**
	 * @return string[]
	 */
	private function getSiteLinkGroups() {
		return $this->settings->getSetting( 'siteLinkGroups' );
	}

	/**
	 * @return array
	 */
	private function getSpecialSiteLinkGroups() {
		return $this->settings->getSetting( 'specialSiteLinkGroups' );
	}

	/**
	 * @return SiteList
	 */
	private function getSites() {
		return $this->siteStore->getSites();
	}

	/**
	 * Get a hash representing the sites table. (This should change
	 * if eg. new sites get added to the sites table).
	 *
	 * @return string
	 */
	private function getSitesHash() {
		$data = '';
		$sites = (array) $this->getSites();
		sort( $sites );

		/**
		 * @var Site $site
		 */
		foreach ( $sites as $site ) {
			$data .= json_encode( (array) $site );
		}

		return sha1( $data );
	}

	/**
	 * Used to propagate information about sites to JavaScript.
	 * Sites infos will be available in 'wbSiteDetails' config var.
	 * @see ResourceLoaderModule::getScript
	 *
	 * @return string
	 */
	public function getScript() {
		$groups = $this->getSiteLinkGroups();
		$specialGroups = $this->getSpecialSiteLinkGroups();
		$specialPos = array_search( 'special', $groups );
		if ( $specialPos !== false ) {
			// The "special" group actually maps to multiple groups
			array_splice( $groups, $specialPos, 1, $specialGroups );
		}

		$siteDetails = array();
		/**
		 * @var Site $site
		 */
		foreach ( $this->getSites() as $site ) {
			if ( $this->shouldSiteBeIncluded( $site, $groups ) ) {
				$siteDetails[ $site->getGlobalId() ] = $this->getSiteDetails( $site, $specialGroups );
			}
		}

		return Xml::encodeJsCall( 'mediaWiki.config.set', array( 'wbSiteDetails', $siteDetails ) );
	}

	/**
	 * @param MediaWikiSite $site
	 * @param string[] $specialGroups
	 * @return string[]
	 */
	private function getSiteDetails( MediaWikiSite $site, array $specialGroups ) {
		$languageNameLookup = new LanguageNameLookup();

		$group = $site->getGroup();

		// FIXME: quickfix to allow a custom site-name / handling for the site groups which are
		// special according to the specialSiteLinkGroups setting
		if ( in_array( $group, $specialGroups ) ) {
			$languageNameMsg = wfMessage( 'wikibase-sitelinks-sitename-' . $site->getGlobalId() );
			$languageName = $languageNameMsg->exists() ? $languageNameMsg->parse() : $site->getGlobalId();
			$groupName = 'special';
		} else {
			$languageName = $languageNameLookup->getName( $site->getLanguageCode() );
			$groupName = $group;
		}

		// Use protocol relative URIs, as it's safe to assume that all wikis support the same protocol
		list( $pageUrl, $apiUrl ) = preg_replace(
			"/^https?:/i",
			'',
			array(
				$site->getPageUrl(),
				$site->getFileUrl( 'api.php' )
			)
		);

		//TODO: figure out which name is best
		//$localIds = $site->getLocalIds();
		//$name = empty( $localIds['equivalent'] ) ? $site->getGlobalId() : $localIds['equivalent'][0];

		return array(
				'shortName' => $languageName,
				'name' => $languageName, // use short name for both, for now
				'id' => $site->getGlobalId(),
				'pageUrl' => $pageUrl,
				'apiUrl' => $apiUrl,
				'languageCode' => $site->getLanguageCode(),
				'group' => $groupName
		);
	}

	/**
	 * Whether it's needed to add a Site to the JS variable.
	 *
	 * @param Site $site
	 * @param array $groups
	 *
	 * @return bool
	 */
	private function shouldSiteBeIncluded( Site $site, array $groups ) {
		return $site->getType() === Site::TYPE_MEDIAWIKI && in_array( $site->getGroup(), $groups );
	}

	/**
	 * @see ResourceLoaderModule::getModifiedHash
	 *
	 * @return string
	 */
	public function getModifiedHash() {
		$data = array(
				$this->getSiteLinkGroups(),
				$this->getSpecialSiteLinkGroups(),
				$this->getSitesHash()
		);

		return sha1( json_encode( $data ) );
	}

}
