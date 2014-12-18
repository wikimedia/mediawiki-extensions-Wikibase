<?php

namespace Wikibase;

use DatabaseBase;
use DBAccessBase;
use InvalidArgumentException;
use Iterator;
use MWException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\LegacyIdInterpreter;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\FingerprintProvider;
use Wikibase\Lib\Store\LabelConflictFinder;

/**
 * Term lookup cache.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Jens Ohlig < jens.ohlig@wikimedia.de >
 * @author Daniel Kinzler
 * @author Denny
 */
class TermSqlIndex extends DBAccessBase implements TermIndex, LabelConflictFinder {

	/**
	 * @since 0.1
	 *
	 * @var string
	 */
	protected $tableName;

	/**
	 * @var StringNormalizer
	 */
	protected $stringNormalizer;

	/**
	 * @var int
	 */
	protected $maxConflicts = 10;

	/**
	 * Maps table fields to TermIndex interface field names.
	 *
	 * @since 0.2
	 *
	 * @var array
	 */
	protected $termFieldMap = array(
		'term_entity_type' => 'entityType',
		'term_type' => 'termType',
		'term_language' => 'termLanguage',
		'term_text' => 'termText',
		'term_entity_id' => 'entityId',
	);

	/**
	 * @since    0.4
	 *
	 * @param StringNormalizer $stringNormalizer
	 * @param string|bool      $wikiDb
	 */
	public function __construct( StringNormalizer $stringNormalizer, $wikiDb = false ) {
		parent::__construct( $wikiDb );
		$this->stringNormalizer = $stringNormalizer;
		$this->tableName = 'wb_terms';
	}

	/**
	 * Returns the name of the database table used to store the terms.
	 * This is the logical table name, subject to prefixing by the Database object.
	 *
	 * @since 0.4
	 *
	 * @return string
	 */
	public function getTableName() {
		return $this->tableName;
	}

	/**
	 * @see TermIndex::saveTermsOfEntity
	 *
	 * @since 0.1
	 *
	 * @param EntityDocument $entity
	 *
	 * @return boolean Success indicator
	 */
	public function saveTermsOfEntity( EntityDocument $entity ) {
		wfProfileIn( __METHOD__ );

		//First check whether there's anything to update
		$newTerms = $this->getEntityTerms( $entity );
		$oldTerms = $this->getTermsOfEntity( $entity->getId() );

		$termsToInsert = array_udiff( $newTerms, $oldTerms, 'Wikibase\Term::compare' );
		$termsToDelete = array_udiff( $oldTerms, $newTerms, 'Wikibase\Term::compare' );

		if ( !$termsToInsert && !$termsToDelete ) {
			wfDebugLog( __CLASS__, __FUNCTION__ . ': terms did not change, returning.' );
			wfProfileOut( __METHOD__ );
			return true;
		}

		$ok = true;
		$dbw = $this->getConnection( DB_MASTER );

		if ( $ok && $termsToDelete ) {
			wfDebugLog( __CLASS__, __FUNCTION__ . ': ' . count( $termsToDelete ) . ' terms to delete.' );
			$ok = $dbw->deadlockLoop( array( $this, 'deleteTermsInternal' ), $entity->getId(), $termsToDelete, $dbw );
		}

		if ( $ok && $termsToInsert ) {
			wfDebugLog( __CLASS__, __FUNCTION__ . ': ' . count( $termsToInsert ) . ' terms to insert.' );
			$ok = $dbw->deadlockLoop( array( $this, 'insertTermsInternal' ), $entity, $termsToInsert, $dbw );
		}

		$this->releaseConnection( $dbw );
		wfProfileOut( __METHOD__ );

		return $ok;
	}

	/**
	 * Internal callback for inserting a list of terms.
	 *
	 * @note: this is public only because it acts as a callback, there should be no
	 *        reason to call this directly!
	 *
	 * @since 0.5
	 *
	 * @param EntityDocument $entity
	 * @param Term[] $terms
	 * @param DatabaseBase $dbw
	 *
	 * @return boolean Success indicator
	 */
	public function insertTermsInternal( EntityDocument $entity, $terms, DatabaseBase $dbw ) {
		wfProfileIn( __METHOD__ );

		$entityIdentifiers = array(
			// FIXME: this will fail for IDs that do not have a numeric form
			'term_entity_id' => $entity->getId()->getNumericId(),
			'term_entity_type' => $entity->getType()
		);

		wfDebugLog( __CLASS__, __FUNCTION__ . ': inserting terms for ' . $entity->getId()->getSerialization() );

		$weightField = array();
		if ( $this->supportsWeight() ) {
			$weightField = array( 'term_weight'  => $this->getWeight( $entity ) );
		}

		$success = true;
		foreach ( $terms as $term ) {
			$success = $dbw->insert(
				$this->tableName,
				array_merge(
					$this->getTermFields( $term ),
					$entityIdentifiers,
					$weightField
				),
				__METHOD__,
				array( 'IGNORE' )
			);

			if ( !$success ) {
				break;
			}
		}

		wfProfileOut( __METHOD__ );

		return $success;
	}

	/**
	 * @param EntityDocument $entity
	 *
	 * @return Term[]
	 */
	public function getEntityTerms( EntityDocument $entity ) {
		$extraFields = array(
			'entityType' => $entity->getType(),
		);

		$entityId = $entity->getId();
		if ( $entityId !== null ) {
			$extraFields['entityId'] = $entityId->getNumericId();
		}

		// FIXME: OCP violation. No support for new types of entities can be registered

		if ( $entity instanceof FingerprintProvider ) {
			return $this->getFingerprintTerms( $entity->getFingerprint(), $extraFields );
		}

		return array();
	}

	private function getFingerprintTerms( Fingerprint $fingerprint, array $extraFields = array() ) {
		$terms = array();

		foreach ( $fingerprint->getDescriptions()->toTextArray() as $languageCode => $description ) {
			$term = new Term( $extraFields );

			$term->setLanguage( $languageCode );
			$term->setType( Term::TYPE_DESCRIPTION );
			$term->setText( $description );

			$terms[] = $term;
		}

		foreach ( $fingerprint->getLabels()->toTextArray() as $languageCode => $label ) {
			$term = new Term( $extraFields );

			$term->setLanguage( $languageCode );
			$term->setType( Term::TYPE_LABEL );
			$term->setText( $label );

			$terms[] = $term;
		}

		/** @var AliasGroup $aliasGroup */
		foreach ( $fingerprint->getAliasGroups() as $aliasGroup ) {
			foreach ( $aliasGroup->getAliases() as $alias ) {
				$term = new Term( $extraFields );

				$term->setLanguage( $aliasGroup->getLanguageCode() );
				$term->setType( Term::TYPE_ALIAS );
				$term->setText( $alias );

				$terms[] = $term;
			}
		}

		return $terms;
	}

	/**
	 * Internal callback for deleting a list of terms.
	 *
	 * @note: this is public only because it acts as a callback, there should be no
	 *        reason to call this directly!
	 *
	 * @since 0.5
	 *
	 * @param EntityId $entityId
	 * @param Term[] $terms
	 * @param DatabaseBase $dbw
	 *
	 * @return boolean Success indicator
	 */
	public function deleteTermsInternal( EntityId $entityId, $terms, DatabaseBase $dbw ) {
		wfProfileIn( __METHOD__ );

		//TODO: Make getTermsOfEntity() collect term_row_id values, so we can use them here.
		//      That would allow us to do the deletion in a single query, based on a set of ids.

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
				__METHOD__,
				array( 'IGNORE' )
			);

			if ( !$success ) {
				break;
			}
		}

		wfProfileOut( __METHOD__ );

		return $success;
	}

	/**
	 * Calculate a weight the given entity to be used for ranking. Should be normalized
	 * between 0 and 1, but that's not a strong constraint.
	 * This implementation relies on sitelinks, and simply takes the number of sitelinks
	 * as the weight.
	 *
	 * TODO Should be moved to its own object and be added via dependency injection
	 *
	 * @since 0.4
	 *
	 * @param EntityDocument $entity
	 *
	 * @return float weight
	 */
	protected function getWeight( EntityDocument $entity ) {
		// FIXME: OCP violation. No support for new types of entities can be registered

		if ( $entity instanceof Item ) {
			return $entity->getSiteLinkList()->count() / 1000.0;
		}

		return 0.0;
	}

	/**
	 * Returns an array with the database table fields for the provided term.
	 *
	 * @since 0.2
	 *
	 * @param Term $term
	 *
	 * @return array
	 */
	protected function getTermFields( Term $term ) {
		$fields = array(
			'term_language' => $term->getLanguage(),
			'term_type' => $term->getType(),
			'term_text' => $term->getText(),
		);

		if ( !Settings::get( 'withoutTermSearchKey' ) ) {
			$fields['term_search_key'] = $this->getSearchKey( $term->getText(), $term->getLanguage() );
		}

		return $fields;
	}

	/**
	 * @see TermIndex::deleteTermsOfEntity
	 *
	 * @since 0.5
	 *
	 * @param EntityId $entityId
	 *
	 * @return boolean Success indicator
	 */
	public function deleteTermsOfEntity( EntityId $entityId ) {
		wfProfileIn( __METHOD__ );

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

		wfProfileOut( __METHOD__ );
		return $success;
	}

	/**
	 * Returns the terms stored for the given entity.
	 *
	 * @see TermIndex::getTermsOfEntity
	 * @todo: share more code with getTermsOfEntities. There are only subtle differences
	 * regarding what fields are loaded.
	 *
	 * @param EntityId $entityId
	 * @param string[]|null $termTypes
	 * @param string[]|null $languageCodes
	 *
	 * @return Term[]
	 */
	public function getTermsOfEntity(
		EntityId $entityId,
		array $termTypes = null,
		array $languageCodes = null
	) {
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
	 * @return Term[]
	 */
	public function getTermsOfEntities(
		array $entityIds,
		array $termTypes = null,
		array $languageCodes = null
	) {
		return $this->fetchTerms( $entityIds, $termTypes, $languageCodes );
	}

	/**
	 * @param EntityId[] $entityIds
	 * @param string[]|null $termTypes
	 * @param string[]|null $languageCodes
	 *
	 * @throws MWException
	 * @return Term[]
	 */
	private function fetchTerms(
		array $entityIds,
		array $termTypes = null,
		array $languageCodes = null
	) {
		if ( empty( $entityIds )
			|| ( is_array( $termTypes ) && empty( $termTypes ) )
			|| ( is_array( $languageCodes ) && empty( $languageCodes ) )
		) {
			return array();
		}

		wfProfileIn( __METHOD__ );

		$entityType = null;
		$numericIds = array();

		foreach ( $entityIds as $id ) {
			if ( $entityType === null ) {
				$entityType = $id->getEntityType();
			} elseif ( $id->getEntityType() !== $entityType ) {
				throw new MWException( "ID $id does not refer to an entity of type $entityType" );
			}

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
			array_keys( $this->termFieldMap ),
			$conditions,
			__METHOD__
		);

		$terms = $this->buildTermResult( $res );

		$this->releaseConnection( $dbr );

		wfProfileOut( __METHOD__ );

		return $terms;
	}

	/**
	 * Returns the Database connection from which to read.
	 *
	 * @since 0.1
	 *
	 * @return DatabaseBase
	 */
	public function getReadDb() {
		return $this->getConnection( DB_SLAVE );
	}

	/**
	 * Returns the Database connection to which to write.
	 *
	 * @since 0.4
	 *
	 * @return DatabaseBase
	 */
	public function getWriteDb() {
		return $this->getConnection( DB_MASTER );
	}

	/**
	 * @see TermIndex::getMatchingTerms
	 *
	 * @since 0.2
	 *
	 * @param Term[] $terms
	 * @param string|null $termType
	 * @param string|null $entityType
	 * @param array $options
	 *
	 * @return Term[]
	 */
	public function getMatchingTerms( array $terms, $termType = null, $entityType = null, array $options = array() ) {
		if ( empty( $terms ) ) {
			return array();
		}

		wfProfileIn( __METHOD__ );

		$dbr = $this->getReadDb();

		$termConditions = $this->termsToConditions( $dbr, $terms, $termType, $entityType, $options );
		$where = array( implode( ' OR ', $termConditions ) );

		$selectionFields = array_keys( $this->termFieldMap );

		$queryOptions = array();

		if ( isset( $options['LIMIT'] ) && $options['LIMIT'] > 0 ) {
			$queryOptions['LIMIT'] = intval( $options['LIMIT'] );
		}

		$obtainedTerms = $dbr->select(
			$this->tableName,
			$selectionFields,
			$where,
			__METHOD__,
			$queryOptions
		);

		$terms = $this->buildTermResult( $obtainedTerms );

		$this->releaseConnection( $dbr );

		wfProfileOut( __METHOD__ );

		return $terms;
	}

	/**
	 * @see TermIndex::getMatchingIDs
	 *
	 * @since 0.4
	 *
	 * @param Term[] $terms
	 * @param string|null $entityType
	 * @param array $options There is an implicit LIMIT of 5000 items in this implementation
	 *
	 * @return EntityId[]
	 */
	public function getMatchingIDs( array $terms, $entityType = null, array $options = array() ) {
		if ( empty( $terms ) ) {
			return array();
		}

		wfProfileIn( __METHOD__ );

		// this is the maximum limit of search results
		// TODO this should not be hardcoded
		$internalLimit = 5000;

		$dbr = $this->getReadDb();

		$conditions = $this->termsToConditions( $dbr, $terms, null, $entityType, $options );

		$selectionFields = array(
			'term_entity_id',
			'term_entity_type',
		);

		// TODO instead of a DB query, get a setting. Should save on a few Database round trips.
		$hasWeight = $this->supportsWeight();

		if ( $hasWeight ) {
			$selectionFields[] = 'term_weight';
		}

		$queryOptions = array(
			'DISTINCT',
			'LIMIT' => $internalLimit,
		);

		$requestedLimit = isset( $options['LIMIT'] ) ? max( intval( $options['LIMIT'] ), 0 ) : 0;
		// if we take the weight into account, we need to grab basically all hits in order
		// to allow for the post-search sorting below.
		if ( !$hasWeight && $requestedLimit > 0 && $requestedLimit < $queryOptions['LIMIT'] ) {
			$queryOptions['LIMIT'] = $requestedLimit;
		}

		$rows = $dbr->select(
			$this->tableName,
			$selectionFields,
			implode( ' OR ', $conditions ),
			__METHOD__,
			$queryOptions
		);

		$entityIds = array();

		if ( $rows instanceof Iterator ) {
			if ( $hasWeight ) {
				$entityIds = $this->getEntityIdsOrderedByWeight( $rows, $requestedLimit );
			} else {
				foreach ( $rows as $row ) {
					// FIXME: this only works for items and properties
					$id = LegacyIdInterpreter::newIdFromTypeAndNumber( $row->term_entity_type, $row->term_entity_id );

					$entityIds[] = $id;
				}
			}
		}

		$this->releaseConnection( $dbr );

		wfProfileOut( __METHOD__ );

		return $entityIds;
	}

	/**
	 * @param Iterator $rows
	 * @param int $limit
	 *
	 * @return EntityId[]
	 */
	private function getEntityIdsOrderedByWeight( Iterator $rows, $limit = 0 ) {
		$weights = array();
		$idMap = array();

		foreach ( $rows as $row ) {
			// FIXME: this only works for items and properties
			$id = LegacyIdInterpreter::newIdFromTypeAndNumber( $row->term_entity_type, $row->term_entity_id );

			$key = $id->getSerialization();
			$weights[$key] = floatval( $row->term_weight );
			$idMap[$key] = $id;
		}

		// this is a post-search sorting by weight. This allows us to not require an additional
		// index on the wb_terms table that is very big already. This is also why we have
		// the internal limit of 5000, since SQL's index would explode in size if we added the
		// weight to it here (which would allow us to delegate the sorting to SQL itself)
		arsort( $weights, SORT_NUMERIC );

		if ( $limit > 0 ) {
			$weights = array_slice( $weights, 0, $limit, true );
		}

		$entityIds = array();

		foreach ( $weights as $key => $weight ) {
			$entityIds[] = $idMap[$key];
		}

		return $entityIds;
	}

	/**
	 * @param DatabaseBase $db
	 * @param Term[] $terms
	 * @param string|null $termType
	 * @param string|null $entityType
	 * @param array $options
	 *
	 * @return array
	 */
	private function termsToConditions( DatabaseBase $db, array $terms, $termType = null, $entityType = null, array $options = array() ) {
		wfProfileIn( __METHOD__ );

		$conditions = array();

		foreach ( $terms as $term ) {
			$termConditions = $this->termMatchConditions( $db, $term, $termType, $entityType, $options );
			$conditions[] = '(' . implode( ' AND ', $termConditions ) . ')';
		}

		wfProfileOut( __METHOD__ );

		return $conditions;
	}

	/**
	 * @param DatabaseBase $db
	 * @param Term $term
	 * @param string|null $termType
	 * @param string|null $entityType
	 * @param array $options
	 *
	 * @return array
	 */
	private function termMatchConditions(
		DatabaseBase $db,
		Term $term,
		$termType = null,
		$entityType = null,
		array $options = array()
	) {
		wfProfileIn( __METHOD__ );

		$options = array_merge(
			array(
				'caseSensitive' => true,
				'prefixSearch' => false,
			),
			$options
		);

		$conditions = array();

		$language = $term->getLanguage();

		if ( $language !== null ) {
			$conditions['term_language'] = $language;
		}

		$text = $term->getText();

		if ( $text !== null ) {
			if ( $options['caseSensitive']
				|| Settings::get( 'withoutTermSearchKey' ) ) {
				//NOTE: whether this match is *actually* case sensitive depends on the collation used in the database.
				$textField = 'term_text';
			}
			else {
				$textField = 'term_search_key';
				$text = $this->getSearchKey( $term->getText(), $term->getLanguage() );
			}

			if ( $options['prefixSearch'] ) {
				$conditions[] = $textField . $db->buildLike( $text, $db->anyString() );
			}
			else {
				$conditions[$textField] = $text;
			}
		}

		if ( $term->getType() !== null ) {
			$conditions['term_type'] = $term->getType();
		}
		elseif ( $termType !== null ) {
			$conditions['term_type'] = $termType;
		}

		if ( $term->getEntityType() !== null ) {
			$conditions['term_entity_type'] = $term->getEntityType();
		}
		elseif ( $entityType !== null ) {
			$conditions['term_entity_type'] = $entityType;
		}

		foreach ( $conditions as $field => &$value ) {
			if ( !is_int( $field ) ) {
				$value = $field . '=' . $db->addQuotes( $value );
			}
		}

		wfProfileOut( __METHOD__ );

		return $conditions;
	}

	/**
	 * Modifies the provided terms to use the field names expected by the interface
	 * rather then the table field names. Also ensures the values are of the correct type.
	 *
	 * @since 0.2
	 *
	 * @param Iterator|array $obtainedTerms PHP fails for not having a common iterator/array thing :<0
	 *
	 * @return Term[]
	 */
	protected function buildTermResult( $obtainedTerms ) {
		wfProfileIn( __METHOD__ );

		$matchingTerms = array();

		foreach ( $obtainedTerms as $obtainedTerm ) {
			$matchingTerm = array();

			foreach ( $obtainedTerm as $key => $value ) {
				if ( !array_key_exists( $key, $this->termFieldMap ) ) {
					// unknown field, skip
					continue;
				}

				if ( $key === 'term_entity_id' ) {
					$value = (int)$value;
				}

				$matchingTerm[$this->termFieldMap[$key]] = $value;
			}

			$matchingTerms[] = new Term( $matchingTerm );
		}

		wfProfileOut( __METHOD__ );

		return $matchingTerms;
	}

	/**
	 * @see TermIndex::clear
	 *
	 * @since 0.2
	 *
	 * @return boolean Success indicator
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
	 * @since 0.5
	 *
	 * @param string $entityType
	 * @param string[] $labels
	 *
	 * @throws InvalidArgumentException
	 * @return Term[]
	 */
	public function getLabelConflicts( $entityType, array $labels ) {
		if ( !is_string( $entityType ) ) {
			throw new InvalidArgumentException( '$entityType must be a string' );
		}

		if ( empty( $labels ) ) {
			return array();
		}

		wfProfileIn( __METHOD__ );
		$templates = $this->makeQueryTerms( $labels, Term::TYPE_LABEL );

		$labelConflicts = $this->getMatchingTerms(
			$templates,
			Term::TYPE_LABEL,
			$entityType,
			array(
				'LIMIT' => $this->maxConflicts,
			)
		);

		wfProfileOut( __METHOD__ );
		return $labelConflicts;
	}

	/**
	 * @see LabelConflictFinder::getLabelWithDescriptionConflicts
	 *
	 * @note: This implementation does not guarantee that all matches are returned.
	 * The maximum number of conflicts returned is controlled by $this->maxConflicts.
	 *
	 * @since 0.5
	 *
	 * @param string $entityType
	 * @param string[] $labels
	 * @param string[] $descriptions
	 *
	 * @throws InvalidArgumentException
	 * @return Term[]
	 */
	public function getLabelWithDescriptionConflicts( $entityType, array $labels, array $descriptions ) {
		$labels = array_intersect_key( $labels, $descriptions );
		$descriptions = array_intersect_key( $descriptions, $labels );

		if ( empty( $descriptions ) || empty( $labels ) ) {
			return array();
		}

		wfProfileIn( __METHOD__ );

		$dbr = $this->getReadDb();

		// FIXME: MySQL doesn't support self-joins on temporary tables,
		//        so skip this check during unit tests on MySQL!
		if ( defined( 'MW_PHPUNIT_TEST' ) && $dbr->getType() === 'mysql' ) {
			$this->releaseConnection( $dbr );
			wfProfileOut( __METHOD__ );
			return array();
		}

		$where = array();
		$where['L.term_entity_type'] = $entityType;
		$where['L.term_type'] = Term::TYPE_LABEL;
		$where['D.term_type'] = Term::TYPE_DESCRIPTION;

		$where[] = 'D.term_entity_id=' . 'L.term_entity_id';
		$where[] = 'D.term_entity_type=' . 'L.term_entity_type';

		$termConditions = array();

		foreach ( $labels as $lang => $label ) {
			// Due to the array_intersect_key call earlier, we know a corresponding description exists.
			$description = $descriptions[$lang];

			$matchConditions = array(
				'L.term_language' => $lang,
				'L.term_text' => $label,
				'D.term_text' => $description,
			);

			$termConditions[] = $dbr->makeList( $matchConditions, LIST_AND );
		}

		$where[] = $dbr->makeList( $termConditions, LIST_OR );

		$queryOptions = array(
			'LIMIT' => $this->maxConflicts
		);

		$obtainedTerms = $dbr->select(
			array( 'L' => $this->tableName, 'D' => $this->tableName,  ),
			'L.*',
			$where,
			__METHOD__,
			$queryOptions
		);

		$conflicts = $this->buildTermResult( $obtainedTerms );

		$this->releaseConnection( $dbr );

		wfProfileOut( __METHOD__ );
		return $conflicts;
	}

	/**
	 * @param string[] $textsByLanguage A list of texts, or a list of lists of texts (keyed by language on the top level)
	 * @param string $type
	 *
	 * @throws InvalidArgumentException
	 * @return Term[]
	 */
	private function makeQueryTerms( $textsByLanguage, $type ) {
		$terms = array();

		foreach ( $textsByLanguage as $lang => $texts ) {
			$texts = (array)$texts;

			foreach ( $texts as $text ) {
				if ( !is_string( $text ) ) {
					throw new InvalidArgumentException( '$textsByLanguage must contain string values only' );
				}

				$terms[] = new Term( array(
					'termText' => $text,
					'termLanguage' => $lang,
					'termType' => $type,
				) );
			}
		}

		return $terms;
	}

	/**
	 * @since 0.4
	 *
	 * @param string $text
	 * @param string $lang language code of the text's language, may be used
	 *                     for specialized normalization.
	 *
	 * @return string
	 */
	public function getSearchKey( $text, $lang = 'en' ) {
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

		// \p{Z} - whitespace
		// \p{C} - control chars
		// WARNING: *any* invalid UTF8 sequence causes preg_replace to return an empty string.
		$strippedText = $nfcText;
		$strippedText = preg_replace( '/[\p{Cc}\p{Cf}\p{Cn}\p{Cs}]+/u', ' ', $strippedText );
		$strippedText = preg_replace( '/^[\p{Z}]+|[\p{Z}]+$/u', '', $strippedText );

		if ( $strippedText === '' ) {
			// NOTE: This happens when there is only whitespace in the string.
			//       However, preg_replace will also return an empty string if it
			//       encounters any invalid utf-8 sequence.
			return '';
		}

		//TODO: Use Language::lc to convert to lower case.
		//      But that requires us to load ALL the language objects,
		//      which loads ALL the messages, which makes us run out
		//      of RAM (see bug 41103).
		$normalized = mb_strtolower( $strippedText, 'UTF-8' );

		if ( !is_string( $normalized ) || $normalized === '' ) {
			wfWarn( "mb_strtolower normalization failed for `$strippedText`" );
		}

		return $normalized;
	}

	/**
	 * @return mixed
	 */
	public function supportsWeight() {
		return !Settings::get( 'withoutTermWeight' );
	}

}
