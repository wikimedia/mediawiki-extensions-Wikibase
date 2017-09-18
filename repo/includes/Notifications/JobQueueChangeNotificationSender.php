<?php

namespace Wikibase\Repo\Notifications;

use JobQueueGroup;
use JobSpecification;
use Wikibase\Change;

/**
 * ChangeNotificationSender based on a JobQueueGroup and ChangeNotificationJob.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 * @author Marius Hoch
 */
class JobQueueChangeNotificationSender implements ChangeNotificationSender {

	/**
	 * @var string|bool
	 */
	private $repoDB;

	/**
	 * @var string[] Mapping of site IDs to database names.
	 */
	private $wikiDBNames;

	/**
	 * @var int
	 */
	private $batchSize;

	/**
	 * @var callable
	 */
	private $jobQueueGroupFactory;

	/**
	 * @param string|bool $repoDB
	 * @param string[] $wikiDBNames An associative array mapping site IDs to logical database names.
	 * @param int $batchSize Number of changes to push per job.
	 * @param callable|null $jobQueueGroupFactory Function that returns a JobQueueGroup for a given wiki.
	 */
	public function __construct(
		$repoDB,
		array $wikiDBNames = [],
		$batchSize = 50,
		$jobQueueGroupFactory = null
	) {
		$this->repoDB = $repoDB;
		$this->wikiDBNames = $wikiDBNames;
		$this->batchSize = $batchSize;
		$this->jobQueueGroupFactory = $jobQueueGroupFactory ?: [ JobQueueGroup::class, 'singleton' ];
	}

	/**
	 * @see ChangeNotificationSender::sendNotification
	 *
	 * @param string $siteID The client wiki's global site identifier, as used by sitelinks.
	 * @param Change[] $changes The list of changes to post to the wiki.
	 */
	public function sendNotification( $siteID, array $changes ) {
		if ( empty( $changes ) ) {
			return; // nothing to do
		}

		if ( isset( $this->wikiDBNames[$siteID] ) ) {
			$wikiDB = $this->wikiDBNames[$siteID];
		} else {
			$wikiDB = $siteID;
		}

		$qgroup = call_user_func( $this->jobQueueGroupFactory, $wikiDB );
		$chunks = array_chunk( $changes, $this->batchSize );

		$jobs = [];
		foreach ( $chunks as $chunk ) {
			$jobs[] = $this->getJobSpecification( $chunk );
		}
		$qgroup->lazyPush( $jobs );

		wfDebugLog(
			__METHOD__,
			"Posted " . count( $jobs ) . " notification jobs for site $siteID with " .
				count( $changes ) . " changes to $wikiDB."
		);
	}

	/**
	 * @param Change[] $changes
	 *
	 * @return JobSpecification
	 */
	private function getJobSpecification( array $changes ) {
		$changeIds = array_map(
			function ( Change $change ) {
				return $change->getId();
			},
			$changes
		);

		$params = [
			'repo' => $this->repoDB,
			'changeIds' => $changeIds,

			/**
			 * Set root job parameters for deduplication. Compare
			 * @see WikiPageUpdater::buildJobParams and
			 * @see InjectRCRecordsJob::makeJobSpecification.
			 */
			'rootJobTimestamp' => wfTimestampNow(),
		];

		return new JobSpecification(
			'ChangeNotification',
			$params
		);
	}

}
