<?php

namespace Wikibase\Repo\Notifications;

use Wikibase\Change;
use Wikibase\ChangeNotificationJob;

/**
 * ChangeNotificationSender based on a JobQueueGroup and ChangeNotificationJob.
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 * @author Marius Hoch
 */
class JobQueueChangeNotificationSender implements ChangeNotificationSender {

	/**
	 * @var string
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
	 * @param string $repoDB
	 * @param string[] $wikiDBNames An associative array mapping site IDs to logical database names.
	 * @param int $batchSize Number of changes to push per job.
	 * @param callable|null $jobQueueGroupFactory Function that returns a JobQueueGroup for a given wiki.
	 */
	public function __construct(
		$repoDB,
		array $wikiDBNames = array(),
		$batchSize = 50,
		$jobQueueGroupFactory = null
	) {
		$this->repoDB = $repoDB;
		$this->wikiDBNames = $wikiDBNames;
		$this->batchSize = $batchSize;
		$this->jobQueueGroupFactory =
			$jobQueueGroupFactory === null ? 'JobQueueGroup::singleton' : $jobQueueGroupFactory;
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
			$jobs[] = ChangeNotificationJob::newFromChanges( $chunk, $this->repoDB );
		}
		$qgroup->push( $jobs );

		wfDebugLog(
			__METHOD__,
			"Posted " . count( $jobs ) . " notification jobs for site $siteID with " .
				count( $changes ) . " changes to $wikiDB."
		);
	}

}
