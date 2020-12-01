<?php

namespace Wikibase\Lib\Sites;

use InvalidArgumentException;
use MediaWikiSite;
use Site;

/**
 * Translates api sitematrix results json into an array of Site objects
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class SiteMatrixParser {

	/**
	 * @var string
	 */
	private $scriptPath;

	/**
	 * @var string
	 */
	private $articlePath;

	/**
	 * @var bool
	 */
	private $expandGroup;

	/**
	 * @var bool|string
	 */
	private $protocol;

	/**
	 * @param string $scriptPath (e.g. '/w/$1')
	 * @param string $articlePath (e.g. '/wiki/$1')
	 * @param string|bool $protocol (true: default, false: strip, string: protocol to force)
	 * @param boolean $expandGroup expands site matrix group codes from wiki to wikipedia
	 */
	public function __construct( $scriptPath, $articlePath, $protocol, $expandGroup = true ) {
		$this->scriptPath = $scriptPath;
		$this->articlePath = $articlePath;
		$this->protocol = $protocol;
		$this->expandGroup = $expandGroup;
	}

	/**
	 * @param string $json
	 *
	 * @throws InvalidArgumentException
	 * @return Site[]
	 */
	public function sitesFromJson( $json ) {
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

		$sites = [];

		foreach ( $groups as $groupData ) {
			$sites = array_merge(
				$sites,
				$this->getSitesFromLangGroup( $groupData )
			);
		}

		$sites = array_merge(
			$sites,
			$this->getSpecialSites( $specials )
		);

		return $sites;
	}

	/**
	 * @param array[] $specialSites
	 *
	 * @return Site[]
	 */
	private function getSpecialSites( array $specialSites ) {
		$sites = [];

		foreach ( $specialSites as $specialSite ) {
			$site = $this->getSiteFromSiteData( $specialSite );
			$siteId = $site->getGlobalId();

			// todo: get this from $wgConf
			$site->setLanguageCode( 'en' );

			$site->addInterwikiId( $specialSite['code'] );

			$sites[$siteId] = $site;
		}

		return $sites;
	}

	/**
	 * Gets an array of Site objects for all sites of the same language
	 * subdomain grouping used in the site matrix.
	 *
	 * @param array $langGroup
	 *
	 * @return Site[]
	 */
	private function getSitesFromLangGroup( array $langGroup ) {
		$sites = [];

		foreach ( $langGroup['site'] as $siteData ) {
			if ( !array_key_exists( 'code', $langGroup ) ) {
				continue;
			}

			$site = $this->getSiteFromSiteData( $siteData );
			$site->setLanguageCode( $langGroup['code'] );
			$siteId = $site->getGlobalId();
			$sites[$siteId] = $site;
		}

		return $sites;
	}

	/**
	 * @param array $siteData
	 *
	 * @return Site
	 */
	private function getSiteFromSiteData( array $siteData ) {
		$site = new MediaWikiSite();
		$site->setGlobalId( $siteData['dbname'] );

		// @note: expandGroup is specific to wikimedia site matrix sources
		$siteGroup = ( $this->expandGroup && $siteData['code'] === 'wiki' )
			? 'wikipedia' : $siteData['code'];

		$site->setGroup( $siteGroup );

		$url = $siteData['url'];

		if ( $this->protocol === false ) {
			$url = preg_replace( '@^https?:@', '', $url );
		} elseif ( is_string( $this->protocol ) ) {
			$url = preg_replace( '@^https?:@', $this->protocol . ':', $url );
		}

		$site->setFilePath( $url . $this->scriptPath );
		$site->setPagePath( $url . $this->articlePath );

		return $site;
	}

}
