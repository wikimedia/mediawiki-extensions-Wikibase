<?php

namespace Wikibase\Repo\EditEntity;

use MediaWiki\User\User;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\Lib\Store\EntityStore;
use Wikimedia\Stats\StatsFactory;

/**
 * EntityStore that collects stats for edit save times.
 * @license GPL-2.0-or-later
 */
class StatslibSaveTimeRecordingEntityStore implements EntityStore {

	/**
	 * @var EntityStore
	 */
	private $entityStore;

	/**
	 * @var StatsFactory
	 */
	private $statsFactory;

	/**
	 * @var string
	 */
	private $statsdTimingPrefix;

	/**
	 * @var string
	 */
	private $statsTimingPrefix;

	/**
	 * @param EntityStore $entityStore
	 * @param StatsFactory $statsFactory
	 * @param string $statsdTimingPrefix Resulting metric will be: $statsdTimingPrefix.saveEntity.<entitytype>
	 * @param string $statsTimingPrefix
	 */
	public function __construct(
		EntityStore $entityStore,
		StatsFactory $statsFactory,
		string $statsdTimingPrefix,
		string $statsTimingPrefix
	) {
		$this->entityStore = $entityStore;
		$this->statsFactory = $statsFactory->withComponent( 'WikibaseRepo' );
		$this->statsdTimingPrefix = $statsdTimingPrefix;
		$this->statsTimingPrefix = $statsTimingPrefix;
	}

	/**
	 * @inheritDoc
	 */
	public function assignFreshId( EntityDocument $entity ) {
		return $this->entityStore->assignFreshId( $entity );
	}

	/**
	 * @inheritDoc
	 */
	public function saveEntity(
		EntityDocument $entity,
		$summary,
		User $user,
		$flags = 0,
		$baseRevId = false,
		array $tags = []
	) {
		$timing = $this->statsFactory
			->getTiming( "{$this->statsTimingPrefix}_saveEntity_duration_seconds" )
			->setLabel( 'type', $entity->getType() )
			->copyToStatsdAt( "{$this->statsdTimingPrefix}.saveEntity.{$entity->getType()}" );

		$timing->start();
		$result = $this->entityStore->saveEntity( $entity, $summary, $user, $flags, $baseRevId, $tags );
		$timing->stop();

		return $result;
	}

	/**
	 * @inheritDoc
	 */
	public function saveRedirect(
		EntityRedirect $redirect,
		$summary,
		User $user,
		$flags = 0,
		$baseRevId = false,
		array $tags = []
	) {
		return $this->entityStore->saveRedirect( $redirect, $summary, $user, $flags, $baseRevId, $tags );
	}

	/**
	 * @inheritDoc
	 */
	public function deleteEntity( EntityId $entityId, $reason, User $user ) {
		return $this->entityStore->deleteEntity( $entityId, $reason, $user );
	}

	/**
	 * @inheritDoc
	 */
	public function userWasLastToEdit( User $user, EntityId $id, $lastRevId ) {
		return $this->entityStore->userWasLastToEdit( $user, $id, $lastRevId );
	}

	/**
	 * @inheritDoc
	 */
	public function updateWatchlist( User $user, EntityId $id, $watch ) {
		$this->entityStore->updateWatchlist( $user, $id, $watch );
	}

	/**
	 * @inheritDoc
	 */
	public function isWatching( User $user, EntityId $id ) {
		return $this->entityStore->isWatching( $user, $id );
	}

	/**
	 * @inheritDoc
	 */
	public function canCreateWithCustomId( EntityId $id ) {
		return $this->entityStore->canCreateWithCustomId( $id );
	}

}
