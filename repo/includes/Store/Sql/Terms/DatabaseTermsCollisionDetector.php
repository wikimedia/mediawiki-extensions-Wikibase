<?php

namespace Wikibase\Repo\Store\Sql\Terms;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Store\Sql\Terms\TypeIdsLookup;
use Wikibase\Repo\Store\TermsCollisionDetector;
use Wikimedia\Rdbms\ILoadBalancer;

/**
 * Queries db term store for collisions on terms
 *
 * @license GPL-2.0-or-later
 */
class DatabaseTermsCollisionDetector implements TermsCollisionDetector {

	/** @var string */
	private $entityType;

	/** @var ILoadBalancer */
	private $loadBalancer;

	/** @var TypeIdsLookup */
	private $typeIdsLookup;

	/** @var DatabaseEntityTermsTableProvider */
	private $databaseEntityTermsTableProvider;

	/**
	 * @param string $entityType one of the two supported types: Item::ENTITY_TYPE or Property::ENTITY_TYPE
	 * @param ILoadBalancer $loadBalancer
	 * @param TypeIdsLookup $typeIdsLookup
	 *
	 * @throws InvalidArgumentException when non supported entity type is given
	 */
	public function __construct(
		string $entityType,
		ILoadBalancer $loadBalancer,
		TypeIdsLookup $typeIdsLookup
	) {
		if ( !in_array( $entityType, [ Item::ENTITY_TYPE, Property::ENTITY_TYPE ] ) ) {
			throw new InvalidArgumentException(
				'$entityType must be a string, with either "item" or "property" as a value'
			);
		}

		$this->databaseEntityTermsTableProvider = new DatabaseEntityTermsTableProvider( $entityType );
		$this->entityType = $entityType;
		$this->loadBalancer = $loadBalancer;
		$this->typeIdsLookup = $typeIdsLookup;
	}

	/**
	 * Returns an entity id that collides with given label in given language, if any
	 * @param string $lang
	 * @param string $label
	 * @return EntityId|null
	 */
	public function detectLabelCollision(
		string $lang,
		string $label
	): ?EntityId {
		$labelTypeId = $this->typeIdsLookup->lookupTypeIds( [ 'label' ] )['label'] ?? null;

		if ( $labelTypeId === null ) {
			return null;
		}

		$entityId = $this->findEntityIdsWithTermInLang( $lang, $label, $labelTypeId, true )[0] ?? null;

		return $entityId !== null ? $this->makeEntityId( $entityId ) : null;
	}

	/**
	 * Returns an entity id that collides with given label and description in given languages, if any
	 * @param string $lang
	 * @param string $label
	 * @param string $description
	 * @return EntityId|null numeric entity id of colliding entity, if any
	 */
	public function detectLabelAndDescriptionCollision(
		string $lang,
		string $label,
		string $description
	): ?EntityId {
		$labelTypeId = $this->typeIdsLookup->lookupTypeIds( [ 'label' ] )['label'] ?? null;
		$descTypeId = $this->typeIdsLookup->lookupTypeIds( [ 'description' ] )['description'] ?? null;

		if ( $labelTypeId === null || $descTypeId === null ) {
			return null;
		}

		$entityIdsWithLabel = $this->findEntityIdsWithTermInLang( $lang, $label, $labelTypeId );

		if ( empty( $entityIdsWithLabel ) ) {
			return null;
		}

		$entityId = $this->findEntityIdsWithTermInLang(
			$lang,
			$description,
			$descTypeId,
			true,
			$entityIdsWithLabel
		)[0] ?? null;

		return $entityId !== null ? $this->makeEntityId( $entityId ) : null;
	}

	/**
	 * @param mixed|null $numericEntityId
	 * @return EntityId|null
	 */
	private function makeEntityId( $numericEntityId ): ?EntityId {
		if ( !$numericEntityId ) {
			return null;
		}

		return $this->composeEntityId( $numericEntityId );
	}

	private function composeEntityId( $numericEntityId ) {
		if ( $this->entityType === Item::ENTITY_TYPE ) {
			return ItemId::newFromNumber( $numericEntityId );
		} elseif ( $this->entityType === Property::ENTITY_TYPE ) {
			return PropertyId::newFromNumber( $numericEntityId );
		}
	}

	private function findEntityIdsWithTermInLang(
		$lang,
		string $text,
		int $termTypeId,
		bool $firstMatchOnly = false,
		array $filterOnEntityIds = []
	): array {

		list(
			$table,
			$joinConditions,
			$conditions,
			$entityIdColumn
		) = $this->getTermQueryParams( 'term', $termTypeId, $lang, $text );

		if ( !empty( $filterOnEntityIds ) ) {
			$conditions[ $entityIdColumn ] = $filterOnEntityIds;
		}

		$dbr = $this->getDbr();

		if ( $firstMatchOnly === true ) {

			$match = $dbr->selectField(
				$table,
				$entityIdColumn,
				$conditions,
				__METHOD__,
				[],
				$joinConditions
			);

			return $match === false ? [] : [ $match ];

		} else {

			return $dbr->selectFieldValues(
				$table,
				$entityIdColumn,
				$conditions,
				__METHOD__,
				[],
				$joinConditions
			);

		}
	}

	private function getTermQueryParams( $alias, $typeId, $lang, $text ) {
		list(
			$table,
			$joinConditions,
			$entityIdColumn
		) = $this->databaseEntityTermsTableProvider->getEntityTermsTableAndJoinConditions( $alias );

		$conditions = [
			"{$alias}TermInLang.wbtl_type_id" => $typeId,
			"{$alias}TextInLang.wbxl_language" => $lang,
			"{$alias}Text.wbx_text" => $text
		];

		return [ $table, $joinConditions, $conditions, $entityIdColumn ];
	}

	private function getDbr() {
		return $this->loadBalancer->getConnection( ILoadBalancer::DB_REPLICA, [] );
	}
}
