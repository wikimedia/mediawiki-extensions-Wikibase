<?php

namespace Wikibase;

use FormatJson;
use Http;
use Language;
use MediaWikiSite;
use MWException;
use SiteList;
use SiteSQLStore;

/**
 * Builds the sites table
 *
 * @since 0.5
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Tobias Gritschacher
 * @author Jens Ohlig < jens.ohlig@wikimedia.de >
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class SitesTableBuilder {

	protected $groupMap;

	protected $sites;

	public function __construct( array $groupMap ) {
		$this->groupMap = $groupMap;
		$this->sites = SiteSQLStore::newInstance()->getSites( "nocache" );
	}

	/**
	 * Inserts sites from another wiki into the sites table. The other wiki must run the
	 * WikiMatrix extension. Existing entries in the sites table are not modified.
	 *
	 * @note This should move into core, together with the populateSitesTable.php script.
	 *
	 * @param String $url The URL of the API to fetch the sites from. Defaults
	 *   to 'https://meta.wikimedia.org/w/api.php'
	 *
	 * @param String|bool $stripProtocol Causes any leading http or https to be stripped
	 *   from URLs, forcing the remote sites to be references in a protocol-relative way.
	 *
	 * @throws MWException if an error occurs.
	 */
	public function insertSitesFrom( $url, $stripProtocol = false ) {
		$siteMatrix = $this->getSiteMatrix( $url );

		$newSites = array();

		// Inserting obtained sites...
		foreach ( $siteMatrix as $language ) {
			$newSites = array_merge(
				$newSites,
				$this->processSiteMatrixGroup( $language, $stripProtocol )
			);
		}

		$store = SiteSQLStore::newInstance();
		$store->saveSites( $newSites );

		wfWaitForSlaves();
	}

	protected function buildSiteFromData( $siteData, $languageCode, $stripProtocol ) {
		$site = new MediaWikiSite();
		$site->setGlobalId( $siteData['dbname'] );

		$site->setGroup( $this->groupMap[$siteData['code']] );
		$site->setLanguageCode( $languageCode );

		$localId = $siteData['code'] === 'wiki' ? $languageCode : $siteData['dbname'];
		$site->addInterwikiId( $localId );
		$site->addNavigationId( $localId );

		$url = $siteData['url'];

		if ( $stripProtocol === 'stripProtocol' ) {
			$url = preg_replace( '@^https?:@', '', $url );
		}

		$site->setFilePath( $url . '/w/$1' );
		$site->setPagePath( $url . '/wiki/$1' );

		return $site;
	}

	protected function getSiteMatrix( $url ) {
		$url .= '?action=sitematrix&format=json';

		//NOTE: the raiseException option needs change Iad3995a6 to be merged, otherwise it is ignored.
		$json = Http::get( $url, 'default', array( 'raiseException' => true ) );

		if ( !$json ) {
			throw new MWException( "Got no data from $url" );
		}

		$siteMatrixData = FormatJson::decode(
			$json,
			true
		);

		if ( !is_array( $siteMatrixData ) || !array_key_exists( 'sitematrix', $siteMatrixData ) ) {
			throw new MWException( "Failed to parse JSON from $url" );
		}

		return $siteMatrixData['sitematrix'];
	}

	protected function processSiteMatrixGroup( $matrixGroup, $stripProtocol ) {
		$newSites = array();

		if ( is_array( $matrixGroup ) && array_key_exists( 'code', $matrixGroup )
			&& array_key_exists( 'site', $matrixGroup ) ) {

			$languageCode = $matrixGroup['code'];

			foreach ( $matrixGroup['site'] as $siteData ) {
				if ( $this->sites->hasSite( $siteData['dbname'] ) ) {
					continue;
				}

				$newSites[] = $this->buildSiteFromData(
					$siteData,
					$languageCode,
					$stripProtocol
				);
			}
		}

		return $newSites;
	}

}
