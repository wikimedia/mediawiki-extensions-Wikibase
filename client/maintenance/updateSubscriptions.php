<?php

namespace Wikibase;

use LoggedUpdateMaintenance;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Reporting\ObservableMessageReporter;
use Wikibase\Lib\Reporting\ReportingExceptionHandler;
use Wikibase\Repo\Store\Sql\ChangesSubscriptionTableBuilder;
use Wikibase\Repo\WikibaseRepo;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * Maintenance script for populating wb_changes_subscription based on the wbc_entity_usage table.
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class PopulateChangesSubscription extends LoggedUpdateMaintenance {

	public function __construct() {
		$this->mDescription = 'Updates the repo\'s wb_changes_subscription table based on entries in wbc_entity_usage.';

		$this->addOption( 'start-item', "The entity ID to start from.", false, true );
		$this->addOption( 'purge', "Purge subscriptions first. If not given, subscriptions are only added, not removed.", false, true );

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
		if ( !defined( 'WB_VERSION' ) ) {
			$this->output( "You need to have Wikibase enabled in order to use this maintenance script!\n\n" );
			exit;
		}

		$idParser = WikibaseRepo::getDefaultInstance()->getEntityIdParser();
		$startItemOption = $this->getOption( 'start-item' );

		$startItem = $startItemOption === null ? null : $idParser->parse( $startItemOption );

		if ( $startItem !== null && !( $startItem instanceof ItemId ) ) {
			throw new EntityIdParsingException( 'Not an Item ID: ' . $startItemOption );
		}

		$reporter = new ObservableMessageReporter();
		$reporter->registerReporterCallback(
			array( $this, 'report' )
		);

		$builder = new ChangesSubscriptionTableBuilder(
			wfGetLB(),
			'wb_changes_subscription',
			$this->mBatchSize
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
		$this->output( "$msg\n" );
	}

}

$maintClass = 'Wikibase\PopulateChangesSubscription';
require_once( RUN_MAINTENANCE_IF_MAIN );
