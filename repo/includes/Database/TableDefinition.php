<?php

namespace Wikibase\Repo\Database;

use InvalidArgumentException;

/**
 * Definition of a database table.
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
 * @since wd.db
 *
 * @file
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class TableDefinition implements \Immutable {

	/**
	 * @since wd.db
	 *
	 * @var string
	 */
	private $name;

	/**
	 * @since wd.db
	 *
	 * @var FieldDefinition[]
	 */
	private $fields;

	/**
	 * Constructor.
	 *
	 * @since wd.db
	 *
	 * @param string $name
	 * @param FieldDefinition[] $fields
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $name, array $fields ) {
		if ( !is_string( $name ) ) {
			throw new InvalidArgumentException( 'The table $name needs to be a string' );
		}

		if ( empty( $fields ) ) {
			throw new InvalidArgumentException( 'The table $fields list cannot be empty' );
		}

		$this->name = $name;
		$this->fields = $fields;
	}

	/**
	 * Returns the name of the table.
	 *
	 * @since wd.db
	 *
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Returns the fields that make up this table.
	 *
	 * @since wd.db
	 *
	 * @return FieldDefinition[]
	 */
	public function getFields() {
		return $this->fields;
	}

	// TODO: multiple field indices

}