<?php

namespace Wikibase\QueryEngine\SQLStore\Engine;

use Ask\Language\Description\Description;
use Ask\Language\Description\SomeProperty;
use Ask\Language\Description\ValueDescription;
use Ask\Language\Option\QueryOptions;
use Wikibase\Database\QueryInterface;
use Wikibase\Lib\EntityIdParser;
use Wikibase\Lib\EntityRetrievingDataTypeLookup;
use Wikibase\QueryEngine\QueryEngine;
use Wikibase\QueryEngine\SQLStore\Schema;
use Wikibase\SnakRole;

/**
 * Simple query engine that works on top of the SQLStore.
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
 * @ingroup WikibaseSQLStore
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class DescriptionMatchFinder {

	protected $queryInterface;
	protected $schema;

	public function __construct( QueryInterface $queryInterface, Schema $schema ) {
		$this->queryInterface = $queryInterface;
		$this->schema = $schema;
	}

	/**
	 * Finds all entities that match the selection criteria.
	 * The matching entities are returned as an array of internal entity ids.
	 *
	 * @since 0.1
	 *
	 * @param Description $description
	 * @param QueryOptions $options
	 *
	 * @return int[]
	 */
	public function findMatchingEntities( Description $description, QueryOptions $options ) {
		if ( $description instanceof SomeProperty ) {
			$parser = new EntityIdParser();
			$propertyId = $parser->parse( $description->getProperty()->getValue() );
			$typeLookup = new EntityRetrievingDataTypeLookup();

			$dvHandler = $this->schema->getDataValueHandler(
				$typeLookup->getDataTypeIdForProperty( $propertyId ),
				SnakRole::MAIN_SNAK
			);

			$subDescription = $description->getDescription();

			if ( $subDescription instanceof ValueDescription ) {
				$dvHandler->getWhereConditions( $subDescription->getValue() );
			}


		}


		return array();

		// SomeProperty[AnyValue]: SELECT entity_id FROM $table WHERE property_id = $id

		// SomeProperty[ValueDescription]:
		// SELECT SELECT entity_id FROM $table WHERE property_id = $property_id AND
	}

}
