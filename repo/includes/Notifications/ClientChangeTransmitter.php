<?php

namespace Wikibase\Repo\Notifications;

use JobQueueGroup;
use Wikibase\Change;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\ItemChange;
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
	 * @var EntityTitleLookup
	 */
	private $entityTitleLookup;

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	public function __construct(
		SubscriptionLookup $subscriptionLookup,
		EntityTitleLookup $entityTitleLookup,
		EntityIdParser $entityIdParser
	) {
		$this->subscriptionLookup = $subscriptionLookup;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->entityIdParser = $entityIdParser;
	}

	/**
	 * @see ChangeNotificationChannel::sendChangeNotification()
	 *
	 * @param Change $change
	 */
	public function transmitChange( Change $change ) {
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
			array( 'site_ids' => $siteIds )
		);

		$jobQueueGroup = JobQueueGroup::singleton();
		$jobQueueGroup->push( $job );

		wfDebugLog( 'wikidata', var_export( $siteIds, true ) );
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
