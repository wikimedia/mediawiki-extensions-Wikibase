<?php

namespace Wikibase\Repo\Maintenance;

use LoggedUpdateMaintenance;
use Wikibase\Lib\Reporting\ObservableMessageReporter;
use Wikibase\Repo\Store\SQL\EntityPerPageBuilder;
use Wikibase\Repo\WikibaseRepo;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * Maintenance script for rebuilding the items_per_page table.
 *
 * @since 0.3
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class RebuildEntityPerPage extends LoggedUpdateMaintenance {

	public function __construct() {
		parent::__construct();

		$this->mDescription = 'Rebuild the entites_per_page table';

		$this->addOption( 'rebuild-all', "Rebuild the entire table (per default, only missing entries are rebuild)" );
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
		$rebuildAll = $this->getOption( 'rebuild-all', false );

		$reporter = new ObservableMessageReporter();
		$reporter->registerReporterCallback(
			array( $this, 'report' )
		);

		$entityPerPageTable = WikibaseRepo::getDefaultInstance()->getStore()->newEntityPerPage();
		$wikibaseRepo =  WikibaseRepo::getDefaultInstance();
		$entityIdParser = $wikibaseRepo->getEntityIdParser();
		$contentModels = $wikibaseRepo->getContentModelMappings();

		$builder = new EntityPerPageBuilder(
			$entityPerPageTable,
			$entityIdParser,
			$contentModels
		);

		$builder->setReporter( $reporter );

		$builder->setBatchSize( $batchSize );
		$builder->setRebuildAll( $rebuildAll );

		$builder->rebuild();

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
		return 'Wikibase\Repo\Maintenance\RebuildEntityPerPage';
	}

}

$maintClass = 'Wikibase\Repo\Maintenance\RebuildEntityPerPage';
require_once( RUN_MAINTENANCE_IF_MAIN );
