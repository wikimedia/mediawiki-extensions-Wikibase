<?php

namespace Wikibase;

use Maintenance;
use Wikibase\Client\Store\Sql\BulkSubscriptionUpdater;
use Wikimedia\Rdbms\SessionConsistentConnectionManager;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\Lib\Reporting\ObservableMessageReporter;
use Wikibase\Lib\Reporting\ReportingExceptionHandler;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false
	? getenv( 'MW_INSTALL_PATH' )
	: __DIR__ . '/../../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * Maintenance script for inserting subscriptions into wb_changes_subscription based on the
 * wbc_entity_usage table.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class UpdateSubscriptions extends Maintenance {

	public function __construct() {
		$this->addDescription( 'Updates the repo\'s wb_changes_subscription table based on entries'
			. ' in wbc_entity_usage.' );

		$this->addOption( 'start-item', "The entity ID to start from.", false, true );
		$this->addOption(
			'purge',
			'Purge subscriptions first. If not given, subscriptions are only added, not removed.',
			false,
			false
		);

		parent::__construct();

		$this->setBatchSize( 1000 );
	}

	/**
	 * @see Maintenance::execute
	 *
	 * @throws EntityIdParsingException
	 * @return bool
	 */
	public function execute() {
		if ( !WikibaseSettings::isClientEnabled() ) {
			$this->error(
				'You need to have WikibaseClient enabled in order to use this maintenance script!',
				1
			);
		}

		$wikibaseClient = WikibaseClient::getDefaultInstance();
		$settings = $wikibaseClient->getSettings();
		$repoDB = $settings->getSetting( 'repoDatabase' );
		$clientId = $settings->getSetting( 'siteGlobalID' );

		$idParser = $wikibaseClient->getEntityIdParser();
		$startItemOption = $this->getOption( 'start-item' );

		$startItem = $startItemOption === null ? null : $idParser->parse( $startItemOption );

		$reporter = new ObservableMessageReporter();
		$reporter->registerReporterCallback(
			[ $this, 'report' ]
		);

		$updater = new BulkSubscriptionUpdater(
			new SessionConsistentConnectionManager( wfGetLB() ),
			new SessionConsistentConnectionManager( wfGetLB( $repoDB ), $repoDB ),
			$clientId,
			$repoDB,
			$this->mBatchSize
		);

		$updater->setProgressReporter( $reporter );
		$updater->setExceptionHandler( new ReportingExceptionHandler( $reporter ) );

		if ( $this->getOption( 'purge' ) ) {
			$updater->purgeSubscriptions( $startItem );
		}

		$updater->updateSubscriptions( $startItem );
		return true;
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

$maintClass = UpdateSubscriptions::class;
require_once RUN_MAINTENANCE_IF_MAIN;
