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
 */
class TermSqlCache implements TermCache {

	/**
	 * @since 0.1
	 *
	 * @var integer $readDb
	 */
	protected $readDb;

	/**
	 * @since 0.1
	 *
	 * @var string
	 */
	protected $tableName;

	/**
	 * Maps table fields to TermCache interface field names.
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
	 * @since 0.1
	 *
	 * @param string $tableName
	 * @param integer $readDb
	 */
	public function __construct( $tableName, $readDb = DB_SLAVE ) {
		$this->readDb = $readDb;
		$this->tableName = $tableName;
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
		$dbw = wfGetDB( DB_MASTER );
		$dbw->commit( __METHOD__, "flush" ); // flush to make sure we are not in some explicit transaction

		return $dbw->deadlockLoop( array( $this, 'saveTermsOfEntityInternal' ), $entity, $dbw );
	}

	/**
	 * Internal implementation of saveTermsOfEntity, called via DatabaseBase:deadlockLoop.
	 *
	 * @since 0.4
	 *
	 * @param Entity $entity
	 * @param DatabaseBase $dbw
	 *
	 * @return boolean Success indicator
	 */
	public function saveTermsOfEntityInternal( Entity $entity, DatabaseBase $dbw ) {
		//TODO: diff old against new, only update what's needed, if anything.

		$entityIdentifiers = array(
			'term_entity_id' => $entity->getId()->getNumericId(),
			'term_entity_type' => $entity->getType()
		);

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
					$entityIdentifiers
				),
				__METHOD__
			) && $success;
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
			$fields['term_search_key'] = $term->getNormalizedText();
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
		$dbw = wfGetDB( DB_MASTER );
		$dbw->commit( __METHOD__, "flush" ); // flush to make sure we are not in some explicit transaction

		return $dbw->deadlockLoop( array( $this, 'deleteTermsOfEntityInternal' ), $entity, $dbw );
	}

	/**
	 * Internal implementation of deleteTermsOfEntity, called via DatabaseBase:deadlockLoop.
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
	 * Returns the Database from which to read.
	 *
	 * @since 0.1
	 *
	 * @return \DatabaseBase
	 */
	protected function getReadDb() {
		return wfGetDB( $this->readDb );
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

		$result = $this->getReadDb()->selectRow(
			$this->tableName,
			array(
				'term_entity_id',
			),
			$conditions,
			__METHOD__
		);

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

		$obtainedTerms = $dbr->select(
			$this->tableName,
			$selectionFields,
			implode( ' OR ', $conditions ),
			__METHOD__
		);

		return $this->buildTermResult( $obtainedTerms );
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
					$text = $term->getNormalizedText();
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
		return wfGetDB( DB_MASTER )->delete( $this->tableName, '*', __METHOD__ );
	}

	/**
	 * @see TermCache::getMatchingTermCombination
	 *
	 * Note: the interface specifies capability for only a single join, which in this implementation
	 * is enforced by the $joinCount var. The code itself however could handle multiple joins.
	 *
	 * @since 0.2
	 *
	 * @param array $terms
	 * @param string|null $termType
	 * @param string|null $entityType
	 * @param integer|null $excludeId
	 * @param string|null $excludeType
	 *
	 * @return array
	 */
	public function getMatchingTermCombination( array $terms, $termType = null, $entityType = null, $excludeId = null, $excludeType = null ) {
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
				$exclusionConds[] = 'terms0.term_entity_id <> ' . $dbr->addQuotes( $excludeId );
			}

			if ( $excludeType !== null ) {
				$exclusionConds[] = 'terms0.term_entity_type <> ' . $dbr->addQuotes( $excludeType );
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

		return $this->buildTermResult( $this->getNormalizedJoinResult( $obtainedTerms, $joinCount ) );
	}

	/**
	 * Takes the result of a query with joins and turns it into a row per term.
	 *
	 * Also ditches any successive results PDO manages to add to the first one,
	 * so the behaviour appears to be the same as when running the query against
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
	 * Rebuild the search key field term_search_key from the source term_text field.
	 *
	 * FIXME: for some unknown reason some rows are skipped in the rebuild
	 *
	 * @since 0.2
	 */
	public function rebuildSearchKey() {
		$dbw = wfGetDB( DB_MASTER );

		$entityId = -1;
		$entityType = '';
		$language = '';
		$type = '';
		$text = '';

		$limit = 10;

		$hasMoreResults = true;

		while ( $hasMoreResults ) {
			$terms = $dbw->select(
				$this->tableName,
				array(
					'term_entity_id',
					'term_entity_type',
					'term_language',
					'term_type',
					'term_text',
				),
				array(
					'term_entity_id >= ' . $dbw->addQuotes( $entityId ),
					'term_entity_type >= ' . $dbw->addQuotes( $entityType ),
					'term_language >= ' . $dbw->addQuotes( $language ),
					'term_type >= ' . $dbw->addQuotes( $type ),
					'term_text >= ' . $dbw->addQuotes( $text ),
				),
				__METHOD__,
				array(
					'LIMIT' => $limit,
					'ORDER BY term_entity_id, term_entity_type, term_language, term_type, term_text ASC, ASC, ASC, ASC, ASC'
				)
			);

			$continuationTerm = $this->rebuildSearchKeyForTerms( $terms, $dbw, $limit );

			if ( $continuationTerm === null ) {
				$hasMoreResults = false;
			}
			else {
				$entityId = $continuationTerm->term_entity_id;
				$entityType = $continuationTerm->term_entity_type;
				$language = $continuationTerm->term_language;
				$type = $continuationTerm->term_type;
				$text = $continuationTerm->term_text;
			}
		}
	}

	/**
	 * @since 0.2
	 *
	 * @param Iterator $terms
	 * @param DatabaseBase $dbw
	 * @param integer $limit
	 *
	 * @return object|null The continuation term if there is one or null
	 */
	protected function rebuildSearchKeyForTerms( Iterator $terms, DatabaseBase $dbw, $limit ) {
		$termNumber = 0;

		$doTrx = $dbw->trxLevel() === 0;

		if ( $doTrx ) {
			$dbw->begin();
		}

		$hasMoreResults = false;

		foreach ( $terms as $term ) {
			$dbw->update(
				$this->tableName,
				array(
					'term_search_key' => Term::normalizeText( $term->term_text )
				),
				array(
					'term_entity_id' => $term->term_entity_id,
					'term_entity_type' => $term->term_entity_type,
					'term_language' => $term->term_language,
					'term_type' => $term->term_type,
					'term_text' => $term->term_text,
				),
				__METHOD__
			);

			if ( ++$termNumber >= $limit ) {
				$hasMoreResults = true;
			}
		}

		if ( $doTrx ) {
			$dbw->commit();
		}

		return $hasMoreResults ? $term : null;
	}

}
