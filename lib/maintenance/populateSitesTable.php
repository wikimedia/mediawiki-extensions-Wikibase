<?php

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';
require_once $basePath . '/maintenance/Maintenance.php';

/**
 * Maintenance script for populating the Sites table from another wiki that runs the
 * SiteMatrix extension.
 *
 * @since 0.1
 * @note: this should move out of Wikibase
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class PopulateSitesTable extends Maintenance {

	public function __construct() {
		$this->mDescription = 'Populate the sites table from another wiki that runs the SiteMatrix extension';

		$this->addOption( 'strip-protocols', "Strip http/https from URLs to make them protocol relative." );
		$this->addOption( 'load-from', "Full URL to the API of the wiki to fetch the site info from. "
				. "Default is https://meta.wikimedia.org/w/api.php", false, true );
		$this->addOption( 'site-group', 'Site group that this wiki is a member of.  Used to populate '
				. ' local interwiki identifiers in the site identifiers table.  If not set and --wiki'
				. ' is set, the script will try to determine which site group the wiki is part of'
				. ' and populate interwiki ids for sites in that group.', false, true );

		parent::__construct();
	}

	public function execute() {
		$stripProtocols = $this->getOption( 'strip-protocols' ) ? "stripProtocol" : false;
		$url = $this->getOption( 'load-from', 'https://meta.wikimedia.org/w/api.php' );
		$wikiId = $this->getOption( 'wiki' );
		$siteGroup = $this->getOption( 'site-group' );

		$groupMap = array(
			'wiki' => 'wikipedia',
			'wiktionary' => 'wiktionary',
			'wikibooks' => 'wikibooks',
			'wikiquote' => 'wikiquote',
			'wikisource' => 'wikisource',
			'wikiversity' => 'wikiversity',
			'wikivoyage' => 'wikivoyage',
			'wikinews' => 'wikinews',
		);

		$json = $this->getSiteMatrixData( $url );

		$siteMatrixParser = new \Wikibase\SiteMatrixParser( $groupMap, $stripProtocols );
		$sites = $siteMatrixParser->newFromJson( $json );

		if ( is_string( $siteGroup ) ) {
			$this->addInterwikiIds( $sites, $siteGroup );
		} elseif ( is_string( $wikiId ) ) {
			$siteGroup = $this->getSiteGroupFromWikiId( $sites, $wikiId );
			$this->addInterwikiIds( $sites, $siteGroup );
		}

		$store = SiteSQLStore::newInstance();
		$store->getSites( "nocache" );
		$store->saveSites( $sites );

		$this->output( "done.\n" );
	}

	protected function getSiteMatrixData( $url ) {
		$url .= '?action=sitematrix&format=json';

		//NOTE: the raiseException option needs change Iad3995a6 to be merged, otherwise it is ignored.
		$json = Http::get( $url, 'default', array( 'raiseException' => true ) );

		if ( !$json ) {
			throw new MWException( "Got no data from $url" );
		}

		return $json;
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

				$site->addNavigationId( $localId );
				$site->addInterwikiId( $localId );
			}
		}

		return $sites;
	}

	protected function getSiteGroupFromWikiId( $sites, $wikiId ) {
		if ( !array_key_exists( $wikiId, $sites ) ) {
			return null;
		}

		$site = $sites[$wikiId];

		return $site->getGroup();
	}

}

$maintClass = 'PopulateSitesTable';
require_once ( RUN_MAINTENANCE_IF_MAIN );
