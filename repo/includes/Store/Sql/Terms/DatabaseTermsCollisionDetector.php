<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Store\Sql\Terms;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lib\Rdbms\RepoDomainDb;
use Wikibase\Lib\Store\Sql\Terms\TypeIdsLookup;
use Wikibase\Repo\Store\TermsCollisionDetector;

/**
 * Queries db term store for collisions on terms
 *
 * @license GPL-2.0-or-later
 */
class DatabaseTermsCollisionDetector implements TermsCollisionDetector {

	private string $entityType;

	private RepoDomainDb $db;

	private TypeIdsLookup $typeIdsLookup;

	/**
	 * @param string $entityType one of the two supported types: Item::ENTITY_TYPE or Property::ENTITY_TYPE
	 * @param RepoDomainDb $db
	 * @param TypeIdsLookup $typeIdsLookup
	 *
	 * @throws InvalidArgumentException when non supported entity type is given
	 */
	public function __construct(
		string $entityType,
		RepoDomainDb $db,
		TypeIdsLookup $typeIdsLookup
	) {
		if ( !in_array( $entityType, [ Item::ENTITY_TYPE, Property::ENTITY_TYPE ] ) ) {
			throw new InvalidArgumentException(
				'$entityType must be a string, with either "item" or "property" as a value'
			);
		}

		$this->entityType = $entityType;
		$this->db = $db;
		$this->typeIdsLookup = $typeIdsLookup;
	}

	/**
	 * Returns an entity id that collides with given label in given language, if any
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

		return $this->makeEntityId( $entityId );
	}

	/**
	 * Returns an entity id that collides with given label and description in given languages, if any
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

		return $this->makeEntityId( $entityId );
	}

	public function detectLabelsCollision( TermList $termList ): array {
		if ( $termList->isEmpty() ) {
			return [];
		}

		$labelTypeId = $this->typeIdsLookup->lookupTypeIds( [ 'label' ] )['label'] ?? null;

		if ( $labelTypeId === null ) {
			return [];
		}
		$lang = [];
		$labels = [];

		foreach ( $termList->getIterator() as $label ) {
			$lang[] = $label->getLanguageCode();
			$labels[] = $label->getText();
		}

		return $this->findEntityIdsWithTermsInLangs( $lang, $labels, $labelTypeId );
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
			return NumericPropertyId::newFromNumber( $numericEntityId );
		}
	}

	private function findEntityIdsWithTermInLang(
		string $lang,
		string $text,
		int $termTypeId,
		bool $firstMatchOnly = false,
		array $filterOnEntityIds = []
	): array {
		$queryBuilder = $this->newSelectQueryBuilder();
		$queryBuilder->whereTerm( $termTypeId, $lang, $text );

		if ( !empty( $filterOnEntityIds ) ) {
			$queryBuilder->andWhere( [
				$queryBuilder->getEntityIdColumn() => $filterOnEntityIds,
			] );
		}
		$queryBuilder->caller( __METHOD__ );

		if ( $firstMatchOnly ) {
			$match = $queryBuilder->fetchField();
			return $match === false ? [] : [ $match ];
		} else {
			return $queryBuilder->fetchFieldValues();
		}
	}

	private function findEntityIdsWithTermsInLangs(
		array $lang,
		array $text,
		int $termTypeId
	): array {
		$queryBuilder = $this->newSelectQueryBuilder()
			->select( [ 'wbx_text', 'wbxl_language' ] )
			->distinct()
			->whereMultiTerm( $termTypeId, $lang, $text );
		$res = $queryBuilder->caller( __METHOD__ )->fetchResultSet();

		if ( $res === false ) {
			// Log warning?
			return [];
		}

		$values = [];
		foreach ( $res as $row ) {
			$dbEntityId = $row->{$queryBuilder->getEntityIdColumn()};
			$entityId = $this->makeEntityId( $dbEntityId );

			if ( !$entityId ) {
				throw new \RuntimeException( "Select result contains entityIds that are null." );
			}

			$values[$entityId->getSerialization()][] = new Term( $row->wbxl_language, $row->wbx_text );
		}

		return $values;
	}

	private function newSelectQueryBuilder(): EntityTermsSelectQueryBuilder {
		return new EntityTermsSelectQueryBuilder(
			$this->db->connections()->getReadConnection(),
			$this->entityType
		);
	}

}
