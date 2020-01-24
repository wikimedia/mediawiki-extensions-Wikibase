<?php

namespace Wikibase\DataModel\Services\Lookup;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Entity\EntityPrefetcher;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\StatementListProvider;

/**
 * Service for getting the closest entity (out of a specified set),
 * from a given starting entity. The starting entity, and the target entities
 * are (potentially indirectly, via intermediate entities) linked by statements
 * with a given property ID, pointing from the starting entity to one of the
 * target entities.
 *
 * @since 3.10
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class EntityRetrievingClosestReferencedEntityIdLookup implements ReferencedEntityIdLookup {

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @var EntityPrefetcher
	 */
	private $entityPrefetcher;

	/**
	 * @var int Maximum search depth: Maximum number of intermediate entities to search through.
	 *  For example 0 means that only the entities immediately referenced will be found.
	 */
	private $maxDepth;

	/**
	 * @var int Maximum number of entities to retrieve.
	 */
	private $maxEntityVisits;

	/**
	 * Map (entity id => true) of already visited entities.
	 *
	 * @var bool[]
	 */
	private $alreadyVisited = [];

	/**
	 * @param EntityLookup $entityLookup
	 * @param EntityPrefetcher $entityPrefetcher
	 * @param int $maxDepth Maximum search depth: Maximum number of intermediate entities to search through.
	 *  For example if 0 is given, only the entities immediately referenced will be found.
	 *  If this limit gets exhausted, a MaxReferenceDepthExhaustedException is thrown.
	 * @param int $maxEntityVisits Maximum number of entities to retrieve during a lookup.
	 *  If this limit gets exhausted, a MaxReferencedEntityVisitsExhaustedException is thrown.
	 */
	public function __construct(
		EntityLookup $entityLookup,
		EntityPrefetcher $entityPrefetcher,
		$maxDepth,
		$maxEntityVisits
	) {
		$this->entityLookup = $entityLookup;
		$this->entityPrefetcher = $entityPrefetcher;
		$this->maxDepth = $maxDepth;
		$this->maxEntityVisits = $maxEntityVisits;
	}

	/**
	 * Get the closest entity (out of $toIds), from a given entity. The starting entity, and
	 * the target entities are (potentially indirectly, via intermediate entities) linked by
	 * statements with the given property ID, pointing from the starting entity to one of the
	 * target entities.
	 *
	 * @since 3.10
	 *
	 * @param EntityId $fromId
	 * @param PropertyId $propertyId
	 * @param EntityId[] $toIds
	 *
	 * @return EntityId|null Returns null in case none of the target entities are referenced.
	 * @throws ReferencedEntityIdLookupException
	 */
	public function getReferencedEntityId( EntityId $fromId, PropertyId $propertyId, array $toIds ) {
		if ( !$toIds ) {
			return null;
		}

		$this->alreadyVisited = [];

		$steps = $this->maxDepth + 1; // Add one as checking $fromId already is a step
		$toVisit = [ $fromId ];

		while ( $steps-- ) {
			$this->entityPrefetcher->prefetch( $toVisit );
			$toVisitNext = [];

			foreach ( $toVisit as $curId ) {
				$result = $this->processEntityById( $curId, $fromId, $propertyId, $toIds, $toVisitNext );
				if ( $result ) {
					return $result;
				}
			}
			// Remove already visited entities
			$toVisit = array_unique(
				array_diff( $toVisitNext, array_keys( $this->alreadyVisited ) )
			);

			if ( !$toVisit ) {
				return null;
			}
		}

		// Exhausted the max. depth without finding anything.
		throw new MaxReferenceDepthExhaustedException(
			$fromId,
			$propertyId,
			$toIds,
			$this->maxDepth
		);
	}

	/**
	 * Find out whether an entity (directly) references one of the target ids.
	 *
	 * @param EntityId $id Id of the entity to process
	 * @param EntityId $fromId Id this lookup started from
	 * @param PropertyId $propertyId
	 * @param EntityId[] $toIds
	 * @param EntityId[] &$toVisit List of entities that still need to be checked
	 * @return EntityId|null Target id the entity refers to, null if none.
	 */
	private function processEntityById(
		EntityId $id,
		EntityId $fromId,
		PropertyId $propertyId,
		array $toIds,
		array &$toVisit
	) {
		$entity = $this->getEntity( $id, $fromId, $propertyId, $toIds );
		if ( !$entity ) {
			return null;
		}

		$mainSnaks = $this->getMainSnaks( $entity, $propertyId );

		foreach ( $mainSnaks as $mainSnak ) {
			$result = $this->processSnak( $mainSnak, $toVisit, $toIds );
			if ( $result ) {
				return $result;
			}
		}

		return null;
	}

	/**
	 * @param EntityId $id Id of the entity to get
	 * @param EntityId $fromId Id this lookup started from
	 * @param PropertyId $propertyId
	 * @param EntityId[] $toIds
	 *
	 * @return StatementListProvider|null Null if not applicable.
	 */
	private function getEntity( EntityId $id, EntityId $fromId, PropertyId $propertyId, array $toIds ) {
		if ( isset( $this->alreadyVisited[$id->getSerialization()] ) ) {
			trigger_error(
				'Entity ' . $id->getSerialization() . ' already visited.',
				E_USER_WARNING
			);

			return null;
		}

		$this->alreadyVisited[$id->getSerialization()] = true;

		if ( count( $this->alreadyVisited ) > $this->maxEntityVisits ) {
			throw new MaxReferencedEntityVisitsExhaustedException(
				$fromId,
				$propertyId,
				$toIds,
				$this->maxEntityVisits
			);
		}

		try {
			$entity = $this->entityLookup->getEntity( $id );
		} catch ( EntityLookupException $ex ) {
			throw new ReferencedEntityIdLookupException( $fromId, $propertyId, $toIds, null, $ex );
		}

		if ( !( $entity instanceof StatementListProvider ) ) {
			return null;
		}

		return $entity;
	}

	/**
	 * Decide whether a single Snak is pointing to one of the target ids.
	 *
	 * @param Snak $snak
	 * @param EntityId[] &$toVisit List of entities that still need to be checked
	 * @param EntityId[] $toIds
	 * @return EntityId|null Target id the Snak refers to, null if none.
	 */
	private function processSnak( Snak $snak, array &$toVisit, array $toIds ) {
		if ( !( $snak instanceof PropertyValueSnak ) ) {
			return null;
		}
		$dataValue = $snak->getDataValue();
		if ( !( $dataValue instanceof EntityIdValue ) ) {
			return null;
		}

		$entityId = $dataValue->getEntityId();
		if ( in_array( $entityId, $toIds, false ) ) {
			return $entityId;
		}

		$toVisit[] = $entityId;

		return null;
	}

	/**
	 * @param StatementListProvider $statementListProvider
	 * @param PropertyId $propertyId
	 * @return Snak[]
	 */
	private function getMainSnaks(
		StatementListProvider $statementListProvider,
		PropertyId $propertyId
	) {
		return $statementListProvider
			->getStatements()
			->getByPropertyId( $propertyId )
			->getBestStatements()
			->getMainSnaks();
	}

}
