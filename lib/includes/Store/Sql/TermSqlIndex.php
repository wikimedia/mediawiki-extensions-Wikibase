<?php

namespace Wikibase;

use Database;
use DBAccessBase;
use InvalidArgumentException;
use MWException;
use Traversable;
use Wikibase\DataModel\Assert\RepositoryNameAssert;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Int32EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Term\AliasesProvider;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\DescriptionsProvider;
use Wikibase\DataModel\Term\LabelsProvider;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lib\EntityIdComposer;
use Wikibase\Lib\Store\LabelConflictFinder;
use Wikibase\Lib\Store\TermIndexSearchCriteria;

/**
 * Term lookup cache.
 *
 * @license GPL-2.0+
 */
class TermSqlIndex extends DBAccessBase implements TermIndex, LabelConflictFinder {

	/**
	 * @var string
	 */
	private $repositoryName;

	/**
	 * @var string
	 */
	private $tableName;

	/**
	 * @var StringNormalizer
	 */
	private $stringNormalizer;

	/**
	 * @var EntityIdComposer
	 */
	private $entityIdComposer;

	/**
	 * @var bool
	 */
	private $writeFullEntityIdColumn;

	/**
	 * @var int
	 */
	private $maxConflicts = 500;

	/**
	 * @param StringNormalizer $stringNormalizer
	 * @param EntityIdComposer $entityIdComposer
	 * @param string|bool $wikiDb
	 * @param string $repositoryName
	 * @param bool $writeFullEntityIdColumn Allow writing to the column.
	 */
	public function __construct(
		StringNormalizer $stringNormalizer,
		EntityIdComposer $entityIdComposer,
		$wikiDb = false,
		$repositoryName = '',
		$writeFullEntityIdColumn = true
	) {
		RepositoryNameAssert::assertParameterIsValidRepositoryName( $repositoryName, '$repositoryName' );
		parent::__construct( $wikiDb );
		$this->repositoryName = $repositoryName;
		$this->stringNormalizer = $stringNormalizer;
		$this->entityIdComposer = $entityIdComposer;
		$this->writeFullEntityIdColumn = $writeFullEntityIdColumn;

		$this->tableName = 'wb_terms';
	}

	/**
	 * Returns the name of the database table used to store the terms.
	 * This is the logical table name, subject to prefixing by the Database object.
	 *
	 * @return string
	 */
	public function getTableName() {
		return $this->tableName;
	}

	/**
	 * @see TermIndex::saveTermsOfEntity
	 *
	 * @param EntityDocument $entity Must have an ID, and optionally any combination of terms as
	 *  declared by the TermIndexEntry::TYPE_... constants.
	 *
	 * @throws InvalidArgumentException when $entity does not have an ID.
	 * @throws MWException
	 *
	 * @return bool Success indicator
	 */
	public function saveTermsOfEntity( EntityDocument $entity ) {
		$entityId = $entity->getId();
		if ( $entityId === null ) {
			throw new InvalidArgumentException( '$entity must have an ID' );
		}

		$this->assertEntityIdFromRightRepository( $entityId );

		//First check whether there's anything to update
		$newTerms = $this->getEntityTerms( $entity );
		$oldTerms = $this->getTermsOfEntity( $entityId );

		$termsToInsert = array_udiff( $newTerms, $oldTerms, 'Wikibase\TermIndexEntry::compare' );
		$termsToDelete = array_udiff( $oldTerms, $newTerms, 'Wikibase\TermIndexEntry::compare' );

		if ( !$termsToInsert && !$termsToDelete ) {
			wfDebugLog( __CLASS__, __FUNCTION__ . ': terms did not change, returning.' );
			return true;
		}

		$ok = true;
		$dbw = $this->getConnection( DB_MASTER );

		if ( $ok && $termsToDelete ) {
			wfDebugLog( __CLASS__, __FUNCTION__ . ': ' . count( $termsToDelete ) . ' terms to delete.' );
			$ok = $this->deleteTerms( $entity->getId(), $termsToDelete, $dbw );
		}

		if ( $ok && $termsToInsert ) {
			wfDebugLog( __CLASS__, __FUNCTION__ . ': ' . count( $termsToInsert ) . ' terms to insert.' );
			$ok = $this->insertTerms( $entity, $termsToInsert, $dbw );
		}

		$this->releaseConnection( $dbw );

		return $ok;
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @throws MWException
	 */
	private function assertEntityIdFromRightRepository( EntityId $entityId ) {
		if ( $entityId->getRepositoryName() !== $this->repositoryName ) {
			throw new MWException(
				'Entity ID: ' . $entityId->getSerialization() . ' does not belong to repository: ' . $this->repositoryName
			);
		}
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @throws MWException
	 */
	private function assertIsNumericEntityId( EntityId $entityId ) {
		if ( !( $entityId instanceof Int32EntityId ) ) {
			throw new MWException(
				'Entity ID: ' . $entityId->getSerialization() . ' does not implement Int32EntityId'
			);
		}
	}

	/**
	 * @param EntityDocument $entity
	 * @param TermIndexEntry[] $terms
	 * @param Database $dbw
	 *
	 * @return bool Success indicator
	 */
	private function insertTerms( EntityDocument $entity, array $terms, Database $dbw ) {
		$entityId = $entity->getId();
		$this->assertIsNumericEntityId( $entityId );
		/** @var EntityId|Int32EntityId $entityId */

		$entityIdentifiers = array(
			'term_entity_id' => $entityId->getNumericId(),
			'term_entity_type' => $entity->getType(),
			'term_weight' => $this->getWeight( $entity ),
		);

		if ( $this->writeFullEntityIdColumn ) {
			$entityIdentifiers['term_full_entity_id'] = $entityId->getSerialization();
		}

		wfDebugLog( __CLASS__, __FUNCTION__ . ': inserting terms for ' . $entity->getId()->getSerialization() );

		$success = true;
		foreach ( $terms as $term ) {
			$success = $dbw->insert(
				$this->tableName,
				array_merge(
					$this->getTermFields( $term ),
					$entityIdentifiers
				),
				__METHOD__,
				array( 'IGNORE' )
			);

			if ( !$success ) {
				break;
			}
		}

		return $success;
	}

	/**
	 * @param EntityDocument $entity
	 *
	 * @return TermIndexEntry[]
	 * @throws MWException
	 */
	public function getEntityTerms( EntityDocument $entity ) {
		$id = $entity->getId();
		$this->assertEntityIdFromRightRepository( $id );

		$terms = [];

		if ( $entity instanceof DescriptionsProvider ) {
			$terms = array_merge( $terms, $this->getTermListTerms(
				TermIndexEntry::TYPE_DESCRIPTION,
				$entity->getDescriptions(),
				$id
			) );
		}

		if ( $entity instanceof LabelsProvider ) {
			$terms = array_merge( $terms, $this->getTermListTerms(
				TermIndexEntry::TYPE_LABEL,
				$entity->getLabels(),
				$id
			) );
		}

		if ( $entity instanceof AliasesProvider ) {
			$terms = array_merge( $terms, $this->getAliasGroupListTerms(
				$entity->getAliasGroups(),
				$id
			) );
		}

		return $terms;
	}

	/**
	 * @param string $termType
	 * @param TermList $termList
	 * @param EntityId $entityId
	 *
	 * @return TermIndexEntry[]
	 */
	private function getTermListTerms( $termType, TermList $termList, EntityId $entityId ) {
		$terms = [];

		foreach ( $termList->toTextArray() as $languageCode => $text ) {
			$terms[] = new TermIndexEntry( [
				'entityId' => $entityId,
				'termLanguage' => $languageCode,
				'termType' => $termType,
				'termText' => $text,
			] );
		}

		return $terms;
	}

	/**
	 * @param AliasGroupList $aliasGroupList
	 * @param EntityId $entityId
	 *
	 * @return TermIndexEntry[]
	 */
	private function getAliasGroupListTerms( AliasGroupList $aliasGroupList, EntityId $entityId ) {
		$terms = [];

		foreach ( $aliasGroupList->toArray() as $aliasGroup ) {
			$languageCode = $aliasGroup->getLanguageCode();

			foreach ( $aliasGroup->getAliases() as $alias ) {
				$terms[] = new TermIndexEntry( [
					'entityId' => $entityId,
					'termLanguage' => $languageCode,
					'termType' => TermIndexEntry::TYPE_ALIAS,
					'termText' => $alias,
				] );
			}
		}

		return $terms;
	}

	/**
	 * @param EntityId $entityId
	 * @param TermIndexEntry[] $terms
	 * @param Database $dbw
	 *
	 * @return bool Success indicator
	 */
	private function deleteTerms( EntityId $entityId, array $terms, Database $dbw ) {
		//TODO: Make getTermsOfEntity() collect term_row_id values, so we can use them here.
		//      That would allow us to do the deletion in a single query, based on a set of ids.

		$this->assertIsNumericEntityId( $entityId );
		/** @var EntityId|Int32EntityId $entityId */

		$entityIdentifiers = array(
			'term_entity_id' => $entityId->getNumericId(),
			'term_entity_type' => $entityId->getEntityType()
		);

		$uniqueKeyFields = array( 'term_entity_type', 'term_entity_id', 'term_language', 'term_type', 'term_text' );

		wfDebugLog( __CLASS__, __FUNCTION__ . ': deleting terms for ' . $entityId->getSerialization() );

		$success = true;
		foreach ( $terms as $term ) {
			$termIdentifiers = $this->getTermFields( $term );
			$termIdentifiers = array_intersect_key( $termIdentifiers, array_flip( $uniqueKeyFields ) );

			$success = $dbw->delete(
				$this->tableName,
				array_merge(
					$termIdentifiers,
					$entityIdentifiers
				),
				__METHOD__
			);

			if ( !$success ) {
				break;
			}
		}

		return $success;
	}

	/**
	 * Calculate a weight the given entity to be used for ranking. Should be normalized
	 * between 0 and 1, but that's not a strong constraint.
	 * This implementation uses the max of the number of labels and the number of sitelinks.
	 *
	 * TODO Should be moved to its own object and be added via dependency injection
	 *
	 * @param EntityDocument $entity
	 *
	 * @return float
	 */
	private function getWeight( EntityDocument $entity ) {
		$weight = 0.0;

		if ( $entity instanceof LabelsProvider ) {
			$weight = max( $weight, $entity->getLabels()->count() / 1000.0 );
		}

		if ( $entity instanceof Item ) {
			$weight = max( $weight, $entity->getSiteLinkList()->count() / 1000.0 );
		}

		return $weight;
	}

	/**
	 * Returns an array with the database table fields for the provided term.
	 *
	 * @param TermIndexEntry $term
	 *
	 * @return string[]
	 */
	private function getTermFields( TermIndexEntry $term ) {
		$fields = array(
			'term_language' => $term->getLanguage(),
			'term_type' => $term->getTermType(),
			'term_text' => $term->getText(),
			'term_search_key' => $this->getSearchKey( $term->getText() )
		);

		return $fields;
	}

	/**
	 * @see TermIndex::deleteTermsOfEntity
	 *
	 * @param EntityId $entityId
	 *
	 * @return bool Success indicator
	 */
	public function deleteTermsOfEntity( EntityId $entityId ) {
		$this->assertEntityIdFromRightRepository( $entityId );
		$this->assertIsNumericEntityId( $entityId );
		/** @var EntityId|Int32EntityId $entityId */

		$dbw = $this->getConnection( DB_MASTER );

		$success = $dbw->delete(
			$this->tableName,
			array(
				'term_entity_id' => $entityId->getNumericId(),
				'term_entity_type' => $entityId->getEntityType()
			),
			__METHOD__
		);

		// NOTE: if we fail to delete some labels, it may not be possible to use those labels
		// for other entities, without any way to remove them from the database.
		// We probably want some extra handling here.

		return $success;
	}

	/**
	 * Returns the terms stored for the given entity.
	 *
	 * @see TermIndex::getTermsOfEntity
	 *
	 * @param EntityId $entityId
	 * @param string[]|null $termTypes
	 * @param string[]|null $languageCodes
	 *
	 * @return TermIndexEntry[]
	 * @throws MWException
	 */
	public function getTermsOfEntity(
		EntityId $entityId,
		array $termTypes = null,
		array $languageCodes = null
	) {
		$this->assertEntityIdFromRightRepository( $entityId );

		return $this->getTermsOfEntities(
			array( $entityId ),
			$termTypes,
			$languageCodes
		);
	}

	/**
	 * Returns the terms stored for the given entities.
	 *
	 * @see TermIndex::getTermsOfEntities
	 *
	 * @param EntityId[] $entityIds
	 * @param string[]|null $termTypes
	 * @param string[]|null $languageCodes
	 *
	 * @throws MWException
	 * @return TermIndexEntry[]
	 */
	public function getTermsOfEntities(
		array $entityIds,
		array $termTypes = null,
		array $languageCodes = null
	) {
		foreach ( $entityIds as $id ) {
			$this->assertEntityIdFromRightRepository( $id );
		}

		// Fetch up to 9 (as suggested by the DBA) terms each time:
		// https://phabricator.wikimedia.org/T163544#3201562
		$entityIdBatches = array_chunk( $entityIds, 9 );
		$terms = [];

		foreach ( $entityIdBatches as $entityIdBatch ) {
			$terms = array_merge(
				$terms,
				$this->fetchTerms( $entityIdBatch, $termTypes, $languageCodes )
			);
		}

		return $terms;
	}

	/**
	 * @param EntityId[] $entityIds
	 * @param string[]|null $termTypes
	 * @param string[]|null $languageCodes
	 *
	 * @throws MWException
	 * @return TermIndexEntry[]
	 */
	private function fetchTerms(
		array $entityIds,
		array $termTypes = null,
		array $languageCodes = null
	) {
		if ( $entityIds === [] || $termTypes === [] || $languageCodes === [] ) {
			return [];
		}

		$entityType = null;
		$numericIds = array();

		foreach ( $entityIds as $id ) {
			if ( $entityType === null ) {
				$entityType = $id->getEntityType();
			} elseif ( $id->getEntityType() !== $entityType ) {
				throw new MWException( "ID $id does not refer to an entity of type $entityType" );
			}

			$this->assertIsNumericEntityId( $id );
			/** @var Int32EntityId $id */
			$numericIds[] = $id->getNumericId();
		}

		$conditions = array(
			'term_entity_type' => $entityType,
			'term_entity_id' => $numericIds,
		);

		if ( $languageCodes !== null ) {
			$conditions['term_language'] = $languageCodes;
		}

		if ( $termTypes !== null ) {
			$conditions['term_type'] = $termTypes;
		}

		$dbr = $this->getReadDb();

		$res = $dbr->select(
			$this->tableName,
			[ 'term_entity_type', 'term_type', 'term_language', 'term_text', 'term_entity_id' ],
			$conditions,
			__METHOD__
		);

		$terms = $this->buildTermResult( $res );

		$this->releaseConnection( $dbr );

		return $terms;
	}

	/**
	 * Returns the Database connection from which to read.
	 *
	 * @return Database
	 */
	public function getReadDb() {
		return $this->getConnection( DB_REPLICA );
	}

	/**
	 * Returns the Database connection to which to write.
	 *
	 * @return Database
	 */
	public function getWriteDb() {
		return $this->getConnection( DB_MASTER );
	}

	/**
	 * @see TermIndex::getMatchingTerms
	 *
	 * @param TermIndexSearchCriteria[] $criteria
	 * @param string|string[]|null $termType
	 * @param string|string[]|null $entityType
	 * @param array $options
	 *
	 * @return TermIndexEntry[]
	 */
	public function getMatchingTerms(
		array $criteria,
		$termType = null,
		$entityType = null,
		array $options = array()
	) {
		if ( empty( $criteria ) ) {
			return array();
		}

		$dbr = $this->getReadDb();

		$termConditions = $this->criteriaToConditions( $dbr, $criteria, $termType, $entityType, $options );

		$queryOptions = array();
		if ( isset( $options['LIMIT'] ) && $options['LIMIT'] > 0 ) {
			$queryOptions['LIMIT'] = $options['LIMIT'];
		}

		$rows = $dbr->select(
			$this->tableName,
			[
				'term_entity_type',
				'term_type',
				'term_language',
				'term_text',
				'term_entity_id',
				'term_weight'
			],
			array( $dbr->makeList( $termConditions, LIST_OR ) ),
			__METHOD__,
			$queryOptions
		);

		if ( array_key_exists( 'orderByWeight', $options ) && $options['orderByWeight'] ) {
			$rows = $this->getRowsOrderedByWeight( $rows );
		}

		$terms = $this->buildTermResult( $rows );

		$this->releaseConnection( $dbr );

		return $terms;
	}

	/**
	 * @see TermIndex::getTopMatchingTerms
	 *
	 * @param TermIndexSearchCriteria[] $criteria
	 * @param string|string[]|null $termType
	 * @param string|string[]|null $entityType
	 * @param array $options
	 *           In this implementation at most 2500 terms will be retreived.
	 *           As we only return a single TermIndexEntry per Entity the return count may be lower.
	 *
	 * @return TermIndexEntry[]
	 */
	public function getTopMatchingTerms(
		array $criteria,
		$termType = null,
		$entityType = null,
		array $options = array()
	) {
		$requestedLimit = 0;
		if ( array_key_exists( 'LIMIT', $options ) ) {
			$requestedLimit = $options['LIMIT'];
		}
		$options['LIMIT'] = 2500;
		$options['orderByWeight'] = true;

		$matchingTermIndexEntries = $this->getMatchingTerms(
			$criteria,
			$termType,
			$entityType,
			$options
		);

		$returnTermIndexEntries = array();
		foreach ( $matchingTermIndexEntries as $indexEntry ) {
			$entityIdSerilization = $indexEntry->getEntityId()->getSerialization();
			if ( !array_key_exists( $entityIdSerilization, $returnTermIndexEntries ) ) {
				$returnTermIndexEntries[$entityIdSerilization] = $indexEntry;
			}
		}

		if ( $requestedLimit > 0 ) {
			$returnTermIndexEntries = array_slice( $returnTermIndexEntries, 0, $requestedLimit, true );
		}

		return array_values( $returnTermIndexEntries );
	}

	/**
	 * @param Traversable $rows
	 * @param int $limit
	 *
	 * @return object[]
	 */
	private function getRowsOrderedByWeight( Traversable $rows, $limit = 0 ) {
		$sortData = array();
		$rowMap = array();

		foreach ( $rows as $key => $row ) {
			$termWeight = floatval( $row->term_weight );
			$sortData[$key]['weight'] = $termWeight;
			$sortData[$key]['string'] =
				$row->term_text .
				$row->term_type .
				$row->term_language .
				$row->term_entity_type .
				$row->term_entity_id;
			$rowMap[$key] = $row;
		}

		// this is a post-search sorting by weight. This allows us to not require an additional
		// index on the wb_terms table that is very big already. This is also why we have
		// the internal limit of 2500, since SQL's index would explode in size if we added the
		// weight to it here (which would allow us to delegate the sorting to SQL itself)
		uasort( $sortData, function( $a, $b ) {
			if ( $a['weight'] === $b['weight'] ) {
				return strcmp( $a['string'], $b['string'] );
			}
			return ( $a['weight'] < $b['weight'] ) ? 1 : -1;
		} );

		if ( $limit > 0 ) {
			$sortData = array_slice( $sortData, 0, $limit, true );
		}

		$entityIds = array();

		foreach ( $sortData as $key => $keySortData ) {
			$entityIds[] = $rowMap[$key];
		}

		return $entityIds;
	}

	/**
	 * @param Database $db
	 * @param TermIndexSearchCriteria[] $criteria
	 * @param string|string[]|null $termType
	 * @param string|string[]|null $entityType
	 * @param array $options
	 *
	 * @return string[]
	 */
	private function criteriaToConditions(
		Database $db,
		array $criteria,
		$termType = null,
		$entityType = null,
		array $options = array()
	) {
		$conditions = array();

		foreach ( $criteria as $mask ) {
			$termConditions = $this->getTermMatchConditions( $db, $mask, $termType, $entityType, $options );
			$conditions[] = $db->makeList( $termConditions, LIST_AND );
		}

		return $conditions;
	}

	/**
	 * @param Database $db
	 * @param TermIndexSearchCriteria $mask
	 * @param string|string[]|null $termType
	 * @param string|string[]|null $entityType
	 * @param array $options
	 *
	 * @return array
	 */
	private function getTermMatchConditions(
		Database $db,
		TermIndexSearchCriteria $mask,
		$termType = null,
		$entityType = null,
		array $options = array()
	) {
		$options = array_merge(
			array(
				'caseSensitive' => true,
				'prefixSearch' => false,
			),
			$options
		);

		$conditions = array();

		$language = $mask->getLanguage();

		if ( $language !== null ) {
			$conditions['term_language'] = $language;
		}

		$text = $mask->getText();

		if ( $text !== null ) {
			// NOTE: Whether this match is *actually* case sensitive depends on the collation
			// used in the database.
			$textField = 'term_text';

			if ( !$options['caseSensitive'] ) {
				$textField = 'term_search_key';
				$text = $this->getSearchKey( $mask->getText() );
			}

			if ( $options['prefixSearch'] ) {
				$conditions[] = $textField . $db->buildLike( $text, $db->anyString() );
			} else {
				$conditions[$textField] = $text;
			}
		}

		if ( $mask->getTermType() !== null ) {
			$conditions['term_type'] = $mask->getTermType();
		} elseif ( $termType !== null ) {
			$conditions['term_type'] = $termType;
		}

		if ( $entityType !== null ) {
			$conditions['term_entity_type'] = $entityType;
		}

		return $conditions;
	}

	/**
	 * Modifies the provided terms to use the field names expected by the interface
	 * rather then the table field names. Also ensures the values are of the correct type.
	 *
	 * @param object[]|Traversable $obtainedTerms
	 *
	 * @return TermIndexEntry[]
	 */
	private function buildTermResult( $obtainedTerms ) {
		$matchingTerms = array();

		foreach ( $obtainedTerms as $obtainedTerm ) {
			$matchingTerms[] = new TermIndexEntry( [
				'entityId' => $this->getEntityId( $obtainedTerm ),
				'termType' => $obtainedTerm->term_type,
				'termLanguage' => $obtainedTerm->term_language,
				'termText' => $obtainedTerm->term_text,
			] );
		}

		return $matchingTerms;
	}

	/**
	 * @param object $termRow
	 *
	 * @return EntityId|null
	 */
	private function getEntityId( $termRow ) {
		if ( isset( $termRow->term_entity_type ) && isset( $termRow->term_entity_id ) ) {
			return $this->entityIdComposer->composeEntityId(
				$this->repositoryName,
				$termRow->term_entity_type,
				$termRow->term_entity_id
			);
		}

		return null;
	}

	/**
	 * @see TermIndex::clear
	 *
	 * @return bool Success indicator
	 */
	public function clear() {
		$dbw = $this->getConnection( DB_MASTER );
		$ok = $dbw->delete( $this->tableName, '*', __METHOD__ );
		$this->releaseConnection( $dbw );
		return $ok;
	}

	/**
	 * @see LabelConflictFinder::getLabelConflicts
	 *
	 * @note: This implementation does not guarantee that all matches are returned.
	 * The maximum number of conflicts returned is controlled by $this->maxConflicts.
	 *
	 * @param string $entityType
	 * @param string[] $labels
	 * @param array[]|null $aliases
	 *
	 * @throws InvalidArgumentException
	 * @return TermIndexEntry[]
	 */
	public function getLabelConflicts( $entityType, array $labels, array $aliases = null ) {
		if ( !is_string( $entityType ) ) {
			throw new InvalidArgumentException( '$entityType must be a string' );
		}

		if ( empty( $labels ) && empty( $aliases ) ) {
			return array();
		}

		$termTypes = ( $aliases === null )
			? array( TermIndexEntry::TYPE_LABEL )
			: array( TermIndexEntry::TYPE_LABEL, TermIndexEntry::TYPE_ALIAS );

		$termTexts = ( $aliases === null )
			? $labels
			: array_merge( $labels, $aliases );

		$templates = $this->makeQueryTerms( $termTexts, $termTypes );

		$labelConflicts = $this->getMatchingTerms(
			$templates,
			$termTypes,
			$entityType,
			array(
				'LIMIT' => $this->maxConflicts,
				'caseSensitive' => false
			)
		);

		return $labelConflicts;
	}

	/**
	 * @see LabelConflictFinder::getLabelWithDescriptionConflicts
	 *
	 * @note: This implementation does not guarantee that all matches are returned.
	 * The maximum number of conflicts returned is controlled by $this->maxConflicts.
	 *
	 * @param string $entityType
	 * @param string[] $labels
	 * @param string[] $descriptions
	 *
	 * @throws InvalidArgumentException
	 * @return TermIndexEntry[]
	 */
	public function getLabelWithDescriptionConflicts(
		$entityType,
		array $labels,
		array $descriptions
	) {
		$labels = array_intersect_key( $labels, $descriptions );
		$descriptions = array_intersect_key( $descriptions, $labels );

		if ( empty( $descriptions ) || empty( $labels ) ) {
			return array();
		}

		$dbr = $this->getReadDb();

		// FIXME: MySQL doesn't support self-joins on temporary tables,
		//        so skip this check during unit tests on MySQL!
		if ( defined( 'MW_PHPUNIT_TEST' ) && $dbr->getType() === 'mysql' ) {
			$this->releaseConnection( $dbr );
			return array();
		}

		$where = array();
		$where['L.term_entity_type'] = $entityType;
		$where['L.term_type'] = TermIndexEntry::TYPE_LABEL;
		$where['D.term_type'] = TermIndexEntry::TYPE_DESCRIPTION;

		$where[] = 'D.term_entity_id=' . 'L.term_entity_id';
		$where[] = 'D.term_entity_type=' . 'L.term_entity_type';

		$termConditions = array();

		foreach ( $labels as $lang => $label ) {
			// Due to the array_intersect_key call earlier, we know a corresponding description exists.
			$description = $descriptions[$lang];

			$matchConditions = array(
				'L.term_language' => $lang,
				'L.term_search_key' => $this->getSearchKey( $label ),
				'D.term_search_key' => $this->getSearchKey( $description )
			);

			$termConditions[] = $dbr->makeList( $matchConditions, LIST_AND );
		}

		$where[] = $dbr->makeList( $termConditions, LIST_OR );

		$queryOptions = array(
			'LIMIT' => $this->maxConflicts
		);

		$obtainedTerms = $dbr->select(
			array( 'L' => $this->tableName, 'D' => $this->tableName ),
			'L.*',
			$where,
			__METHOD__,
			$queryOptions
		);

		$conflicts = $this->buildTermResult( $obtainedTerms );

		$this->releaseConnection( $dbr );

		return $conflicts;
	}

	/**
	 * @param string[]|array[] $textsByLanguage A list of texts, or a list of lists of texts (keyed
	 *  by language on the top level).
	 * @param string[] $types
	 *
	 * @throws InvalidArgumentException
	 * @return TermIndexSearchCriteria[]
	 */
	private function makeQueryTerms( $textsByLanguage, array $types ) {
		$criteria = array();

		foreach ( $textsByLanguage as $lang => $texts ) {
			$texts = (array)$texts;

			foreach ( $texts as $text ) {
				if ( !is_string( $text ) ) {
					throw new InvalidArgumentException( '$textsByLanguage must contain string values only' );
				}

				foreach ( $types as $type ) {
					$criteria[] = new TermIndexSearchCriteria( [
						'termText' => $text,
						'termLanguage' => $lang,
						'termType' => $type,
					] );
				}
			}
		}

		return $criteria;
	}

	/**
	 * @param string $text
	 *
	 * @return string
	 */
	public function getSearchKey( $text ) {
		if ( $text === null ) {
			return null;
		}

		if ( $text === '' ) {
			return '';
		}

		// composed normal form
		$nfcText = $this->stringNormalizer->cleanupToNFC( $text );

		if ( !is_string( $nfcText ) || $nfcText === '' ) {
			wfWarn( "Unicode normalization failed for `$text`" );
		}

		// WARNING: *any* invalid UTF8 sequence causes preg_replace to return an empty string.
		// Control character classes excluding private use areas.
		$strippedText = preg_replace( '/[\p{Cc}\p{Cf}\p{Cn}\p{Cs}]+/u', ' ', $nfcText );
		// \p{Z} includes all whitespace characters and invisible separators.
		$strippedText = preg_replace( '/^\p{Z}+|\p{Z}+$/u', '', $strippedText );

		if ( $strippedText === '' ) {
			// NOTE: This happens when there is only whitespace in the string.
			//       However, preg_replace will also return an empty string if it
			//       encounters any invalid utf-8 sequence.
			return '';
		}

		//TODO: Use Language::lc to convert to lower case.
		//      But that requires us to load ALL the language objects,
		//      which loads ALL the messages, which makes us run out
		//      of RAM (see bug T43103).
		$normalized = mb_strtolower( $strippedText, 'UTF-8' );

		if ( !is_string( $normalized ) || $normalized === '' ) {
			wfWarn( "mb_strtolower normalization failed for `$strippedText`" );
		}

		return $normalized;
	}

}
