<?php

/**
 * Builds the site identifiers table
 *
 * @since 0.5
 * @note: this should move out of Wikibase
 *
 * @licence GNU GPL v2+
 *
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class SitesBuilder {

	/**
	 * @var SiteStore
	 */
	protected $store;

	public function __construct( SiteStore $store ) {
		$this->store = $store;
	}

	/**
	 * @param Site[] $sites
	 * @param string $siteGroup
	 * @param string $wikiId
	 */
	public function buildStore( array $sites, $siteGroup, $wikiId ) {
		$sites = $this->addInterwikiIdsToGroup( $sites, $siteGroup, $wikiId );

		$this->store->getSites( "nocache" );
		$this->store->saveSites( $sites );
	}

	/**
	 * @param Site[] $sites
	 * @param string $siteGroup
	 * @param string $wikiId
	 *
	 * @return Site[]
	 */
	public function addInterwikiIdsToGroup( array $sites, $siteGroup, $wikiId ) {
		if ( $siteGroup !== null ) {
			$sites = $this->addInterwikiIds( $sites, $siteGroup );
		} elseif ( is_string( $wikiId ) ) {
			$siteGroup = $this->getSiteGroupFromWikiId( $sites, $wikiId );
			$sites = $this->addInterwikiIds( $sites, $siteGroup );
		}

		return $sites;
	}

	/**
	 * @param Site[] $sites
	 * @param string $siteGroup
	 *
	 * @return Site[]
	 */
	protected function addInterwikiIds( array $sites, $siteGroup ) {
		foreach( $sites as $site ) {
			if( $site->getGroup() === $siteGroup ) {
				$localId = $site->getLanguageCode();

				if ( $localId ) {
					$site->addNavigationId( $localId );
					$site->addInterwikiId( $localId );
				}
			}
		}

		return $sites;
	}

	/**
	 * @param Site[] $sites
	 * @param string $wikiId
	 *
	 * @return string
	 */
	protected function getSiteGroupFromWikiId( $sites, $wikiId ) {
		if ( !array_key_exists( $wikiId, $sites ) ) {
			return null;
		}

		$site = $sites[$wikiId];

		return $site->getGroup();
	}

}
