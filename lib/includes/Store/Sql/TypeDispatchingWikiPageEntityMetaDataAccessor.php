<?php

namespace Wikibase\Lib\Store\Sql;

use InvalidArgumentException;
use stdClass;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\LookupConstants;
use Wikimedia\Assert\Assert;

/**
 * Accessor that can dispatch to internal Accessors based on the entity type of IDs provided.
 * As the accessor includes batched access this class will batch entity IDs by type before
 * using the internal accessors.
 * This is needed as the EntityMetaDataAccessor implements queries against the page table base
 * on entity IDs that won't work for all entities.
 *
 * @author Addshore
 * @license GPL-2.0-or-later
 */
class TypeDispatchingWikiPageEntityMetaDataAccessor implements WikiPageEntityMetaDataAccessor {

	/**
	 * @var callable[]|WikiPageEntityMetaDataAccessor[] indexed by entity type
	 */
	private $accessors;

	/**
	 * @var WikiPageEntityMetaDataAccessor
	 */
	private $defaultAccessor;

	/**
	 * @var string|false
	 */
	private $databaseName;

	/**
	 * @var string
	 */
	private $repositoryName;

	/**
	 * @param callable[]|WikiPageEntityMetaDataAccessor[] $instansiators
	 * An associative array mapping entity types to instansiator callbacks.
	 *  Callback signature: function( $databaseName, $repositoryName ): WikiPageEntityMetaDataAccessor
	 *  Parameter Types: string|false $databaseName, string $repositoryName
	 * @param WikiPageEntityMetaDataAccessor $defaultAccessor,
	 * @param string|false $databaseName
	 * @param string $repositoryName
	 */
	public function __construct(
		array $instansiators,
		WikiPageEntityMetaDataAccessor $defaultAccessor,
		$databaseName,
		$repositoryName
	) {
		Assert::parameterElementType(
			'callable|' . WikiPageEntityMetaDataAccessor::class,
			$instansiators,
			'$instansiators'
		);
		$this->accessors = $instansiators;
		$this->defaultAccessor = $defaultAccessor;
		$this->databaseName = $databaseName;
		$this->repositoryName = $repositoryName;
	}

	/**
	 * @see WikiPageEntityMetaDataAccessor::loadRevisionInformation
	 *
	 * @param EntityId[] $entityIds
	 * @param string $mode ( LookupConstants::LATEST_FROM_REPLICA,
	 *     LookupConstants::LATEST_FROM_REPLICA_WITH_FALLBACK or
	 *     LookupConstants::LATEST_FROM_MASTER)
	 *
	 * @return (stdClass|bool)[] Array mapping entity ID serializations to either objects
	 * or false if an entity could not be found.
	 */
	public function loadRevisionInformation( array $entityIds, $mode ) {
		$groupedIds = $this->groupIdsByType( $entityIds );
		$result = [];

		foreach ( $groupedIds as $entityType => $entityIdsForType ) {
			$accessor = $this->getAccessor( $entityType );
			$result = array_merge(
				$result,
				$accessor->loadRevisionInformation( $entityIdsForType, $mode )
			);
		}

		return $result;
	}

	/**
	 * @see WikiPageEntityMetaDataAccessor::loadRevisionInformationByRevisionId
	 *
	 * @param EntityId $entityId
	 * @param int $revisionId Revision id to fetch data about, must be an integer greater than 0.
	 * @param string $mode ( LookupConstants::LATEST_FROM_REPLICA,
	 *     LookupConstants::LATEST_FROM_REPLICA_WITH_FALLBACK or
	 *     LookupConstants::LATEST_FROM_MASTER).
	 *
	 * @return stdClass|bool false if no such entity exists
	 */
	public function loadRevisionInformationByRevisionId(
		EntityId $entityId,
		$revisionId,
		$mode = LookupConstants::LATEST_FROM_MASTER
	) {
		return $this
			->getAccessor( $entityId->getEntityType() )
			->loadRevisionInformationByRevisionId( $entityId, $revisionId, $mode );
	}

	/**
	 * @see WikiPageEntityMetaDataAccessor::loadLatestRevisionIds
	 *
	 * @param EntityId[] $entityIds
	 * @param string $mode ( LookupConstants::LATEST_FROM_REPLICA,
	 *     LookupConstants::LATEST_FROM_REPLICA_WITH_FALLBACK or
	 *     LookupConstants::LATEST_FROM_MASTER)
	 *
	 * @return (int|bool)[] Array mapping entity ID serializations to either revision IDs
	 * or false if an entity could not be found (including if the page is a redirect).
	 */
	public function loadLatestRevisionIds( array $entityIds, $mode ) {
		$groupedIds = $this->groupIdsByType( $entityIds );
		$result = [];

		foreach ( $groupedIds as $entityType => $entityIdsForType ) {
			$accessor = $this->getAccessor( $entityType );
			$result = array_merge(
				$result,
				$accessor->loadLatestRevisionIds( $entityIdsForType, $mode )
			);
		}

		return $result;
	}

	/**
	 * @param EntityId[] $entityIds
	 * @return array[]
	 */
	private function groupIdsByType( array $entityIds ) {
		$groupedIds = [];
		foreach ( $entityIds as $entityId ) {
			$groupedIds[$entityId->getEntityType()][] = $entityId;
		}
		return $groupedIds;
	}

	/**
	 * @param string $entityType
	 *
	 * @throws InvalidArgumentException
	 * @return WikiPageEntityMetaDataAccessor
	 */
	private function getAccessor( $entityType ) {
		if ( !array_key_exists( $entityType, $this->accessors ) ) {
			return $this->defaultAccessor;
		}

		if ( is_callable( $this->accessors[$entityType] ) ) {
			$this->accessors[$entityType] = call_user_func(
				$this->accessors[$entityType],
				$this->databaseName,
				$this->repositoryName
			);

			Assert::postcondition(
				$this->accessors[$entityType] instanceof WikiPageEntityMetaDataAccessor,
				"Callback provided for $entityType must create a WikiPageEntityMetaDataAccessor"
			);
		}

		return $this->accessors[$entityType];
	}

}
