<?php

namespace Wikibase\Lib;

use DataTypes\DataType;

/**
 * Defines the data types supported by Wikibase.
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
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class WikibaseDataTypeBuilders {

	public function __construct() {
		//TODO: take a service registry as a parameter. That's OK for a builder class.
	}

	/**
	 * @return array DataType builder specs
	 */
	public function getDataTypeBuilders() {
		return array(
			'wikibase-item' => array( $this, 'buildItemType' ),
			'commonsMedia' => array( $this, 'buildMediaType' ),
			'string' => array( $this, 'buildStringType' ),
			'time' => array( $this, 'buildTimeType' ),
			'globe-coordinate' => array( $this, 'buildCoordinateType' ),
		);
	}

	public function buildItemType( $id ) {
		return new DataType( $id, 'wikibase-entityid', array(), array(), array() );
	}

	public function buildMediaType( $id ) {
		return new DataType( $id, 'string', array(), array(), array() );
	}

	public function buildStringType( $id ) {
		return new DataType( $id, 'string', array(), array(), array() );
	}

	public function buildTimeType( $id ) {
		return new DataType( $id, 'time', array(), array(), array() );
	}

	public function buildCoordinateType( $id ) {
		return new DataType( $id, 'globecoordinate', array(), array(), array() );
	}

}
