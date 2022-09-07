<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Maintenance;

use IJobSpecification;
use Maintenance;
use MediaWiki\MediaWikiServices;
use Wikibase\Lib\Changes\EntityChange;
use Wikibase\Lib\WikibaseSettings;
use Wikibase\Repo\ChangeModification\DispatchChangesJob;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\Timestamp\ConvertibleTimestamp;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';
require_once $basePath . '/maintenance/Maintenance.php';

/**
 * Resubmit stuck jobs based on old changes in the Wikibase wb_changes table
 *
 * @license GPL-2.0-or-later
 */
class ResubmitChanges extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->addDescription( 'Resubmit DispatchChanges jobs, based on entries in the wb_changes table.' );

		$this->addOption( 'minimum-age', 'Only resubmit jobs older than this number of seconds', false, true );
		$this->setBatchSize( 500 );
	}

	public function execute(): void {
		if ( !WikibaseSettings::isRepoEnabled() ) {
			$this->output( "You need to have Wikibase enabled in order to use this maintenance script!\n\n" );
			exit;
		}

		$minimumAge = $this->getOption( 'minimum-age', 60 * 60 * 24 );
		$thisTimeOrOlder = ConvertibleTimestamp::convert( TS_MW, time() - $minimumAge );
		$stats = MediaWikiServices::getInstance()->getPerDbNameStatsdDataFactory();
		$entityChangeLookup = WikibaseRepo::getEntityChangeLookup();
		$jobQueueGroup = MediaWikiServices::getInstance()->getJobQueueGroupFactory()->makeJobQueueGroup();

		$offset = 0;
		$changes = $entityChangeLookup->loadChangesBefore( $thisTimeOrOlder, $this->mBatchSize, $offset );
		while ( !empty( $changes ) ) {
			$numberOfChanges = count( $changes );
			$stats->updateCount( 'wikibase.repo.ResubmitChanges.numberOfChanges', $numberOfChanges );
			$this->log( 'Resubmitting ' . $numberOfChanges . ' changes older than ' . $minimumAge . ' seconds.' );

			$jobQueueGroup->push( $this->makeChangesIntoJobs( $changes ) );
			$offset += $this->mBatchSize;
			$changes = $entityChangeLookup->loadChangesBefore( $thisTimeOrOlder, $this->mBatchSize, $offset );
		}

		$this->log( 'Resubmitting changes is done.' );
	}

	private function makeChangesIntoJobs( array $changes ): array {
		return array_map( function ( EntityChange $change ): IJobSpecification {
			return DispatchChangesJob::makeJobSpecification( $change->getEntityId()->getSerialization() );
		}, $changes );
	}

	/**
	 * Log a message unless we are quiet.
	 */
	public function log( string $message ): void {
		$this->output( date( 'H:i:s' ) . ' ' . $message . "\n", 'resubmitChanges::log' );
		$this->cleanupChanneled();
	}
}

$maintClass = ResubmitChanges::class;
require_once RUN_MAINTENANCE_IF_MAIN;
