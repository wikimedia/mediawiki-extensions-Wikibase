<?php

namespace Wikibase;

use Ask\Language\Query as QueryDefinition;
use MWException;

/**
 * Represents a single Wikibase query.
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
 * @ingroup WikibaseLib
 * @ingroup WikibaseQuery
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class Query extends Entity {

	const ENTITY_TYPE = 'query';

	/**
	 * @since 0.4
	 *
	 * @var QueryDefinition|null
	 */
	protected $queryDefinition = null;

	/**
	 * Returns the QueryDefinition of the query entity.
	 *
	 * @since 0.4
	 *
	 * @return QueryDefinition
	 * @throws MWException
	 */
	public function getQueryDefinition() {
		if ( $this->queryDefinition === null ) {
			if ( array_key_exists( 'querydefinition', $this->data ) ) {
				// TODO
			}
			else {
				throw new MWException( 'The QueryDefinition of the query is not known' );
			}
		}

		return $this->queryDefinition;
	}

	/**
	 * Sets the QueryDefinition of the query entity.
	 *
	 * @since 0.4
	 *
	 * @param QueryDefinition $queryDefinition
	 */
	public function setQueryDefinition( QueryDefinition $queryDefinition ) {
		$this->queryDefinition = $queryDefinition;
	}

	/**
	 * @see Entity::newFromArray
	 *
	 * @since 0.1
	 *
	 * @param array $data
	 *
	 * @return Query
	 */
	public static function newFromArray( array $data ) {
		return new static( $data );
	}

	/**
	 * @since 0.1
	 *
	 * @return Query
	 */
	public static function newEmpty() {
		return self::newFromArray( array() );
	}

	/**
	 * @see Entity::getType
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getType() {
		return Query::ENTITY_TYPE;
	}

	/**
	 * @see Entity::getLocalType
	 *
	 * @since 0.2
	 *
	 * @return string
	 */
	public function getLocalizedType() {
		return wfMessage( 'wikibase-entity-query' )->parse();
	}

}
