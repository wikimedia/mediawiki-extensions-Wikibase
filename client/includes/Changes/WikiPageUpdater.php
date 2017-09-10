<?php

namespace Wikibase\Client\Changes;

use HTMLCacheUpdateJob;
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
	 * @var int Batch size for UpdateHtmlCacheJob
	 */
	private $purgeCacheBatchSize = 100;

	/**
	 * @var int Batch size for InjectRCRecordsJob
	 */
	private $rcBatchSize = 100;

	/**
	 * @var RecentChangesDuplicateDetector|null
	 */
	private $recentChangesDuplicateDetector;

	/**
	 * @var StatsdDataFactoryInterface|null
	 */
	private $stats;

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
	public function getPurgeCacheBatchSize() {
		return $this->purgeCacheBatchSize;
	}

	/**
	 * @param int $purgeCacheBatchSize
	 */
	public function setPurgeCacheBatchSize( $purgeCacheBatchSize ) {
		$this->purgeCacheBatchSize = $purgeCacheBatchSize;
	}

	/**
	 * @return int
	 */
	public function getRecentChangesBatchSize() {
		return $this->rcBatchSize;
	}

	/**
	 * @param int $rcBatchSize
	 */
	public function setRecentChangesBatchSize( $rcBatchSize ) {
		$this->rcBatchSize = $rcBatchSize;
	}

	private function incrementStats( $updateType, $delta ) {
		if ( $this->stats ) {
			$this->stats->updateCount( 'wikibase.client.pageupdates.' . $updateType, $delta );
		}
	}

	/**
	 * @param array $params
	 * @param array $rootJobParams
	 * @return array
	 */
	private function addRootJobParameters( array $params, array $rootJobParams ) {
		// See JobQueueChangeNotificationSender::getJobSpecification for relevant root job parameters.

		if ( isset( $rootJobParams['rootJobSignature'] ) ) {
			$params['rootJobSignature'] = $rootJobParams['rootJobSignature'];
		} else {
			ksort( $params ); // apply canonical ordering before hashing
			$params['rootJobSignature'] = 'params:' . sha1( json_encode( $params ) );
		}

		if ( isset( $rootJobParams['rootJobTimestamp'] ) ) {
			$params['rootJobTimestamp'] = $rootJobParams['rootJobTimestamp'];
		} else {
			$params['rootJobTimestamp'] = wfTimestampNow();
		}

		return $params;
	}

	/**
	 * Invalidates external web cached of the given pages.
	 *
	 * @param Title[] $titles The Titles of the pages to update
	 * @param array $rootJobParams
	 */
	public function purgeWebCache( array $titles, array $rootJobParams = [] ) {
		if ( $titles === [] ) {
			return;
		}

		$jobs = [];
		$titleBatches = array_chunk( $titles, $this->getPurgeCacheBatchSize() );

		/* @var Title[] $batch */
		foreach ( $titleBatches as $batch ) {
			wfDebugLog( __CLASS__, __FUNCTION__ . ": scheduling HTMLCacheUpdateJob for "
			                     . count( $batch ) . " titles" );

			$dummyTitle = Title::makeTitle( NS_SPECIAL, 'Badtitle/' . __CLASS__ );

			$jobs[] = new HTMLCacheUpdateJob(
				$dummyTitle, // the title will be ignored because the 'pages' parameter is set.
				$this->addRootJobParameters( [
					'pages' => $this->getPageParamForRefreshLinksJob( $batch )
				], $rootJobParams )
			);
		}

		$this->jobQueueGroup->lazyPush( $jobs );
		$this->incrementStats( 'WebCache.jobs', count( $jobs ) );
		$this->incrementStats( 'WebCache.titles', count( $titles ) );
	}

	/**
	 * Schedules RefreshLinks jobs for the given titles
	 *
	 * @param Title[] $titles The Titles of the pages to update
	 * @param array $rootJobParams
	 */
	public function scheduleRefreshLinks( array $titles, array $rootJobParams = [] ) {
		if ( $titles === [] ) {
			return;
		}

		$c = 0;

		// NOTE: no batching here, since RefreshLinksJobs are slow, and benefit more from
		// deduplication and checking against page_touched than from reducing overhead
		// through batching.

		/* @var Title $title */
		foreach ( $titles as $title ) {
			$job = new RefreshLinksJob(
				$title, // the title will be ignored because the 'pages' parameter is set.
				$this->addRootJobParameters( [], $rootJobParams )
			);

			$this->jobQueueGroup->lazyPush( $job );
			$c++;
		}

		$this->incrementStats( 'RefreshLinks.jobs', $c );
		$this->incrementStats( 'RefreshLinks.titles', count( $titles ) );
	}

	/**
	 * @param Title[] $titles
	 *
	 * @return array[] string $pageId => [ int $namespace, string $dbKey ]
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
	 * @param array $rootJobParams
	 */
	public function injectRCRecords( array $titles, EntityChange $change, array $rootJobParams = [] ) {
		if ( $titles === [] ) {
			return;
		}

		$jobSpec = InjectRCRecordsJob::makeJobSpecification(
			$titles,
			$change,
			$rootJobParams,
			$this->getRecentChangesBatchSize()
		);

		$this->jobQueueGroup->lazyPush( $jobSpec );

		$this->incrementStats( 'InjectRCRecords.jobs', 1 );
		$this->incrementStats( 'InjectRCRecords.titles', count( $titles ) );
	}

}
