<?php

namespace Wikibase;

use LoggedUpdateMaintenance;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\Lib\Reporting\ObservableMessageReporter;
use Wikibase\Lib\Reporting\ReportingExceptionHandler;
use Wikibase\Repo\Store\Sql\ChangesSubscriptionTableBuilder;
use Wikibase\Repo\WikibaseRepo;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * Maintenance script for populating wb_changes_subscription based on the wb_items_per_site table.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class PopulateChangesSubscription extends LoggedUpdateMaintenance {

	public function __construct() {
		$this->addDescription( 'Populate the wb_changes_subscription table based on entries in wb_items_per_site.' );

		$this->addOption( 'start-item', "The item ID to start from.", false, true );
		$this->addOption( 'verbose', 'Report more detailed script progress.' );

		parent::__construct();

		$this->setBatchSize( 1000 );
	}

	/**
	 * @see LoggedUpdateMaintenance::doDBUpdates
	 *
	 * @throws EntityIdParsingException
	 * @return bool
	 */
	public function doDBUpdates() {
		if ( !WikibaseSettings::isRepoEnabled() ) {
			$this->error( "You need to have Wikibase enabled in order to use this maintenance script!", 1 );
		}

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$idParser = $wikibaseRepo->getEntityIdParser();
		$startItemOption = $this->getOption( 'start-item' );

		$startItem = $startItemOption === null ? null : $idParser->parse( $startItemOption );

		if ( $startItem !== null && !( $startItem instanceof ItemId ) ) {
			throw new EntityIdParsingException( 'Not an Item ID: ' . $startItemOption );
		}

		$verbose = (bool)$this->getOption( 'verbose', false );

		$reporter = new ObservableMessageReporter();
		$reporter->registerReporterCallback(
			[ $this, 'report' ]
		);

		$builder = new ChangesSubscriptionTableBuilder(
			wfGetLB(),
			$wikibaseRepo->getEntityIdComposer(),
			'wb_changes_subscription',
			$this->mBatchSize,
			$verbose ? 'verbose' : 'standard'
		);

		$builder->setProgressReporter( $reporter );
		$builder->setExceptionHandler( new ReportingExceptionHandler( $reporter ) );

		$builder->fillSubscriptionTable( $startItem );
		return true;
	}

	/**
	 * @see LoggedUpdateMaintenance::getUpdateKey
	 *
	 * @return string
	 */
	public function getUpdateKey() {
		return 'Wikibase\PopulateChangesSubscription';
	}

	/**
	 * Outputs a message vis the output() method.
	 *
	 * @param string $msg
	 */
	public function report( $msg ) {
		$this->output( date( 'H:i:s' ) . ": $msg\n" );
	}

}

$maintClass = PopulateChangesSubscription::class;
require_once RUN_MAINTENANCE_IF_MAIN;
