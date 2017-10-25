<?php

namespace Wikibase\Repo\Maintenance;

use Maintenance;
use Wikibase\Lib\Reporting\ObservableMessageReporter;
use Wikibase\Lib\Store\Sql\SiteLinkTable;
use Wikibase\Repo\Store\Sql\SqlEntityIdPager;
use Wikibase\Repo\Store\Sql\ItemsPerSiteBuilder;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\WikibaseSettings;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * Maintenance script for rebuilding the items_per_site table.
 *
 * @license GPL-2.0+
 * @author Marius Hoch < hoo@online.de >
 */
class RebuildItemsPerSite extends Maintenance {

	public function __construct() {
		parent::__construct();

		$this->addDescription( 'Rebuild the items_per_site table' );

		$this->addOption( 'batch-size', "Number of rows to update per batch (100 by default)", false, true );
	}

	/**
	 * @see Maintenance::execute
	 */
	public function execute() {
		if ( !WikibaseSettings::isRepoEnabled() ) {
			$this->output( "You need to have Wikibase enabled in order to use this maintenance script!\n\n" );
			exit;
		}

		$batchSize = (int)$this->getOption( 'batch-size', 100 );

		$reporter = new ObservableMessageReporter();
		$reporter->registerReporterCallback(
			[ $this, 'report' ]
		);

		$siteLinkTable = new SiteLinkTable( 'wb_items_per_site', false );
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		// Use an uncached EntityLookup here to avoid memory leaks
		$entityLookup = $wikibaseRepo->getEntityLookup( 'retrieve-only' );
		$store = $wikibaseRepo->getStore();
		$builder = new ItemsPerSiteBuilder(
			$siteLinkTable,
			$entityLookup,
			$store->getEntityPrefetcher()
		);

		$builder->setReporter( $reporter );
		$builder->setBatchSize( $batchSize );

		$stream = new SqlEntityIdPager(
			$wikibaseRepo->getEntityNamespaceLookup(),
			$wikibaseRepo->getEntityIdParser(),
			'item'
		);

		// Now <s>kill</s> fix the table
		$builder->rebuild( $stream );
	}

	/**
	 * Outputs a message vis the output() method.
	 *
	 * @param string $msg
	 */
	public function report( $msg ) {
		$this->output( "$msg\n" );
	}

}

$maintClass = RebuildItemsPerSite::class;
require_once RUN_MAINTENANCE_IF_MAIN;
