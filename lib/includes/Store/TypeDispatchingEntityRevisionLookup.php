<?php

namespace Wikibase\Lib\Store;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityId;

/**
 * An EntityRevisionLookup that does dispatching based on the entity type.
 *
 * Warning! This class is build on the assumption that it is only instantiated once.
 *
 * @license GPL-2.0+
 * @author Thiemo Kreuz
 */
class TypeDispatchingEntityRevisionLookup implements EntityRevisionLookup {

	/**
	 * @var EntityRevisionLookup[] indexed by entity type
	 */
	private $lookups;

	/**
	 * @var EntityRevisionLookup
	 */
	private $defaultLookup;

	/**
	 * @param callable[] $callbacks indexed by entity type
	 * @param EntityRevisionLookup $defaultLookup
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( array $callbacks, EntityRevisionLookup $defaultLookup ) {
		foreach ( $callbacks as $entityType => $callback ) {
			$store = call_user_func( $callback, [ $defaultLookup ] );

			if ( !( $store instanceof EntityRevisionLookup ) ) {
				throw new InvalidArgumentException(
					"Callback provided for $entityType did not created an EntityRevisionLookup"
				);
			}

			$this->lookups[$entityType] = $store;
		}

		$this->defaultLookup = $defaultLookup;
	}

	/**
	 * @see EntityRevisionLookup::getEntityRevision
	 *
	 * @param EntityId $entityId
	 * @param int $revisionId
	 * @param string $mode
	 *
	 * @throws RevisionedUnresolvedRedirectException
	 * @throws StorageException
	 * @return EntityRevision|null
	 */
	public function getEntityRevision(
		EntityId $entityId,
		$revisionId = 0,
		$mode = self::LATEST_FROM_REPLICA
	) {
		return $this->getLookup( $entityId->getEntityType() )->getEntityRevision(
			$entityId,
			$revisionId,
			$mode
		);
	}

	/**
	 * @see EntityRevisionLookup::getLatestRevisionId
	 *
	 * @param EntityId $entityId
	 * @param string $mode
	 *
	 * @return int|false
	 */
	public function getLatestRevisionId( EntityId $entityId, $mode = self::LATEST_FROM_REPLICA ) {
		return $this->getLookup( $entityId->getEntityType() )->getLatestRevisionId(
			$entityId,
			$mode
		);
	}

	/**
	 * @param string $entityType
	 *
	 * @return EntityRevisionLookup
	 */
	private function getLookup( $entityType ) {
		return $this->lookups[$entityType] ?: $this->defaultLookup;
	}

}
