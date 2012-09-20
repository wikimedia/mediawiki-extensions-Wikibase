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
			'wb_terms',
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
				'term_type' => 'description',
				'term_text' => $description,
			);
		}

		foreach ( $entity->getLabels() as $languageCode => $label ) {
			$terms[] = array(
				'term_language' => $languageCode,
				'term_type' => 'label',
				'term_text' => $label,
			);
		}

		foreach ( $entity->getAllAliases() as $languageCode => $aliases ) {
			foreach ( $aliases as $alias ) {
				$terms[] = array(
					'term_language' => $languageCode,
					'term_type' => 'alias',
					'term_text' => $alias,
				);
			}
		}

		return $terms;
	}

	/**
	 * Returns the Databse from which to read.
	 *
	 * @since 0.1
	 *
	 * @return \DatabaseBase
	 */
	protected function getReadDb() {
		return wfGetDB( $this->readDb );
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
			'terms0.term_type' => 'label',
		);

		$joinConds = array();

		if ( !is_null( $languageCode ) ) {
			$conds['terms0.term_language'] = $languageCode;
		}

		if ( !is_null( $description ) ) {
			$conds['terms1.term_text'] = $description;
			$conds['terms1.term_type'] = 'description';

			if ( !is_null( $languageCode ) ) {
				$conds['terms1.term_language'] = $languageCode;
			}

			$tables['terms1'] = $this->tableName;

			$joinConds = array(
				'terms1' => array( 'LEFT OUTER JOIN', array( 'terms0.term_entity_id=terms1.term_entity_id', 'terms0.term_entity_type=terms1.term_entity_type' ) ),
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

}