<?php

namespace Wikibase;

use InvalidArgumentException;
use MediaWikiSite;

/**
 * @since 0.5
 *
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 *
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class SiteMatrixParser {

	protected $groupMap;

	protected $stripProtocol;

	public function __construct( array $groupMap, $stripProtocol ) {
		$this->groupMap = $groupMap;
		$this->stripProtocol = $stripProtocol;
	}

	/**
	 * @param string $json
	 * @param string $wikiId
	 *
	 * @return Site[]
	 */
	public function newFromJson( $json, $wiki = null ) {
		$specials = null;

		$data = json_decode( $json, true );

		if ( !is_array( $data ) || !array_key_exists( 'sitematrix', $data ) ) {
			throw new InvalidArgumentException( 'Cannot decode site matrix data.' );
		}

		if ( array_key_exists( 'specials', $data['sitematrix'] ) ) {
			$specials = $data['sitematrix']['specials'];
			unset( $data['sitematrix']['specials'] );
		}

		if ( array_key_exists( 'count', $data['sitematrix'] ) ) {
			unset( $data['sitematrix']['count'] );
		}

		$groups = $data['sitematrix'];

		$sites = array();

		foreach( $groups as $groupData ) {
			$sites = array_merge(
				$this->getLangGroupFromMatrix( $groupData ),
				$sites
			);
		}

		if ( is_string( $wiki ) ) {
			$sites = $this->addInterwikiIds( $sites, $wiki );
		}

		return $sites;
	}

	/**
	 * @param array $groupData
	 *
	 * @return Site[]
	 */
	protected function getLangGroupFromMatrix( $groupData ) {
		$sites = array();

		foreach( $groupData['site'] as $siteData ) {
			$site = $this->getSiteFromMatrix( $siteData, $groupData['code'], false );
			$site->setLanguageCode( $groupData['code'] );
			$siteId = $site->getGlobalId();
			$sites[$siteId] = $site;
		}

		return $sites;
	}

	/**
	 * @param array $siteData
	 * @param string $langCode
	 *
	 * @return Site
	 */
	protected function getSiteFromMatrix( $siteData ) {
		$site = new MediaWikiSite();
		$site->setGlobalId( $siteData['dbname'] );

		$site->setGroup( $this->groupMap[$siteData['code']] );

		$url = $siteData['url'];

		if ( $this->stripProtocol === 'stripProtocol' ) {
			$url = preg_replace( '@^https?:@', '', $url );
		}

		$site->setFilePath( $url . '/w/$1' );
		$site->setPagePath( $url . '/wiki/$1' );

		return $site;
	}

	/**
	 * @param Site[] $sites
	 * @param string $siteId
	 *
	 * @return Site[]
	 */
	protected function addInterwikiIds( array $sites, $siteId ) {
		if ( !array_key_exists( $siteId, $sites ) ) {
			return $sites;
		}

		$thisSite = $sites[$siteId];
		$siteGroup = $thisSite->getGroup();

		foreach( $sites as $site ) {
			if( $site->getGroup() === $siteGroup ) {
				$localId = $site->getLanguageCode();

				$site->addNavigationId( $localId );
				$site->addInterwikiId( $localId );
			}
		}

		return $sites;
	}

}


