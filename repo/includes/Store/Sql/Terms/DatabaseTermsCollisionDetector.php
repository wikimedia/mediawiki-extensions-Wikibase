<?php

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
use Wikimedia\Rdbms\IDatabase;

/**
 * Queries db term store for collisions on terms
 *
 * @license GPL-2.0-or-later
 */
class DatabaseTermsCollisionDetector implements TermsCollisionDetector {

	/** @var string */
	private $entityType;

	/** @var RepoDomainDb */
	private $db;

	/** @var TypeIdsLookup */
	private $typeIdsLookup;

	/** @var DatabaseEntityTermsTableProvider */
	private $databaseEntityTermsTableProvider;

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

		$this->databaseEntityTermsTableProvider = new DatabaseEntityTermsTableProvider( $entityType );
		$this->entityType = $entityType;
		$this->db = $db;
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

		// @phan-suppress-next-line SecurityCheck-SQLInjection
		$entityId = $this->findEntityIdsWithTermInLang(
			$lang,
			$description,
			$descTypeId,
			true,
			$entityIdsWithLabel
		)[0] ?? null;

		return $entityId !== null ? $this->makeEntityId( $entityId ) : null;
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

		list(
			$table,
			$joinConditions,
			$conditions,
			$entityIdColumn
		) = $this->getTermQueryParams( $termTypeId, $lang, $text );

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

	private function findEntityIdsWithTermsInLangs(
		array $lang,
		array $text,
		int $termTypeId
	): array {

		$dbr = $this->getDbr();
		$options = [];

		list(
			$table,
			$joinConditions,
			$conditions,
			$entityIdColumn
		) = $this->getMultiTermQueryParams( $termTypeId, $lang, $text );

		$labelStatements = [];
		foreach ( $conditions as $condition ) {
			$labelStatements[] = $dbr->makeList( $condition, $dbr::LIST_AND );
		}

		$conditions = $dbr->makeList( $labelStatements, $dbr::LIST_OR );
		$options[] = 'DISTINCT';

		$res = $dbr->select(
			$table,
			[ $entityIdColumn, 'wbx_text', 'wbxl_language' ],
			$conditions,
			__METHOD__,
			$options,
			$joinConditions
		);

		if ( $res === false ) {
			// Log warning?
			return [];
		}

		$values = [];
		foreach ( $res as $row ) {
			$dbEntityId = $row->{$entityIdColumn};
			$entityId = $this->makeEntityId( $dbEntityId );

			if ( !$entityId ) {
				throw new \RuntimeException( "Select result contains entityIds that are null." );
			}

			$values[$entityId->getSerialization()][] = new Term( $row->wbxl_language, $row->wbx_text );
		}

		return $values;
	}

	/**
	 * @param string $typeId
	 * @param string $lang
	 * @param string $text
	 * @return array
	 */
	private function getTermQueryParams( $typeId, $lang, $text ) {
		list(
			$table,
			$joinConditions,
			$entityIdColumn
		) = $this->databaseEntityTermsTableProvider->getEntityTermsTableAndJoinConditions();

		return [
			$table,
			$joinConditions,
			$this->getTermInLanguageCondition( $typeId, $lang, $text ),
			$entityIdColumn,
		];
	}

	/**
	 * @param string $typeId
	 * @param array $languages
	 * @param array $texts
	 * @return array
	 */
	private function getMultiTermQueryParams( $typeId, $languages, $texts ) {
		list(
			$table,
			$joinConditions,
			$entityIdColumn
		) = $this->databaseEntityTermsTableProvider->getEntityTermsTableAndJoinConditions();

		$conditions = [];

		for ( $i = 0; $i < count( $languages ); $i++ ) {
			$language = $languages[$i];
			$text = $texts[$i];

			$conditions[] = $this->getTermInLanguageCondition( $typeId, $language, $text );
		}

		return [ $table, $joinConditions, $conditions, $entityIdColumn ];
	}

	private function getTermInLanguageCondition( string $typeId, string $language, string $text ): array {
		return [
			"wbtl_type_id" => $typeId,
			"wbxl_language" => $language,
			"wbx_text" => $text,
		];
	}

	private function getDbr(): IDatabase {
		return $this->db->connections()->getReadConnection();
	}

}
