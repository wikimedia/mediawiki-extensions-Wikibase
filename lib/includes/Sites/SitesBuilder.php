<?php

namespace Wikibase\Lib\Sites;

use Site;
use SiteStore;

/**
 * Builds the site identifiers table
 *
 * @note: this should move out of Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class SitesBuilder {

	/**
	 * @var SiteStore
	 */
	private $store;

	/**
	 * @var string[]
	 */
	private $validGroups;

	/**
	 * @param SiteStore $store
	 * @param string[] $validGroups
	 */
	public function __construct( SiteStore $store, array $validGroups ) {
		$this->store = $store;
		$this->validGroups = $validGroups;
	}

	/**
	 * @param Site[] $sites
	 * @param string|null $siteGroup
	 * @param string|null $wikiId
	 */
	public function buildStore( array $sites, $siteGroup = null, $wikiId = null ) {
		if ( $siteGroup === null && is_string( $wikiId ) ) {
			$siteGroup = $this->getInterwikiGroup( $sites, $wikiId );
		}

		if ( $siteGroup && in_array( $siteGroup, $this->validGroups ) ) {
			$sites = $this->addInterwikiIdsToGroup( $sites, $siteGroup );
		}

		$existingSites = $this->store->getSites();

		foreach ( $sites as $site ) {
			$siteId = $site->getGlobalId();

			if ( $existingSites->hasSite( $siteId ) ) {
				$existingSite = $existingSites->getSite( $siteId );
				$site->setInternalId( $existingSite->getInternalId() );
			}
		}

		$this->store->saveSites( $sites );
	}

	/**
	 * @param Site[] $sites
	 * @param string $siteGroup
	 *
	 * @return Site[]
	 */
	protected function addInterwikiIdsToGroup( array $sites, $siteGroup ) {
		foreach ( $sites as $site ) {
			if ( $site->getGroup() === $siteGroup ) {
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
	private function getInterwikiGroup( array $sites, $wikiId ) {
		if ( !array_key_exists( $wikiId, $sites ) ) {
			return null;
		}

		$site = $sites[$wikiId];

		// @fixme: handle interwiki prefixes in a better way!
		if ( preg_match( '/^([\w-]*)wiki$/', $site->getGlobalId() ) ) {
			return 'wikipedia';
		}

		return $site->getGroup();
	}

}
