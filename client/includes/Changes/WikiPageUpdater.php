<?php

namespace Wikibase\Client\Changes;

use HTMLCacheUpdateJob;
use JobQueueGroup;
use Liuggio\StatsdClient\Factory\StatsdDataFactoryInterface;
use Psr\Log\LoggerInterface;
use RefreshLinksJob;
use Title;
use Wikibase\Lib\Changes\EntityChange;

/**
 * Service object for triggering different kinds of page updates
 * and generally notifying the local wiki of external changes.
 *
 * Used by ChangeHandler as an interface to the local wiki.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class WikiPageUpdater implements PageUpdater {

	/**
	 * @var JobQueueGroup
	 */
	private $jobQueueGroup;

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @var int Batch size for UpdateHtmlCacheJob
	 */
	private $purgeCacheBatchSize = 300;

	/**
	 * @var int Batch size for InjectRCRecordsJob
	 */
	private $rcBatchSize = 300;

	/**
	 * @var StatsdDataFactoryInterface|null
	 */
	private $stats;

	/**
	 * @param JobQueueGroup $jobQueueGroup
	 * @param LoggerInterface $logger
	 * @param StatsdDataFactoryInterface|null $stats
	 */
	public function __construct(
		JobQueueGroup $jobQueueGroup,
		LoggerInterface $logger,
		StatsdDataFactoryInterface $stats = null
	) {
		$this->jobQueueGroup = $jobQueueGroup;
		$this->logger = $logger;
		$this->stats = $stats;
	}

	/**
	 * @param int $purgeCacheBatchSize
	 */
	public function setPurgeCacheBatchSize( $purgeCacheBatchSize ) {
		$this->purgeCacheBatchSize = $purgeCacheBatchSize;
	}

	/**
	 * @param int $rcBatchSize
	 */
	public function setRecentChangesBatchSize( $rcBatchSize ) {
		$this->rcBatchSize = $rcBatchSize;
	}

	/**
	 * @param string $updateType
	 * @param int $delta
	 */
	private function incrementStats( $updateType, $delta ) {
		if ( $this->stats ) {
			$this->stats->updateCount( 'wikibase.client.pageupdates.' . $updateType, $delta );
		}
	}

	/**
	 * @param Title[] $titles
	 * @param array $rootJobParams
	 *
	 * @return array
	 */
	private function buildJobParams( array $titles, array $rootJobParams ) {
		$pages = $this->getPageParamForRefreshLinksJob( $titles );

		/**
		 * @see ChangeHandler::handleChange for relevant root job parameters.
		 */
		if ( isset( $rootJobParams['rootJobSignature'] ) ) {
			$signature = $rootJobParams['rootJobSignature'];
		} else {
			// Apply canonical ordering before hashing
			ksort( $pages );
			$signature = 'params:' . sha1( json_encode( $pages ) );
		}

		return [
			'pages' => $pages,
			'rootJobSignature' => $signature,
			'rootJobTimestamp' => $rootJobParams['rootJobTimestamp'] ?? wfTimestampNow(),
		];
	}

	/**
	 * Invalidates external web cached of the given pages.
	 *
	 * @param Title[] $titles The Titles of the pages to update
	 * @param array $rootJobParams
	 * @param string $causeAction Triggering action
	 * @param string $causeAgent Triggering agent
	 */
	public function purgeWebCache(
		array $titles,
		array $rootJobParams,
		$causeAction,
		$causeAgent
	) {
		if ( $titles === [] ) {
			return;
		}

		$jobs = [];
		$titleBatches = array_chunk( $titles, $this->purgeCacheBatchSize );
		$dummyTitle = Title::makeTitle( NS_SPECIAL, 'Badtitle/' . __CLASS__ );

		$cause = [ 'causeAction' => $causeAction, 'causeAgent' => $causeAgent ];
		/** @var Title[] $batch */
		foreach ( $titleBatches as $batch ) {
			$this->logger->debug(
				'{method}: scheduling HTMLCacheUpdateJob for {pageCount} titles',
				[
					'method' => __METHOD__,
					'pageCount' => count( $batch ),
				]
			);

			$jobs[] = new HTMLCacheUpdateJob(
				$dummyTitle, // the title will be ignored because the 'pages' parameter is set.
				array_merge( $this->buildJobParams( $batch, $rootJobParams ), $cause )
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
	 * @param string $causeAction Triggering action
	 * @param string $causeAgent Triggering agent
	 */
	public function scheduleRefreshLinks(
		array $titles,
		array $rootJobParams,
		$causeAction,
		$causeAgent
	) {
		if ( $titles === [] ) {
			return;
		}

		// NOTE: no batching here, since RefreshLinksJobs are slow, and benefit more from
		// deduplication and checking against page_touched than from reducing overhead
		// through batching.
		$jobCount = count( $titles );

		$cause = [ 'causeAction' => $causeAction, 'causeAgent' => $causeAgent ];
		foreach ( $titles as $title ) {
			$this->jobQueueGroup->lazyPush(
				new RefreshLinksJob( $title, array_merge( $rootJobParams, $cause ) )
			);
		}

		$this->incrementStats( 'RefreshLinks.jobs', $jobCount );
		$this->incrementStats( 'RefreshLinks.titles', count( $titles ) );
	}

	/**
	 * @param Title[] $titles
	 *
	 * @return array[] string $pageId => [ int $namespace, string $dbKey ]
	 */
	private function getPageParamForRefreshLinksJob( array $titles ) {
		$pages = [];

		/** @see ChangeHandler::getTitleBatchSignature */
		foreach ( $titles as $title ) {
			$id = $title->getArticleID();
			$pages[$id] = [ $title->getNamespace(), $title->getDBkey() ];
		}

		return $pages;
	}

	/**
	 * Injects an RC entry into the recentchanges, using the given title and attribs
	 *
	 * @param Title[] $titles
	 * @param EntityChange $change
	 * @param array $rootJobParams
	 */
	public function injectRCRecords( array $titles, EntityChange $change, array $rootJobParams = [] ) {
		if ( $titles === [] ) {
			return;
		}

		$jobs = [];
		$titleBatches = array_chunk( $titles, $this->rcBatchSize );
		$titleCount = 0;

		/** @var Title[] $batch */
		foreach ( $titleBatches as $batch ) {
			$this->logger->debug(
				'{method}: scheduling InjectRCRecords for {pageCount} titles',
				[
					'method' => __METHOD__,
					'pageCount' => count( $batch ),
				]
			);

			$jobs[] = InjectRCRecordsJob::makeJobSpecification( $batch, $change, $rootJobParams );
			$titleCount += count( $batch );

			// FIXME: This is a hot fix for T177707, and must be reconsidered.
			break;
		}

		$this->jobQueueGroup->lazyPush( $jobs );

		$this->incrementStats( 'InjectRCRecords.jobs', count( $jobs ) );
		$this->incrementStats( 'InjectRCRecords.titles', $titleCount );

		// tracking fallout of the hacky fix for T177707
		$delta = count( $titles ) - $titleCount;
		$this->incrementStats( 'InjectRCRecords.discardedTitles', $delta );
		if ( $delta !== 0 ) {
			$this->incrementStats( 'InjectRCRecords.incompleteChanges', 1 );
		}
	}

}
