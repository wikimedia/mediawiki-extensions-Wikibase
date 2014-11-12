<?php

namespace Wikibase;
use MediaWikiSite;
use ResourceLoaderContext;
use ResourceLoaderModule;
use Site;
use SiteSQLStore;
use Xml;

/**
 *
 * @since 0.2
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 * @author Marius Hoch < hoo@online.de >
 */
class SitesModule extends ResourceLoaderModule {

	/**
	 * @return string[]
	 */
	private function getSiteLinkGroups() {
		return Settings::get( "siteLinkGroups" );
	}

	/**
	 * @return array
	 */
	private function getSpecialSiteLinkGroups() {
		return Settings::get( "specialSiteLinkGroups" );
	}

	/**
	 * Get a hash representing the sites table. (This should change
	 * if eg. new sites get added to the sites table).
	 *
	 * @return string
	 */
	private function getSitesHash() {
		$data = '';
		$sites = (array)SiteSQLStore::newInstance()->getSites();
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
	 * @since 0.2
	 *
	 * @param ResourceLoaderContext $context
	 *
	 * @return string
	 */
	public function getScript( ResourceLoaderContext $context ) {
		$sites = array();

		$groups = $this->getSiteLinkGroups();
		$specialGroups = $this->getSpecialSiteLinkGroups();

		/**
		 * @var MediaWikiSite $site
		 */
		foreach ( SiteSQLStore::newInstance()->getSites() as $site ) {
			$group = $site->getGroup();

			if ( !$this->shouldSiteBeIncluded( $site, $groups, $specialGroups ) ) {
				continue;
			}

			// FIXME: quickfix to allow a custom site-name / handling for the site groups which are
			// special according to the specialSiteLinkGroups setting
			if ( in_array( $group, $specialGroups ) ) {
				$languageNameMsg = wfMessage( 'wikibase-sitelinks-sitename-' . $site->getGlobalId() );
				$languageName = $languageNameMsg->exists() ? $languageNameMsg->parse() : $site->getGlobalId();
				$groupName = 'special';
			} else {
				$languageName = Utils::fetchLanguageName( $site->getLanguageCode() );
				$groupName = $group;
			}
			$globalId = $site->getGlobalId();

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

			$sites[ $globalId ] = array(
				'shortName' => $languageName,
				'name' => $languageName, // use short name for both, for now
				'id' => $globalId,
				'pageUrl' => $pageUrl,
				'apiUrl' => $apiUrl,
				'languageCode' => $site->getLanguageCode(),
				'group' => $groupName
			);
		}

		return Xml::encodeJsCall( 'mediaWiki.config.set', array( 'wbSiteDetails', $sites ) );
	}

	/**
	 * Whether it's needed to add a Site to the JS variable.
	 *
	 * @param Site $site
	 * @param array $groups
	 * @param array $specialGroups
	 *
	 * @return bool
	 */
	private function shouldSiteBeIncluded( Site $site, array $groups, array $specialGroups ) {
		if ( in_array( 'special', $groups ) ) {
			// The "special" group actually maps to multiple groups
			$groups = array_diff( $groups, array( 'special' ) );
			$groups = array_merge( $groups, $specialGroups );
		}

		if ( $site->getType() === Site::TYPE_MEDIAWIKI && in_array( $site->getGroup(), $groups ) ) {
			return true;
		}

		return false;
	}

	/**
	 * @see ResourceLoaderModule::getModifiedHash
	 *
	 * @param ResourceLoaderContext $context
	 *
	 * @return string
	 */
	public function getModifiedHash( ResourceLoaderContext $context ) {
		$data = array(
			$this->getSiteLinkGroups(),
			$this->getSpecialSiteLinkGroups(),
			$this->getSitesHash()
		);

		return sha1( json_encode( $data ) );
	}

	/**
	 * @see ResourceLoaderModule::getModifiedTime
	 *
	 * @param ResourceLoaderContext $context
	 *
	 * @return int
	 */
	public function getModifiedTime( ResourceLoaderContext $context ) {
		return $this->getHashMtime( $context );
	}
}
