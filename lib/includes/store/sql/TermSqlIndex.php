<?php

namespace Wikibase;

use DatabaseBase;
use DBAccessBase;
use Iterator;
use MWException;
use ResultWrapper;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\LegacyIdInterpreter;

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
class TermSqlIndex extends DBAccessBase implements TermIndex {

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
	 * Constructor.
	 *
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
	 * @param Entity $entity
	 *
	 * @return boolean Success indicator
	 */
	public function saveTermsOfEntity( Entity $entity ) {
		wfProfileIn( __METHOD__ );

		//First check whether there's anything to update
		$newTerms = $this->getEntityTerms( $entity );
		$oldTerms = $this->getTermsOfEntity( $entity->getId() );

		$termsToInsert = array_udiff( $newTerms, $oldTerms, 'Wikibase\Term::compare' );
		$termsToDelete = array_udiff( $oldTerms, $newTerms, 'Wikibase\Term::compare' );

		if ( !$termsToInsert && !$termsToDelete ) {
			wfDebugLog( __CLASS__, __FUNCTION__ . ": terms did not change, returning." );
			wfProfileOut( __METHOD__ );
			return true;
		}

		$ok = true;
		$dbw = $this->getConnection( DB_MASTER );

		if ( $ok && $termsToDelete ) {
			wfDebugLog( __CLASS__, __FUNCTION__ . ": " . count( $termsToDelete ) . " terms to delete." );
			$ok = $dbw->deadlockLoop( array( $this, 'deleteTermsInternal' ), $entity, $termsToDelete, $dbw );
		}

		if ( $ok && $termsToInsert ) {
			wfDebugLog( __CLASS__, __FUNCTION__ . ": " . count( $termsToInsert ) . " terms to insert." );
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
	 * @param Entity $entity
	 * @param Term[] $terms
	 * @param DatabaseBase $dbw
	 *
	 * @return boolean Success indicator
	 */
	public function insertTermsInternal( Entity $entity, $terms, DatabaseBase $dbw ) {
		wfProfileIn( __METHOD__ );

		$entityIdentifiers = array(
			'term_entity_id' => $entity->getId()->getNumericId(),
			'term_entity_type' => $entity->getId()->getEntityType()
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
	 * TODO: this method belongs in Entity itself. This change can only be made once
	 * there is a sane Term object in DataModel itself though.
	 *
	 * @param Entity $entity
	 *
	 * @return Term[]
	 */
	public function getEntityTerms( Entity $entity ) {
		$terms = array();

		foreach ( $entity->getDescriptions() as $languageCode => $description ) {
			$term = new Term();

			$term->setLanguage( $languageCode );
			$term->setType( Term::TYPE_DESCRIPTION );
			$term->setText( $description );

			$terms[] = $term;
		}

		foreach ( $entity->getLabels() as $languageCode => $label ) {
			$term = new Term();

			$term->setLanguage( $languageCode );
			$term->setType( Term::TYPE_LABEL );
			$term->setText( $label );

			$terms[] = $term;
		}

		foreach ( $entity->getAllAliases() as $languageCode => $aliases ) {
			foreach ( $aliases as $alias ) {
				$term = new Term();

				$term->setLanguage( $languageCode );
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
	 * @param Entity $entity
	 * @param Term[] $terms
	 * @param DatabaseBase $dbw
	 *
	 * @return boolean Success indicator
	 */
	public function deleteTermsInternal( Entity $entity, $terms, DatabaseBase $dbw ) {
		wfProfileIn( __METHOD__ );

		//TODO: Make getTermsOfEntity() collect term_row_id values, so we can use them here.
		//      That would allow us to do the deletion in a single query, based on a set of ids.

		$entityIdentifiers = array(
			'term_entity_id' => $entity->getId()->getNumericId(),
			'term_entity_type' => $entity->getId()->getEntityType()
		);

		$uniqueKeyFields = array( 'term_entity_type', 'term_entity_id', 'term_language', 'term_type', 'term_text' );

		wfDebugLog( __CLASS__, __FUNCTION__ . ': deleting terms for ' . $entity->getId()->getSerialization() );

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
	 * @param Entity $entity
	 *
	 * @return float weight
	 */
	protected function getWeight( Entity $entity ) {
		if ( $entity instanceof Item ) {
			return count( $entity->getSiteLinks() ) / 1000.0;
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
	 * @see TermIndex::getTermsOfEntity
	 *
	 * @param EntityId $id
	 *
	 * @return Term[]
	 */
	public function getTermsOfEntity( EntityId $id ) {
		wfProfileIn( __METHOD__ );

		$entityIdentifiers = array(
			'term_entity_id' => $id->getNumericId(),
			'term_entity_type' => $id->getEntityType()
		);

		$fields = array(
			'term_language',
			'term_type',
			'term_text',
		);

		$dbr = $this->getReadDb();

		$res = $dbr->select(
			$this->tableName,
			$fields,
			$entityIdentifiers,
			__METHOD__
		);

		$terms = $this->buildTermResult( $res );

		$this->releaseConnection( $dbr );

		wfProfileOut( __METHOD__ );

		return $terms;
	}

	/**
	 * Returns the terms stored for the given entities.
	 *
	 * @see TermIndex::getTermsOfEntities
	 *
	 * @param EntityId[] $ids
	 * @param string $entityType
	 * @param string|null $language Language code
	 *
	 * @throws MWException
	 * @return Term[]
	 */
	public function getTermsOfEntities( array $ids, $entityType, $language = null ) {
		wfProfileIn( __METHOD__ );

		if ( empty($ids) ) {
			wfProfileOut( __METHOD__ );
			return array();
		}

		$entityIdentifiers = array(
			'term_entity_type' => $entityType
		);
		if ( $language !== null ) {
			$entityIdentifiers['term_language'] = $language;
		}

		$numericIds = array();
		foreach ( $ids as $id ) {
			if ( $id->getEntityType() !== $entityType ) {
				throw new MWException( 'ID ' . $id->getSerialization()
					. " does not refer to an entity of type $entityType." );
			}

			$numericIds[] = $id->getNumericId();
		}

		$entityIdentifiers['term_entity_id'] = $numericIds;

		$fields = array(
			'term_entity_id',
			'term_entity_type',
			'term_language',
			'term_type',
			'term_text',
		);

		$dbr = $this->getReadDb();

		$res = $dbr->select(
			$this->tableName,
			$fields,
			$entityIdentifiers,
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
	 * Returns the Database connection to wich to write.
	 *
	 * @since 0.4
	 *
	 * @return DatabaseBase
	 */
	public function getWriteDb() {
		return $this->getConnection( DB_MASTER );
	}

	/**
	 * @see TermIndex::termExists
	 *
	 * @since 0.1
	 *
	 * @param string $termValue
	 * @param string|null $termType
	 * @param string|null $termLanguage Language code
	 * @param string|null $entityType
	 *
	 * @return boolean
	 */
	public function termExists( $termValue, $termType = null, $termLanguage = null, $entityType = null ) {
		wfProfileIn( __METHOD__ );

		$conditions = array(
			'term_text' => $termValue,
		);

		if ( $termType !== null ) {
			$conditions['term_type'] = $termType;
		}

		if ( $termLanguage !== null ) {
			$conditions['term_language'] = $termLanguage;
		}

		if ( $entityType !== null ) {
			$conditions['term_entity_type'] = $entityType;
		}

		$dbr = $this->getReadDb();

		$result = $dbr->selectRow(
			$this->tableName,
			array(
				'term_entity_id',
			),
			$conditions,
			__METHOD__
		);

		$this->releaseConnection( $dbr );

		wfProfileOut( __METHOD__ );

		return $result !== false;
	}

	/**
	 * @see TermIndex::getEntityIdsForLabel
	 *
	 * @since 0.1
	 *
	 * @param string $label
	 * @param string|null $languageCode
	 * @param string|null $entityType
	 * @param bool $fuzzySearch if false, only exact matches are returned, otherwise more relaxed search . Defaults to false.
	 *
	 * @return EntityId[]
	 */
	public function getEntityIdsForLabel( $label, $languageCode = null, $entityType = null, $fuzzySearch = false ) {
		wfProfileIn( __METHOD__ );

		$fuzzySearch = false; // TODO switched off for now until we have a solution for limiting the results
		$db = $this->getReadDb();

		$tables = array( 'terms0' => $this->tableName );

		$conds = array( 'terms0.term_type' => Term::TYPE_LABEL );
		if ( $fuzzySearch ) {
			$conds[] = 'terms0.term_text' . $db->buildLike( $label, $db->anyString() );
		} else {
			$conds['terms0.term_text'] = $label;
		}

		$joinConds = array();

		if ( !is_null( $languageCode ) ) {
			$conds['terms0.term_language'] = $languageCode;
		}

		if ( !is_null( $entityType ) ) {
			$conds['terms0.term_entity_type'] = $entityType;
		}

		$entities = $db->select(
			$tables,
			array( 'terms0.term_entity_id', 'terms0.term_entity_type' ),
			$conds,
			__METHOD__,
			array( 'DISTINCT' ),
			$joinConds
		);

		$this->releaseConnection( $db );

		$entityIds = array_map(
			function( $entity ) {
				// FIXME: this only works for items and properties
				return LegacyIdInterpreter::newIdFromTypeAndNumber( $entity->term_entity_type, $entity->term_entity_id );
			},
			iterator_to_array( $entities )
		);

		wfProfileOut( __METHOD__ );

		return $entityIds;
	}

	/**
	 * @see TermIndex::getMatchingTerms
	 *
	 * @since 0.2
	 *
	 * @param array $terms
	 * @param string|null $termType
	 * @param string|null $entityType
	 * @param array $options
	 *
	 * @return array
	 */
	public function getMatchingTerms( array $terms, $termType = null, $entityType = null, array $options = array() ) {
		wfProfileIn( __METHOD__ );

		if ( empty( $terms ) ) {
			wfProfileOut( __METHOD__ );
			return array();
		}

		$conditions = $this->termsToConditions( $terms, $termType, $entityType, false, $options );

		$selectionFields = array_keys( $this->termFieldMap );

		$dbr = $this->getReadDb();

		$queryOptions = array();

		if ( isset( $options['LIMIT'] ) && $options['LIMIT'] > 0 ) {
			$queryOptions['LIMIT'] = intval( $options['LIMIT'] );
		}

		$obtainedTerms = $dbr->select(
			$this->tableName,
			$selectionFields,
			implode( ' OR ', $conditions ),
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
	 * @param array $terms
	 * @param string $entityType
	 * @param array $options There is an implicit LIMIT of 5000 items in this implementation
	 *
	 * @return EntityId[]
	 */
	public function getMatchingIDs( array $terms, $entityType, array $options = array() ) {
		wfProfileIn( __METHOD__ );

		if ( empty( $terms ) ) {
			wfProfileOut( __METHOD__ );
			return array();
		}

		// this is the maximum limit of search results
		// TODO this should not be hardcoded
		$internalLimit = 5000;

		$conditions = $this->termsToConditions( $terms, null, $entityType, false, $options );

		$dbr = $this->getReadDb();

		$selectionFields = array( 'term_entity_id' );

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
		if ( !$hasWeight && $requestedLimit && $requestedLimit < $queryOptions['LIMIT'] ) {
			$queryOptions['LIMIT'] = $requestedLimit;
		}

		$obtainedIDs = $dbr->select(
			$this->tableName,
			$selectionFields,
			implode( ' OR ', $conditions ),
			__METHOD__,
			$queryOptions
		);

		if ( $hasWeight ) {
			$weights = array();
			foreach ( $obtainedIDs as $obtainedID ) {
				$weights[$obtainedID->term_entity_id] = floatval( $obtainedID->term_weight );
			}

			// this is a post-search sorting by weight. This allows us to not require an additional
			// index on the wb_terms table that is very big already. This is also why we have
			// the internal limit of 5000, since SQL's index would explode in size if we added the
			// weight to it here (which would allow us to delegate the sorting to SQL itself)
			arsort( $weights, SORT_NUMERIC );

			if ( $requestedLimit ) {
				$numericIds = array_keys( array_slice( $weights, 0, $requestedLimit, true ) );
			} else {
				$numericIds = array_keys( $weights );
			}
		} else {
			$numericIds = array();
			foreach ( $obtainedIDs as $obtainedID ) {
				$numericIds[] = $obtainedID->term_entity_id;
			}
		}

		$this->releaseConnection( $dbr );

		// turn numbers into entity ids
		$result = array();

		foreach ( $numericIds as $numericId ) {
			// FIXME: this only works for items and properties
			$result[] = LegacyIdInterpreter::newIdFromTypeAndNumber( $entityType, $numericId );
		}

		wfProfileOut( __METHOD__ );

		return $result;
	}

	/**
	 * @since 0.2
	 *
	 * @param Term[] $terms
	 * @param string $termType
	 * @param string $entityType
	 * @param boolean $forJoin
	 *            If the provided terms are used for a join.
	 *            If so, the fields of each term get prefixed with a table name starting with terms0 and counting up.
	 * @param array $options
	 *
	 * @return array
	 */
	protected function termsToConditions( array $terms, $termType, $entityType, $forJoin = false, array $options = array() ) {
		wfProfileIn( __METHOD__ );

		$options = array_merge(
			array(
				'caseSensitive' => true,
				'prefixSearch' => false,
			),
			$options
		);

		$conditions = array();
		$tableIndex = 0;

		$dbr = $this->getReadDb();

		/**
		 * @var Term $term
		 */
		foreach ( $terms as $term ) {
			$fullTerm = array();

			$language = $term->getLanguage();

			if ( $language !== null ) {
				$fullTerm['term_language'] = $language;
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
					$fullTerm[] = $textField . $dbr->buildLike( $text, $dbr->anyString() );
				}
				else {
					$fullTerm[$textField] = $text;
				}
			}

			if ( $term->getType() !== null ) {
				$fullTerm['term_type'] = $term->getType();
			}
			elseif ( $termType !== null ) {
				$fullTerm['term_type'] = $termType;
			}

			if ( $term->getEntityType() !== null ) {
				$fullTerm['term_entity_type'] = $term->getEntityType();
			}
			elseif ( $entityType !== null ) {
				$fullTerm['term_entity_type'] = $entityType;
			}

			$tableName = 'terms' . $tableIndex++;

			foreach ( $fullTerm as $field => &$value ) {
				if ( !is_int( $field ) ) {
					$value = $field . '=' . $dbr->addQuotes( $value );
				}

				if ( $forJoin ) {
					$value = $tableName . '.' . $value;
				}
			}

			$conditions[] = '(' . implode( ' AND ', $fullTerm ) . ')';
		}

		$this->releaseConnection( $dbr );

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
	 * @return array
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
	 * @see TermIndex::getMatchingTermCombination
	 *
	 * Note: the interface specifies capability for only a single join, which in this implementation
	 * is enforced by the $joinCount var. The code itself however could handle multiple joins.
	 *
	 * @since 0.4
	 *
	 * @param array $terms
	 * @param string|null $termType
	 * @param string|null $entityType
	 * @param EntityId|null $excludeId
	 *
	 * @return array
	 */
	public function getMatchingTermCombination( array $terms, $termType = null, $entityType = null, EntityId $excludeId = null ) {
		wfProfileIn( __METHOD__ );

		if ( empty( $terms ) ) {
			wfProfileOut( __METHOD__ );
			return array();
		}

		$conditions = array();
		$joinCount = 1;

		$dbr = $this->getReadDb();

		foreach ( $terms as $termList ) {
			// Limit the operation to a single join.
			$termList = array_slice( $termList, 0, $joinCount + 1 );

			$combinationConds = $this->termsToConditions( $termList, $termType, $entityType, true );

			$exclusionConds = array();

			if ( $excludeId !== null ) {
				$exclusionConds[] = 'terms0.term_entity_id <> ' . $dbr->addQuotes( $excludeId->getNumericId() );
				$exclusionConds[] = 'terms0.term_entity_type <> ' . $dbr->addQuotes( $excludeId->getEntityType() );
			}

			if ( !empty( $exclusionConds ) ) {
				$combinationConds[] = '(' . implode( ' OR ', $exclusionConds ) . ')';
			}

			$conditions[] = '(' . implode( ' AND ', $combinationConds ) . ')';
		}

		$tables = array();
		$fieldsToSelect = array();
		$joinConds = array();

		for ( $tableIndex = 0; $tableIndex <= $joinCount; $tableIndex++ ) {
			$tableName = 'terms' . $tableIndex;
			$tables[$tableName] = $this->tableName;

			foreach ( array_keys( $this->termFieldMap ) as $fieldName ) {
				$fieldsToSelect[] = $tableName . '.' . $fieldName . ' AS ' . $tableName . $fieldName;
			}

			if ( $tableIndex !== 0 ) {
				$joinConds[$tableName] = array(
					'INNER JOIN',
					array(
						'terms0.term_entity_id=' . $tableName . '.term_entity_id',
						'terms0.term_entity_type=' . $tableName . '.term_entity_type',
					)
				);
			}
		}

		$obtainedTerms = $dbr->select(
			$tables,
			$fieldsToSelect,
			implode( ' OR ', $conditions ),
			__METHOD__,
			array( 'LIMIT' => 1 ),
			$joinConds
		);

		$terms = $this->buildTermResult( $this->getNormalizedJoinResult( $obtainedTerms, $joinCount ) );

		$this->releaseConnection( $dbr );

		wfProfileOut( __METHOD__ );

		return $terms;
	}

	/**
	 * Takes the result of a query with joins and turns it into a row per term.
	 *
	 * Also ditches any successive results PDO manages to add to the first one,
	 * so the behavior appears to be the same as when running the query against
	 * the database directly without PDO messing the the result up.
	 *
	 * @since 0.2
	 *
	 * @param ResultWrapper $obtainedTerms
	 * @param integer $joinCount
	 *
	 * @return array
	 */
	protected function getNormalizedJoinResult( \ResultWrapper $obtainedTerms, $joinCount ) {
		wfProfileIn( __METHOD__ );

		$resultTerms = array();

		foreach ( $obtainedTerms as $obtainedTerm ) {
			$obtainedTerm = (array)$obtainedTerm;

			for ( $tableIndex = 0; $tableIndex <= $joinCount; $tableIndex++ ) {
				$tableName = 'terms' . $tableIndex;
				$resultTerm = array();

				foreach ( array_keys( $this->termFieldMap ) as $fieldName ) {
					$fullFieldName = $tableName . $fieldName;

					if ( array_key_exists( $fullFieldName, $obtainedTerm ) ) {
						$resultTerm[$fieldName] = $obtainedTerm[$fullFieldName];
					}
				}

				if ( !empty( $resultTerm ) ) {
					$resultTerms[] = $resultTerm;
				}
			}
		}

		wfProfileOut( __METHOD__ );

		return $resultTerms;
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
