<?php

namespace Wikibase\Lib\Store\Sql;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\EntityRevisionLookup;

/**
 * @license GPL-2.0-or-later
 */
class TypeDispatchingWikiPageEntityMetaDataAccessor implements WikiPageEntityMetaDataAccessor {

	/**
	 * @var array indexed by entity type
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
	 * @param callable[] $callbacks indexed by entity type
	 * @param WikiPageEntityMetaDataAccessor $defaultAccessor,
	 * @param string|false $databaseName
	 * @param string $repositoryName
	 */
	public function __construct(
		array $callbacks,
		WikiPageEntityMetaDataAccessor $defaultAccessor,
		$databaseName,
		$repositoryName
	) {
		$this->accessors = $callbacks;
		$this->defaultAccessor = $defaultAccessor;
		$this->databaseName = $databaseName;
		$this->repositoryName = $repositoryName;
	}

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

	public function loadRevisionInformationByRevisionId(
		EntityId $entityId,
		$revisionId,
		$mode = EntityRevisionLookup::LATEST_FROM_MASTER
	) {
		return $this
			->getAccessor( $entityId->getEntityType() )
			->loadRevisionInformationByRevisionId( $entityId, $revisionId, $mode );
	}

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
	 * @return array
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

			if ( !( $this->accessors[$entityType] instanceof WikiPageEntityMetaDataAccessor ) ) {
				throw new InvalidArgumentException(
					"Callback provided for $entityType did not create an WikiPageEntityMetaDataAccessor"
				);
			}
		}

		return $this->accessors[$entityType];
	}

}
