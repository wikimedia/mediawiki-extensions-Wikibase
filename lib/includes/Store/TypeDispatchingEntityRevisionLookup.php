<?php

namespace Wikibase\Lib\Store;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityId;

/**
 * An EntityRevisionLookup that does dispatching based on the entity type.
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class TypeDispatchingEntityRevisionLookup implements EntityRevisionLookup {

	/**
	 * @var array indexed by entity type
	 */
	private $lookups;

	/**
	 * @var EntityRevisionLookup
	 */
	private $defaultLookup;

	/**
	 * @param callable[] $callbacks indexed by entity type
	 * @param EntityRevisionLookup $defaultLookup
	 */
	public function __construct( array $callbacks, EntityRevisionLookup $defaultLookup ) {
		$this->lookups = $callbacks;
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
		$mode = LookupConstants::LATEST_FROM_REPLICA
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
	 * @return LatestRevisionIdResult
	 */
	public function getLatestRevisionId( EntityId $entityId, $mode = LookupConstants::LATEST_FROM_REPLICA ) {
		return $this->getLookup( $entityId->getEntityType() )->getLatestRevisionId(
			$entityId,
			$mode
		);
	}

	/**
	 * @param string $entityType
	 *
	 * @throws InvalidArgumentException
	 * @return EntityRevisionLookup
	 */
	private function getLookup( $entityType ) {
		if ( !array_key_exists( $entityType, $this->lookups ) ) {
			return $this->defaultLookup;
		}

		if ( is_callable( $this->lookups[$entityType] ) ) {
			$this->lookups[$entityType] = call_user_func(
				$this->lookups[$entityType],
				$this->defaultLookup
			);

			if ( !( $this->lookups[$entityType] instanceof EntityRevisionLookup ) ) {
				throw new InvalidArgumentException(
					"Callback provided for $entityType did not create an EntityRevisionLookup"
				);
			}
		}

		return $this->lookups[$entityType];
	}

}
