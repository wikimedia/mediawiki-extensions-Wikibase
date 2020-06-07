<?php

namespace Wikibase\Lib\Maintenance;

use Maintenance;
use MediaWiki\MediaWikiServices;
use MWException;
use Wikibase\Lib\Sites\SiteMatrixParser;
use Wikibase\Lib\Sites\SitesBuilder;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false
	? getenv( 'MW_INSTALL_PATH' )
	: __DIR__ . '/../../../..';

require_once $basePath . '/maintenance/Maintenance.php';

if ( !class_exists( SitesBuilder::class ) ) {
	require_once __DIR__ . '/../includes/Sites/SitesBuilder.php';
}

if ( !class_exists( SiteMatrixParser::class ) ) {
	require_once __DIR__ . '/../includes/Sites/SiteMatrixParser.php';
}

/**
 * Maintenance script for populating the Sites table from another wiki that runs the
 * SiteMatrix extension.
 *
 * @note: this should move out of Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class PopulateSitesTable extends Maintenance {

	public function __construct() {
		parent::__construct();

		$this->addDescription( 'Populate the sites table from another wiki that runs the SiteMatrix extension' );

		$this->addOption( 'strip-protocols', "Strip http/https from URLs to make them protocol relative." );
		$this->addOption( 'force-protocol', "Force a specific protocol for all URLs (like http/https).", false, true );
		$this->addOption( 'load-from', "Full URL to the API of the wiki to fetch the site info from. "
				. "Default is https://meta.wikimedia.org/w/api.php", false, true );
		$this->addOption( 'script-path', 'Script path to use for wikis in the site matrix. '
				. ' (e.g. "/w/$1")', false, true );
		$this->addOption( 'article-path', 'Article path for wikis in the site matrix. '
				. ' (e.g. "/wiki/$1")', false, true );
		$this->addOption( 'site-group', 'Site group that this wiki is a member of.  Used to populate '
				. ' local interwiki identifiers in the site identifiers table.  If not set and --wiki'
				. ' is set, the script will try to determine which site group the wiki is part of'
				. ' and populate interwiki ids for sites in that group.', false, true );
		$this->addOption( 'no-expand-group', 'Do not expand site group codes in site matrix. '
				. ' By default, "wiki" is expanded to "wikipedia".' );
	}

	public function execute() {
		$stripProtocols = (bool)$this->getOption( 'strip-protocols', false );
		$forceProtocol = $this->getOption( 'force-protocol', null );
		$url = $this->getOption( 'load-from', 'https://meta.wikimedia.org/w/api.php' );
		$scriptPath = $this->getOption( 'script-path', '/w/$1' );
		$articlePath = $this->getOption( 'article-path', '/wiki/$1' );
		$expandGroup = !$this->getOption( 'no-expand-group', false );
		$siteGroup = $this->getOption( 'site-group' );
		$wikiId = $this->getOption( 'wiki' );

		if ( $stripProtocols && is_string( $forceProtocol ) ) {
			$this->fatalError( "You can't use both strip-protocols and force-protocol" );
		}

		$protocol = true;
		if ( $stripProtocols ) {
			$protocol = false;
		} elseif ( is_string( $forceProtocol ) ) {
			$protocol = $forceProtocol;
		}

		// @todo make it configurable, such as from a config file.
		$validGroups = [ 'wikipedia', 'wikivoyage', 'wikiquote', 'wiktionary',
			'wikibooks', 'wikisource', 'wikiversity', 'wikinews' ];

		try {
			$json = $this->getSiteMatrixData( $url );

			$siteMatrixParser = new SiteMatrixParser( $scriptPath, $articlePath,
				$protocol, $expandGroup );

			$sites = $siteMatrixParser->sitesFromJson( $json );

			$store = MediaWikiServices::getInstance()->getSiteStore();
			$sitesBuilder = new SitesBuilder( $store, $validGroups );
			$sitesBuilder->buildStore( $sites, $siteGroup, $wikiId );

		} catch ( MWException $e ) {
			$this->output( $e->getMessage() );
		}

		$this->output( "done.\n" );
	}

	/**
	 * @param string $url
	 *
	 * @throws MWException
	 * @return string
	 */
	protected function getSiteMatrixData( $url ) {
		$url .= '?action=sitematrix&format=json';

		$json = MediaWikiServices::getInstance()->getHttpRequestFactory()->get( $url, [], __METHOD__ );

		if ( !$json ) {
			throw new MWException( "Got no data from $url\n" );
		}

		return $json;
	}

}

$maintClass = PopulateSitesTable::class;
require_once RUN_MAINTENANCE_IF_MAIN;
