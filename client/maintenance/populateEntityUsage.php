<?php

declare( strict_types=1 );

namespace Wikibase;

use LoggedUpdateMaintenance;
use Onoi\MessageReporter\CallbackMessageReporter;
use Wikibase\Client\Usage\Sql\EntityUsageTableBuilder;
use Wikibase\Client\WikibaseClient;
use Wikibase\Lib\Reporting\ReportingExceptionHandler;
use Wikibase\Lib\WikibaseSettings;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false
	? getenv( 'MW_INSTALL_PATH' )
	: __DIR__ . '/../../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * Maintenance script for populating wbc_entity_usage based on the page_props table.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class PopulateEntityUsage extends LoggedUpdateMaintenance {

	public function __construct() {
		parent::__construct();

		$this->addDescription( 'Populate the wbc_entity_usage table based on entries in page_props.' );

		$this->addOption( 'start-page', "The page ID to start from.", false, true );

		$this->setBatchSize( 1000 );
	}

	/**
	 * @see LoggedUpdateMaintenance::doDBUpdates
	 */
	public function doDBUpdates(): bool {
		if ( !WikibaseSettings::isClientEnabled() ) {
			$this->output( "You need to have WikibaseClient enabled in order to use this maintenance script!\n\n" );
			exit;
		}

		$startPage = (int)$this->getOption( 'start-page', 0 );

		$reporter = new CallbackMessageReporter( [ $this, 'report' ] );

		$builder = new EntityUsageTableBuilder(
			WikibaseClient::getEntityIdParser(),
			WikibaseClient::getClientDomainDbFactory()->newLocalDb(),
			$this->mBatchSize
		);

		$builder->setProgressReporter( $reporter );
		$builder->setExceptionHandler( new ReportingExceptionHandler( $reporter ) );

		$builder->fillUsageTable( $startPage );
		return true;
	}

	/**
	 * @see LoggedUpdateMaintenance::getUpdateKey
	 */
	public function getUpdateKey(): string {
		return 'Wikibase\PopulateEntityUsage';
	}

	/**
	 * Outputs a message via the output() method.
	 */
	public function report( string $msg ): void {
		$this->output( "$msg\n" );
	}

}

$maintClass = PopulateEntityUsage::class;
require_once RUN_MAINTENANCE_IF_MAIN;
