<?php

namespace Wikibase;

use LoggedUpdateMaintenance;
use Wikibase\Client\Usage\Sql\UsageTablePrimer;
use Wikibase\Client\WikibaseClient;
use Wikibase\Lib\Reporting\ObservableMessageReporter;
use Wikibase\Lib\Reporting\ReportingExceptionHandler;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * Maintenance script for populating wbc_entity_usage based on the page_props table.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class PopulateEntityUsage extends LoggedUpdateMaintenance {

	public function __construct() {
		parent::__construct();

		$this->mDescription = 'Populate the wbc_entity_usage table based on entries in page_props.';

		$this->addOption( 'start-page', "The page ID to start from.", false, true );
		$this->addOption( 'batch-size', "Number of pages to update per database transaction (1000 per default)", false, true );
	}

	/**
	 * @see LoggedUpdateMaintenance::doDBUpdates
	 *
	 * @return boolean
	 */
	public function doDBUpdates() {
		if ( !defined( 'WBC_VERSION' ) ) {
			$this->output( "You need to have Wikibase enabled in order to use this maintenance script!\n\n" );
			exit;
		}

		$batchSize = intval( $this->getOption( 'batch-size', 1000 ) );
		$startPage = intval( $this->getOption( 'start-oage', 1 ) );

		$reporter = new ObservableMessageReporter();
		$reporter->registerReporterCallback(
			array( $this, 'report' )
		);

		$primer = new UsageTablePrimer(
			WikibaseClient::getDefaultInstance()->getEntityIdParser(),
			wfGetLB(),
			'wbc_entity_usage',
			$batchSize
		);

		$primer->setProgressReporter( $reporter );
		$primer->setExceptionHandler( new ReportingExceptionHandler( $reporter ) );

		$primer->fillUsageTable( $startPage );
		return true;
	}

	/**
	 * @see LoggedUpdateMaintenance::getUpdateKey
	 *
	 * @return string
	 */
	public function getUpdateKey() {
		return 'Wikibase\PopulateEntityUsage';
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

}

$maintClass = 'Wikibase\PopulateEntityUsage';
require_once( RUN_MAINTENANCE_IF_MAIN );
