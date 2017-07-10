<?php

namespace Wikibase\Client\Changes;

use Job;
use JobQueueGroup;
use Liuggio\StatsdClient\Factory\StatsdDataFactoryInterface;
use RefreshLinksJob;
use Title;
use Wikibase\Client\RecentChanges\RecentChangeFactory;
use Wikibase\Client\RecentChanges\RecentChangesDuplicateDetector;
use Wikibase\EntityChange;
use Wikimedia\Rdbms\LBFactory;

/**
 * Service object for triggering different kinds of page updates
 * and generally notifying the local wiki of external changes.
 *
 * Used by ChangeHandler as an interface to the local wiki.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class WikiPageUpdater implements PageUpdater {

	/**
	 * @var JobQueueGroup
	 */
	private $jobQueueGroup;

	/**
	 * @var RecentChangeFactory
	 */
	private $recentChangeFactory;

	/**
	 * @var LBFactory
	 */
	private $LBFactory;

	/**
	 * @var int Batch size for database operations
	 */
	private $dbBatchSize = 100;

	/**
	 * @var RecentChangesDuplicateDetector|null
	 */
	private $recentChangesDuplicateDetector;

	/**
	 * @var StatsdDataFactoryInterface|null
	 */
	private $stats;

	/**
	 * @var callable
	 */
	private $getTimestamp = 'wfTimestampNow';

	/**
	 * @param JobQueueGroup $jobQueueGroup
	 * @param RecentChangeFactory $recentChangeFactory
	 * @param LBFactory $LBFactory
	 * @param RecentChangesDuplicateDetector|null $recentChangesDuplicateDetector
	 * @param StatsdDataFactoryInterface|null $stats
	 */
	public function __construct(
		JobQueueGroup $jobQueueGroup,
		RecentChangeFactory $recentChangeFactory,
		LBFactory $LBFactory,
		RecentChangesDuplicateDetector $recentChangesDuplicateDetector = null,
		StatsdDataFactoryInterface $stats = null
	) {
		$this->jobQueueGroup = $jobQueueGroup;
		$this->recentChangeFactory = $recentChangeFactory;
		$this->LBFactory = $LBFactory;
		$this->recentChangesDuplicateDetector = $recentChangesDuplicateDetector;
		$this->stats = $stats;
	}

	/**
	 * @return int
	 */
	public function getDbBatchSize() {
		return $this->dbBatchSize;
	}

	/**
	 * @param int $dbBatchSize
	 */
	public function setDbBatchSize( $dbBatchSize ) {
		$this->dbBatchSize = $dbBatchSize;
	}

	private function incrementStats( $updateType, $delta ) {
		if ( $this->stats ) {
			$this->stats->updateCount( 'wikibase.client.pageupdates.' . $updateType, $delta );
		}
	}

	/**
	 * Invalidates external web cached of the given pages.
	 *
	 * @param Title[] $titles The Titles of the pages to update
	 */
	public function purgeWebCache( array $titles ) {
		/* @var Title $title */
		foreach ( $titles as $title ) {
			wfDebugLog( __CLASS__, __FUNCTION__ . ": purging web cache for " . $title->getText() );
			$title->purgeSquid();
		}
		$this->incrementStats( 'WebCache', count( $titles ) );
	}

	/**
	 * Schedules RefreshLinks jobs for the given titles
	 *
	 * @param Title[] $titles The Titles of the pages to update
	 */
	public function scheduleRefreshLinks( array $titles ) {
		if ( empty( $titles ) ) {
			return;
		}

		$jobs = [];
		$purgeTime = call_user_func( $this->getTimestamp );
		$titleBatches = array_chunk( $titles, $this->dbBatchSize );

		/* @var Title[] $batch */
		foreach ( $titleBatches as $batch ) {
			wfDebugLog( __CLASS__, __FUNCTION__ . ": scheduling refresh links for "
				. count( $batch ) . " titles" );

			// NOTE: nominal title, will be ignored because the 'pages' parameter is set.
			$title = reset( $batch );
			$pages = $this->getPageParamForRefreshLinksJob( $batch );
			$ids = array_keys( $pages );

			// touch, then schedule re-parsing via the job queue.
			$this->touchPages( $ids, $purgeTime );

			// TODO: add root job timestamp?
			$jobs[] = new RefreshLinksJob(
				$title,
				[
					'pages' => $pages,
					'rootJobTimestamp' => $purgeTime
				]
			);
		}

		$this->jobQueueGroup->lazyPush( $jobs );
		$this->incrementStats( 'RefreshLinks-jobs', count( $jobs ) );
		$this->incrementStats( 'RefreshLinks-titles', count( $titles ) );
	}

	/**
	 * Marks the parser cache for the given page IDs as dirty by setting
	 * the page_touched field to $purgeTime.
	 *
	 * @param int[] $ids
	 * @param string $purgeTime
	 */
	private function touchPages( $ids, $purgeTime ) {
		$trxToken = $this->LBFactory->getEmptyTransactionTicket( __METHOD__ );
		$dbw = $this->LBFactory->getMainLB()->getConnection( DB_MASTER );

		$dbTimestamp = $dbw->timestamp( $purgeTime );
		$dbw->update(
			'page',
			[ 'page_touched' => $dbTimestamp ],
			[
				'page_id' => $ids,
				'page_touched < ' . $dbw->addQuotes( $dbTimestamp )
			],
			__METHOD__
		);

		$this->LBFactory->getMainLB()->reuseConnection( $dbw );
		$this->LBFactory->commitAndWaitForReplication( __METHOD__, $trxToken );
	}

	/**
	 * @param Title[] $titles
	 *
	 * @returns array[] A Map of pageId => [ namespace, dbKey ]
	 */
	private function getPageParamForRefreshLinksJob( array $titles ) {
		$pages = [];

		foreach ( $titles as $t ) {
			$id = $t->getArticleID();
			$pages[$id] = [
				$t->getNamespace(),
				$t->getDBkey()
			];
		}

		return $pages;
	}

	/**
	 * Injects an RC entry into the recentchanges, using the the given title and attribs
	 *
	 * @param Title[] $titles
	 * @param EntityChange $change
	 */
	public function injectRCRecords( array $titles, EntityChange $change ) {
		$rcAttribs = $this->recentChangeFactory->prepareChangeAttributes( $change );

		$c = 0;
		$trxToken = $this->LBFactory->getEmptyTransactionTicket( __METHOD__ );

		// TODO: do this via the job queue, in batches, see T107722
		foreach ( $titles as $title ) {
			if ( !$title->exists() ) {
				continue;
			}

			$rc = $this->recentChangeFactory->newRecentChange( $change, $title, $rcAttribs );

			if ( $this->recentChangesDuplicateDetector
				&& $this->recentChangesDuplicateDetector->changeExists( $rc )
			) {
				wfDebugLog( __CLASS__, __FUNCTION__ . ": skipping duplicate RC entry for " . $title->getFullText() );
			} else {
				wfDebugLog( __CLASS__, __FUNCTION__ . ": saving RC entry for " . $title->getFullText() );
				$rc->save();
			}

			if ( ( ++$c % $this->dbBatchSize ) === 0 ) {
				$this->LBFactory->commitAndWaitForReplication( __METHOD__, $trxToken );
				$trxToken = $this->LBFactory->getEmptyTransactionTicket( __METHOD__ );
				$c = 0;
			}
		}

		if ( ( $c % $this->dbBatchSize ) > 0 ) {
			$this->LBFactory->commitAndWaitForReplication( __METHOD__, $trxToken );
		}

		$this->incrementStats( 'InjectRCRecords', count( $titles ) );
	}

	/**
	 * Set callback for getting the current system time.
	 * Intended for testing only!
	 *
	 * @param callable $callback takes no parameter, returns a timestamp string.
	 */
	public function setTimestampCallback( callable $callback ) {
		$this->getTimestamp = $callback;
	}

}
