<?php

namespace Wikibase\Lib\Test;

use Wikibase\EntityId;
use Wikibase\Lib\EntityRetrievingDataTypeLookup;
use Wikibase\Property;
use Wikibase\Test\MockRepository;

/**
 * Tests for the Wikibase\Lib\EntityRetrievingDataTypeLookup class.
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
 * @ingroup Test
 *
 * @group WikibaseLib
 * @group DataTypeLookupTest
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityRetrievingDataTypeLookupTest extends \PHPUnit_Framework_TestCase {

	private $propertiesAndTypes = array(
		1 => 'NyanData all the way across the sky',
		42 => 'string',
		1337 => 'percentage',
		9001 => 'positive whole number',
	);

	private function newEntityLookup() {
		$lookup = new MockRepository();

		foreach ( $this->propertiesAndTypes as $propertyId => $dataTypeId ) {
			$property = Property::newEmpty();
			$property->setId( $propertyId );
			$property->setDataTypeId( $dataTypeId );

			$lookup->putEntity( $property );
		}

		return $lookup;
	}

	public function getDataTypeForPropertyProvider() {
		$argLists = array();

		foreach ( $this->propertiesAndTypes as $propertyId => $dataTypeId ) {
			$argLists[] = array(
				new EntityId( Property::ENTITY_TYPE, $propertyId ),
				$dataTypeId
			);
		}


		return $argLists;
	}

	/**
	 * @dataProvider getDataTypeForPropertyProvider
	 *
	 * @param EntityId $propertyId
	 * @param string $expectedDataType
	 */
	public function testGetDataTypeForProperty( EntityId $propertyId, $expectedDataType ) {
		$lookup = new EntityRetrievingDataTypeLookup( $this->newEntityLookup() );

		$actualDataType = $lookup->getDataTypeIdForProperty( $propertyId );
		$this->assertInternalType( 'string', $actualDataType );

		$this->assertEquals( $expectedDataType, $actualDataType );
	}

	// TODO: tests for not found

}
