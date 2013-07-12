<?php

namespace Wikibase;
use Iterator, DatabaseBase;

/**
 * Term lookup cache.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Jens Ohlig < jens.ohlig@wikimedia.de >
 * @author Daniel Kinzler
 */
class TermSqlIndex extends \DBAccessBase implements TermIndex {

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
	 * @see TermCache::saveTermsOfEntity
	 *
	 * @since 0.1
	 *
	 * @param Entity $entity
	 *
	 * @return boolean Success indicator
	 */
	public function saveTermsOfEntity( Entity $entity ) {
		//First check whether there's anything to update
		$newTerms = $entity->getTerms();
		$oldTerms = $this->getTermsOfEntity( $entity->getId() );

		//NOTE: for now, we just check if anything changed, and if yes, update all the entities's
		//      terms in the database.
		//TODO: generate lists of terms to add resp. remove and pass them to saveTermsOfEntityInternal

		if ( count( $newTerms ) === count( $oldTerms ) ) {
			usort( $newTerms, 'Wikibase\Term::compare' );
			usort( $oldTerms, 'Wikibase\Term::compare' );
			$equal = true;

			foreach ( $newTerms as $i => $term ) {
				if ( !$term->equals( $oldTerms[$i] ) ) {
					$equal = false;
					break;
				}
			}

			if ( $equal ) {
				wfDebugLog( __CLASS__, __FUNCTION__ . ": terms did not change, returning." );
				return true; // nothing to do.
			}
		}

		$dbw = $this->getConnection( DB_MASTER );
		$dbw->commit( __METHOD__, "flush" ); // flush to make sure we are not in some explicit transaction

		$ok = $dbw->deadlockLoop( array( $this, 'saveTermsOfEntityInternal' ), $entity, $dbw );
		$this->releaseConnection( $dbw );

		return $ok;
	}

	/**
	 * Internal implementation of saveTermsOfEntity, called via DatabaseBase:deadlockLoop.
	 *
	 * @note: this is public only because it acts as a callback, there should be no
	 *        reason to call this directly!
	 *
	 * @since 0.4
	 *
	 * @param Entity $entity
	 * @param DatabaseBase $dbw
	 *
	 * @return boolean Success indicator
	 */
	public function saveTermsOfEntityInternal( Entity $entity, DatabaseBase $dbw ) {
		$entityIdentifiers = array(
			'term_entity_id' => $entity->getId()->getNumericId(),
			'term_entity_type' => $entity->getType()
		);

		// very simple weighting calculation.
		// TODO delegate this to an object of its own
		$entityWeight = array();
		if ( $dbw->fieldExists( $this->tableName, 'term_weight' ) ) {
			if ( $entity instanceof Item ) {
				$weight = count( $entity->getSimpleSiteLinks() ) / 1000.0;
				$entityWeight['term_weight'] = $weight;
			}
		}

		wfDebugLog( __CLASS__, __FUNCTION__ . ": updating terms for " . $entity->getId()->getPrefixedId() );

		$success = $dbw->delete(
			$this->tableName,
			$entityIdentifiers,
			__METHOD__
		);

		/**
		 * @var Term $term
		 */
		foreach ( $entity->getTerms() as $term ) {
			$success = $dbw->insert(
				$this->tableName,
				array_merge(
					$this->getTermFields( $term ),
					$entityIdentifiers,
					$entityWeight
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
	 * @see TermCache::deleteTermsOfEntity
	 *
	 * @since 0.1
	 *
	 * @param Entity $entity
	 *
	 * @return boolean Success indicator
	 */
	public function deleteTermsOfEntity( Entity $entity ) {
		$dbw = $this->getConnection( DB_MASTER );

		//TODO: do this via deadlockLoop. Currently triggers warnings, because deleteTermsOfEntity
		//      is called from EntityDeletionUpdate, which is called from within the transaction
		//      started by WikiPage::doDeleteArticleReal.
		/*
		$dbw->commit( __METHOD__, "flush" ); // flush to make sure we are not in some explicit transaction
		return $dbw->deadlockLoop( array( $this, 'deleteTermsOfEntityInternal' ), $entity, $dbw );
		*/

		$ok = $this->deleteTermsOfEntityInternal( $entity, $dbw );
		$this->releaseConnection( $dbw );

		return $ok;
	}

	/**
	 * Internal implementation of deleteTermsOfEntity, called via DatabaseBase:deadlockLoop.
	 *
	 * @note: this is public only because it acts as a callback, there should be no
	 *        reason to call this directly!
	 *
	 * @since 0.4
	 *
	 * @param Entity $entity
	 * @param DatabaseBase $dbw
	 *
	 * @return boolean Success indicator
	 */
	public function deleteTermsOfEntityInternal( Entity $entity, DatabaseBase $dbw ) {

		return $dbw->delete(
			$this->tableName,
			array(
				'term_entity_id' => $entity->getId()->getNumericId(),
				'term_entity_type' => $entity->getType()
			),
			__METHOD__
		);

		// TODO: failures here cause data that block valid stuff from being created to just stick around forever.
		// We probably want some extra handling here.
	}

	/**
	 * Returns the terms stored for the given entity.
	 * @see TermCache::getTermsOfEntity
	 *
	 * @param EntityId $id
	 *
	 * @return Term[]
	 */
	public function getTermsOfEntity( EntityId $id ) {
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
		return $terms;
	}

	/**
	 * Returns the terms stored for the given entities.
	 *
	 * @see TermCache::getTermsOfEntities
	 *
	 * @param EntityId[] $ids
	 * @param string $entityType
	 * @param string|null $language Language code
	 *
	 * @return Term[]
	 */
	public function getTermsOfEntities( array $ids, $entityType, $language = null ) {
		if ( empty($ids) ) {
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
				throw new \MWException( "ID " . $id->getPrefixedId()
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
		return $terms;
	}

	/**
	 * Returns the Database connection from which to read.
	 *
	 * @since 0.1
	 *
	 * @return \DatabaseBase
	 */
	public function getReadDb() {
		return $this->getConnection( DB_SLAVE );
	}

	/**
	 * Returns the Database connection to wich to write.
	 *
	 * @since 0.4
	 *
	 * @return \DatabaseBase
	 */
	public function getWriteDb() {
		return $this->getConnection( DB_MASTER );
	}

	/**
	 * @see TermCache::termExists
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
		return $result !== false;
	}

	/**
	 * @see TermCache::getEntityIdsForLabel
	 *
	 * @since 0.1
	 *
	 * @param string $label
	 * @param string|null $languageCode
	 * @param string|null $description
	 * @param string|null $entityType
	 * @param bool $fuzzySearch if false, only exact matches are returned, otherwise more relaxed search . Defaults to false.
	 *
	 * @return array of array( entity type, entity id )
	 */
	public function getEntityIdsForLabel( $label, $languageCode = null, $description = null, $entityType = null, $fuzzySearch = false ) {
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

		if ( !is_null( $description ) ) {
			$conds['terms1.term_text'] = $description;
			$conds['terms1.term_type'] = Term::TYPE_DESCRIPTION;

			if ( !is_null( $languageCode ) ) {
				$conds['terms1.term_language'] = $languageCode;
			}

			$tables['terms1'] = $this->tableName;

			$joinConds['terms1'] = array(
				'LEFT OUTER JOIN',
				array(
					'terms0.term_entity_id=terms1.term_entity_id',
					'terms0.term_entity_type=terms1.term_entity_type',
				)
			);
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

		return array_map(
			function( $entity ) {
				return array( $entity->term_entity_type, intval( $entity->term_entity_id ) );
			},
			iterator_to_array( $entities )
		);
	}

	/**
	 * @see TermCache::getMatchingTerms
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
		if ( empty( $terms ) ) {
			return array();
		}

		$conditions = $this->termsToConditions( $terms, $termType, $entityType, false, $options );

		$selectionFields = array_keys( $this->termFieldMap );

		$dbr = $this->getReadDb();

		$queryOptions = array();

		if ( array_key_exists( 'LIMIT', $options ) && $options['LIMIT'] ) {
			$queryOptions['LIMIT'] = $options['LIMIT'];
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
		return $terms;
	}

	/**
	 * @see TermCache::getMatchingIDs
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
		if ( empty( $terms ) ) {
			return array();
		}

		// this is the maximum limit of search results TODO this should not be hardcoded
		$internalLimit = 5000;

		$conditions = $this->termsToConditions( $terms, null, $entityType, false, $options );

		$dbr = $this->getReadDb();

		$selectionFields = array( 'term_entity_id' );
		if ( $dbr->fieldExists( $this->tableName, 'term_weight' ) ) {
			$selectionFields[] = 'term_weight';
		}

		$queryOptions = array( 'DISTINCT' );

		if ( array_key_exists( 'LIMIT', $options ) && $options['LIMIT'] ) {
			$queryOptions['LIMIT'] = max( $options['LIMIT'], $internalLimit );
		}

		$obtainedIDs = $dbr->select(
			$this->tableName,
			$selectionFields,
			implode( ' OR ', $conditions ),
			__METHOD__,
			$queryOptions
		);

		$entityIds = array();
		$weights = array();
		foreach ( $obtainedIDs as $obtainedID ) {
			$entityIds[] = new EntityId( $entityType, (int)$obtainedID->term_entity_id );
			if ( array_key_exists( 'term_weight', $obtainedID ) ) {
				$weights[] = floatval( $obtainedID->term_weight );
			} else {
				$weights[] = 0.0;
			}
		}
		$this->releaseConnection( $dbr );

		// this is a post-search sorting by weight. This allows us to not require an additional
		// index on the wb_terms table that is very big already. This is also why we have
		// the internal limit of 5000, since SQL's index would explode in size if we added the
		// weight to it here (which would allow us to delegate the sorting to SQL itself)
		array_multisort( $weights, SORT_DESC, SORT_NUMERIC, $entityIds );

		if ( array_key_exists( 'LIMIT', $options ) && $options['LIMIT'] ) {
			$result = array_slice( $entityIds, 0, $options['LIMIT'] );
		} else {
			$result = $entityIds;
		}

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
					$fullTerm[] = $textField . ' LIKE ' . $dbr->addQuotes( $text . '%' );
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
		return $conditions;
	}

	/**
	 * Modifies the provided terms to use the field names expected by the interface
	 * rather then the table field names. Also ensures the values are of the correct type.
	 *
	 * @since 0.2
	 *
	 * @param \Iterator|array $obtainedTerms PHP fails for not having a common iterator/array thing :<0
	 *
	 * @return array
	 */
	protected function buildTermResult( $obtainedTerms ) {
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

		return $matchingTerms;
	}

	/**
	 * @see TermCache::clear
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
	 * @see TermCache::getMatchingTermCombination
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
		if ( empty( $terms ) ) {
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

			$conditions[] = implode( ' AND ', $combinationConds );
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
	 * @param \ResultWrapper $obtainedTerms
	 * @param integer $joinCount
	 *
	 * @return array
	 */
	protected function getNormalizedJoinResult( \ResultWrapper $obtainedTerms, $joinCount ) {
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
}
