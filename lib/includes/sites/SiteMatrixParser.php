<?php

/**
 * Translates api sitematrix results json into an array of Site objects
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class SiteMatrixParser {

	/**
	 * @var string
	 */
	protected $scriptPath;

	/**
	 * @var string
	 */
	protected $articlePath;

	/**
	 * @var boolean
	 */
	protected $expandGroup;

	/**
	 * @var string
	 */
	protected $stripProtocol;

	/**
	 * @param string $scriptPath (e.g. '/w/$1')
	 * @param string $articlePath (e.g. '/wiki/$1')
	 * @param string $stripProtocol
	 * @param boolean $expandGroup expands site matrix group codes from wiki to wikipedia
	 */
	public function __construct( $scriptPath, $articlePath, $stripProtocol, $expandGroup = true ) {
		$this->scriptPath = $scriptPath;
		$this->articlePath = $articlePath;
		$this->stripProtocol = $stripProtocol;
		$this->expandGroup = $expandGroup;
	}

	/**
	 * @param string $json
	 *
	 * @throws InvalidArgumentException
	 * @return Site[]
	 */
	public function sitesFromJson( $json ) {
		$data = $this->decodeJson( $json );
		$specialSites = $this->getSpecialSites( $data );

		$data = $this->removeSpecialsFromData( $data );
		$data = $this->removeCountFromData( $data );

		$sites = array();
		foreach( $data['sitematrix'] as $groupData ) {
			$sites = array_merge(
				$sites,
				$this->getSitesFromLangGroup( $groupData )
			);
		}

		return array_merge( $sites, $specialSites );
	}

	/**
	 * @param string $json
	 * @return array
	 * @throws InvalidArgumentException
	 */
	protected function decodeJson( $json ){
		$data = json_decode( $json, true );

		if ( !is_array( $data ) || !array_key_exists( 'sitematrix', $data ) ) {
			throw new InvalidArgumentException( 'Cannot decode site matrix data.' );
		}
		return $data;
	}

	/**
	 * @param array $data
	 * @return array
	 */
	protected function removeCountFromData( $data ){
		if ( array_key_exists( 'count', $data['sitematrix'] ) ) {
			unset( $data['sitematrix']['count'] );
		}
		return $data;
	}

	/**
	 * @param array $data
	 * @return array
	 */
	protected function removeSpecialsFromData( $data ){
		if ( array_key_exists( 'specials', $data['sitematrix'] ) ) {
			unset( $data['sitematrix']['specials'] );
		}
		return $data;
	}

	/**
	 * @param array $data
	 *
	 * @return Site[]
	 */
	protected function getSpecialSites( array $data ) {
		$sites = array();

		if ( array_key_exists( 'specials', $data['sitematrix'] ) ) {
			foreach( $data['sitematrix'] as $specialSite ) {
				$site = $this->getSiteFromSiteData( $specialSite );
				$siteId = $site->getGlobalId();

				// todo: get this from $wgConf
				$site->setLanguageCode( 'en' );

				$sites[$siteId] = $site;
			}
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
	protected function getSitesFromLangGroup( $langGroup ) {
		$sites = array();

		foreach( $langGroup['site'] as $siteData ) {
			if ( !array_key_exists( 'code', $langGroup ) ) {
				continue;
			}

			$site = $this->getSiteFromSiteData( $siteData, $langGroup['code'], false );
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
	protected function getSiteFromSiteData( $siteData ) {
		$site = new MediaWikiSite();
		$site->setGlobalId( $siteData['dbname'] );

		// @note: expandGroup is specific to wikimedia site matrix sources
		$siteGroup = ( $this->expandGroup && $siteData['code'] === 'wiki' )
			? 'wikipedia' : $siteData['code'];

		$site->setGroup( $siteGroup );

		$url = $siteData['url'];

		if ( $this->stripProtocol ) {
			$url = preg_replace( '@^https?:@', '', $url );
		}

		$site->setFilePath( $url . $this->scriptPath );
		$site->setPagePath( $url . $this->articlePath );

		return $site;
	}

}
