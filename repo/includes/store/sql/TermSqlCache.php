<?php

namespace Wikibase;

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
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
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

		$entityIdentifiers = array(
			'term_entity_id' => $entity->getId(),
			'term_entity_type' => $entity->getType()
		);

		$success = $dbw->delete(
			$this->tableName,
			$entityIdentifiers,
			__METHOD__
		);

		foreach ( $this->getEntityTerms( $entity ) as $term ) {
			$success = $dbw->insert(
				$this->tableName,
				array_merge(
					$term,
					$entityIdentifiers
				),
				__METHOD__
			) && $success;
		}

		return $success;
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

		return $dbw->delete(
			$this->tableName,
			array(
				'term_entity_id' => $entity->getId(),
				'term_entity_type' => $entity->getType()
			),
			__METHOD__
		);

		// TODO: failures here cause data that block valid stuff from being created to just stick around forever.
		// We probably want some extra handling here.
	}

	/**
	 * Returns a list with all the terms for the entity.
	 *
	 * @since 0.1
	 *
	 * @param Entity $entity
	 *
	 * @return array
	 */
	protected function getEntityTerms( Entity $entity ) {
		$terms = array();

		foreach ( $entity->getDescriptions() as $languageCode => $description ) {
			$terms[] = array(
				'term_language' => $languageCode,
				'term_type' => TermCache::TERM_TYPE_DESCRIPTION,
				'term_text' => $description,
			);
		}

		foreach ( $entity->getLabels() as $languageCode => $label ) {
			$terms[] = array(
				'term_language' => $languageCode,
				'term_type' => TermCache::TERM_TYPE_LABEL,
				'term_text' => $label,
			);
		}

		foreach ( $entity->getAllAliases() as $languageCode => $aliases ) {
			foreach ( $aliases as $alias ) {
				$terms[] = array(
					'term_language' => $languageCode,
					'term_type' => TermCache::TERM_TYPE_ALIAS,
					'term_text' => $alias,
				);
			}
		}

		return $terms;
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
	 * @see TermCache::getItemIdsForLabel
	 *
	 * @since 0.1
	 *
	 * @param string $label
	 * @param string|null $languageCode
	 * @param string|null $description
	 *
	 * @return array of integer
	 */
	public function getItemIdsForLabel( $label, $languageCode = null, $description = null ) {
		$db = $this->getReadDb();

		$tables = array( 'terms0' => $this->tableName );

		$conds = array(
			'terms0.term_text' => $label,
			'terms0.term_type' => TermCache::TERM_TYPE_LABEL,
		);

		$joinConds = array();

		if ( !is_null( $languageCode ) ) {
			$conds['terms0.term_language'] = $languageCode;
		}

		if ( !is_null( $description ) ) {
			$conds['terms1.term_text'] = $description;
			$conds['terms1.term_type'] = TermCache::TERM_TYPE_DESCRIPTION;

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

		$items = $db->select(
			$tables,
			array( 'terms0.term_entity_id' ),
			$conds,
			__METHOD__,
			array( 'DISTINCT' ),
			$joinConds
		);

		return array_map( function( $item ) { return $item->term_entity_id; }, iterator_to_array( $items ) );
	}

	/**
	 * @see TermCache::getMatchingTerms
	 *
	 * @since 0.1
	 *
	 * @param array $terms
	 * @param string|null $termType
	 * @param string|null $entityType
	 *
	 * @return array
	 */
	public function getMatchingTerms( array $terms, $termType = null, $entityType = null ) {
		return $this->doTermSelection( $terms, $termType, $entityType );
	}

	/**
	 * Similar to
	 * @see TermCache::getMatchingTerms
	 *
	 * But also upports self joins when $selfJoin is set to true.
	 * In this case one can prvide a list of terms in each outer array item.
	 *
	 * @since 0.1
	 *
	 * @param array $terms
	 * @param string|null $termType
	 * @param string|null $entityType
	 * @param boolean $selfJoin
	 *
	 * @return array
	 */
	protected function doTermSelection( array $terms, $termType = null, $entityType = null, $selfJoin = false ) {
		$allowedFields = array(
			'term_entity_type' => 'entityType',
			'term_type' => 'termType',
			'term_language' => 'termLanguage',
			'term_text' => 'termText',
		);

		list( $conditions, $hasJoin ) = $this->termsToConditions( $terms, $selfJoin, $allowedFields, $termType, $entityType );

		$allowedFields['term_entity_id'] = 'entityId';

		$joinConds = array();
		$tables = array( 'terms0' => $this->tableName );

		if ( $hasJoin ) {
			$tables['terms1'] = $this->tableName;

			$joinConds['terms1'] = array(
				'LEFT OUTER JOIN',
				array(
					'terms0.term_entity_id=terms1.term_entity_id',
					'terms0.term_entity_type=terms1.term_entity_type',
				)
			);
		}

		$obtainedTerms = $this->getReadDb()->select(
			$tables,
			array_keys( $allowedFields ),
			implode( ' OR ', $conditions ),
			__METHOD__,
			array(),
			$joinConds
		);

		return $this->buildTermResult( $obtainedTerms, $allowedFields );
	}

	/**
	 * @since 0.1
	 *
	 * @param array $terms
	 * @param boolean $selfJoin
	 * @param array $allowedFields
	 * @param string $termType
	 * @param string $entityType
	 *
	 * @return array[ array, boolean ]
	 */
	protected function termsToConditions( array $terms, $selfJoin, array $allowedFields, $termType, $entityType ) {
		$hasJoin = false;
		$conditions = array();

		$dbr = $this->getReadDb();

		foreach ( $terms as $termList ) {
			$termList = $selfJoin ? array_slice( $termList, 0, 2 ) : array( $termList );

			$tableIndex = 0;

			// Maps interface term fields to table fields and defaults
			// using the methods $termType and $entityType parameters.
			foreach ( $termList as $term ) {
				$fullTerm = array();

				if ( array_key_exists( 'termLanguage', $term ) ) {
					$fullTerm['term_language'] = $term['termLanguage'];
				}

				if ( array_key_exists( 'termText', $term ) ) {
					$fullTerm['term_text'] = $term['termText'];
				}

				if ( array_key_exists( 'termType', $term ) ) {
					$fullTerm['term_type'] = $term['termType'];
				}
				elseif ( $termType !== null ) {
					$fullTerm['term_type'] = $termType;
				}

				if ( array_key_exists( 'entityType', $term ) ) {
					$fullTerm['term_entity_type'] = $term['entityType'];
				}
				elseif ( $entityType !== null ) {
					$fullTerm['term_entity_type'] = $entityType;
				}

				$fullTerm = array_intersect_key( $fullTerm, $allowedFields );

				$tableName = 'terms' . $tableIndex++;

				foreach ( $fullTerm as $field => &$value ) {
					$value = $tableName . '.' . $field . '=' . $dbr->addQuotes( $value );
				}

				$conditions[] = '(' . implode( ' AND ', $fullTerm ) . ')';
			}

			if ( $tableIndex > 1 ) {
				$hasJoin = true;
			}
		}

		return array( $conditions, $hasJoin );
	}

	/**
	 * @since 0.1
	 *
	 * @param \ResultWrapper $obtainedTerms
	 * @param array $fieldMap
	 *
	 * @return array
	 */
	protected function buildTermResult( \ResultWrapper $obtainedTerms, array $fieldMap ) {
		$matchingTerms = array();

		foreach ( $obtainedTerms as $obtainedTerm ) {
			$matchingTerm = array();

			foreach ( $obtainedTerm as $key => $value ) {
				if ( $key === 'term_entity_id' ) {
					$value = (int)$value;
				}

				$matchingTerm[$fieldMap[$key]] = $value;
			}

			$matchingTerms[] = $matchingTerm;
		}

		return $matchingTerms;
	}

}
