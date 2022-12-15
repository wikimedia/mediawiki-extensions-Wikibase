<?php

namespace Wikibase\Repo\Store\Sql;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Rdbms\RepoDomainDb;
use Wikibase\Repo\Store\SubscriptionLookup;
use Wikimedia\Rdbms\ConnectionManager;

/**
 * Implementation of SubscriptionLookup based on a database table.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class SqlSubscriptionLookup implements SubscriptionLookup {

	/**
	 * @var ConnectionManager
	 */
	private $repoConnections;

	public function __construct( RepoDomainDb $repoDomainDb ) {
		$this->repoConnections = $repoDomainDb->connections();
	}

	/**
	 * Return the existing subscriptions for given Id to check
	 *
	 * @param EntityId $idToCheck EntityId to get subscribers
	 *
	 * @return string[] wiki IDs of wikis subscribed to the given entity
	 */
	public function getSubscribers( EntityId $idToCheck ) {
		return $this->repoConnections->getReadConnection()->newSelectQueryBuilder()
			->select( 'cs_subscriber_id' )
			->from( 'wb_changes_subscription' )
			->where( [ 'cs_entity_id' => $idToCheck->getSerialization() ] )
			->caller( __METHOD__ )->fetchFieldValues();
	}

}
