<?php

namespace Wikibase\Repo\Notifications;

use JobQueueGroup;
use Wikibase\Change;
use Wikibase\ChangeNotificationJob;

/**
 * ChangeNotificationSender based on a JobQueueGroup and ChangeNotificationJob.
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
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
	 * @param string $repoDB
	 * @param string[] $wikiDBNames An associative array mapping site IDs to logical database names.
	 */
	public function __construct( $repoDB, array $wikiDBNames = [] ) {
		$this->repoDB = $repoDB;
		$this->wikiDBNames = $wikiDBNames;
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

		$job = ChangeNotificationJob::newFromChanges( $changes, $this->repoDB );

		// @todo: inject JobQueueGroup
		$qgroup = JobQueueGroup::singleton( $wikiDB );
		$qgroup->push( $job );

		wfDebugLog( __METHOD__, "Posted notification job for site $siteID with "
			. count( $changes ) . " changes to $wikiDB." );
	}

}
