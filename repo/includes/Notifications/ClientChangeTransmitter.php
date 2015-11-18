<?php

namespace Wikibase\Repo\Notifications;

use Job;
use JobQueueGroup;
use Wikibase\Change;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\ItemChange;
use Wikibase\Lib\Store\ChangeLookup;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\Job\ClientChangeNotificationJob;
use Wikibase\Store\SubscriptionLookup;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ClientChangeTransmitter implements ChangeTransmitter {

	/**
	 * @var SubscriptionLookup
	 */
	private $subscriptionLookup;

	/**
	 * @var ChangeLookup
	 */
	private $changeLookup;

	/**
	 * @var EntityTitleLookup
	 */
	private $entityTitleLookup;

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	private $batchSize;

	public function __construct(
		SubscriptionLookup $subscriptionLookup,
		ChangeLookup $changeLookup,
		EntityTitleLookup $entityTitleLookup,
		EntityIdParser $entityIdParser
	) {
		$this->subscriptionLookup = $subscriptionLookup;
		$this->changeLookup = $changeLookup;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->entityIdParser = $entityIdParser;

		$this->batchSize = 3;
	}

	/**
	 * @see ChangeNotificationChannel::sendChangeNotification()
	 *
	 * @param Change $change
	 */
	public function transmitChange( Change $change ) {
		if ( $this->batchSize <= 1 ) {
			$this->handleChange( $change );

			return;
		}

		$changeId = $change->getId();

		if ( $changeId % $this->batchSize ) {
			$changes = $this->getChangeBatch( $changeId );

			foreach ( $changes as $change ) {
				$this->handleChange( $change );
			}
		}
	}

	private function getChangeBatch( $changeId ) {
		$minId = $changeId - $this->batchSize;

		return $this->changeLookup->loadChunk(
			( $changeId - $this->batchSize ) + 1,
			$this->batchSize
		);
	}

	private function handleChange( Change $change ) {
		$job = $this->createJob( $change );
		$this->pushJob( $job );
	}

	private function createJob( Change $change ) {
		$entityId = $this->getEntityIdFromChange( $change );

		$siteIds = array_unique(
			array_merge(
				$this->getSubscribersForChange( $change ),
				$this->getSitesAffectedByChange( $change )
			)
		);

		$title = $this->entityTitleLookup->getTitleForId( $entityId );

		$job = new ClientChangeNotificationJob(
			$title,
			array(
				'change_id' => $change->getId(),
				'site_ids' => $siteIds
			)
		);

		return $job;
	}

	private function pushJob( Job $job ) {
		$jobQueueGroup = JobQueueGroup::singleton();
		$jobQueueGroup->push( $job );
	}

	/**
	 * @param Change $change
	 *
	 * @return string[] Site IDs
	 */
	private function getSitesAffectedByChange( Change $change ) {
		if ( $change instanceof ItemChange ) {
			$siteLinkDiff = $change->getSiteLinkDiff();

			if ( $siteLinkDiff ) {
				return array_keys( $siteLinkDiff->getOperations() );
			}
		}

		return array();
	}

	/**
	 * @param Change $change
	 *
	 * @return string[] Site IDs
	 */
	private function getSubscribersForChange( Change $change ) {
		$entityId = $this->getEntityIdFromChange( $change );

		return $this->subscriptionLookup->getSubscribers( array( $entityId ) );
	}

	/**
	 * @param Change $change
	 *
	 * @return string
	 */
	private function getEntityIdFromChange( Change $change ) {
		return $this->entityIdParser->parse( $change->getObjectId() );
	}

}
