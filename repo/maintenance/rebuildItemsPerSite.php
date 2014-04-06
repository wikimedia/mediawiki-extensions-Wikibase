<?php

namespace Wikibase;

use Wikibase\Repo\WikibaseRepo;
use LoggedUpdateMaintenance;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * Maintenance script for rebuilding the items_per_site table.
 *
 * @since 0.5
 *
 * @license GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
class RebuildItemsPerSite extends LoggedUpdateMaintenance {

	public function __construct() {
		parent::__construct();

		$this->mDescription = 'Rebuild the items_per_site table';

		$this->addOption( 'batch-size', "Number of rows to update per batch (100 by default)", false, true );
	}

	/**
	 * @see LoggedUpdateMaintenance::doDBUpdates
	 *
	 * @return boolean
	 */
	public function doDBUpdates() {
		if ( !defined( 'WB_VERSION' ) ) {
			$this->output( "You need to have Wikibase enabled in order to use this maintenance script!\n\n" );
			exit;
		}

		$batchSize = intval( $this->getOption( 'batch-size', 100 ) );

		$reporter = new \ObservableMessageReporter();
		$reporter->registerReporterCallback(
			array( $this, 'report' )
		);

		$siteLinkTable = new SiteLinkTable( 'wb_items_per_site', false );
		// Use an uncached EntityLookup here to avoid memory leaks
		$entityLookup = WikibaseRepo::getDefaultInstance()->getEntityLookup( 'uncached' );
		$builder = new ItemsPerSiteBuilder(
			$siteLinkTable,
			$entityLookup
		);

		$builder->setReporter( $reporter );

		$builder->setBatchSize( $batchSize );

		$entityPerPage = new EntityPerPageTable();
		$stream = new EntityPerPageIdPager( $entityPerPage, 'item' );
		$builder->rebuild( $stream );

		return true;
	}

	/**
	 * Outputs a message vis the output() method.
	 *
	 * @since 0.4
	 *
	 * @param $msg
	 */
	public function report( $msg ) {
		$this->output( "$msg\n" );
	}

	/**
	 * @see LoggedUpdateMaintenance::getUpdateKey
	 *
	 * @return string
	 */
	public function getUpdateKey() {
		return 'Wikibase\RebuildItemsPerSite';
	}

}

$maintClass = 'Wikibase\RebuildItemsPerSite';
require_once( RUN_MAINTENANCE_IF_MAIN );
