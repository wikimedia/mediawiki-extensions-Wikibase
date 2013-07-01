<?php

namespace Wikibase\Lib\Test;

use Wikibase\EntityId;
use Wikibase\Lib\EntityRetrievingDataTypeLookup;
use Wikibase\Lib\PropertyDataTypeLookup;
use Wikibase\Lib\PropertyInfoDataTypeLookup;
use Wikibase\Property;
use Wikibase\PropertyInfoStore;
use Wikibase\Test\MockPropertyInfoStore;
use Wikibase\Test\MockRepository;

/**
 * Tests for the Wikibase\Lib\PropertyInfoDataTypeLookup class.
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
 * @group Wikibase
 * @group WikibaseLib
 * @group DataTypeLookupTest
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class PropertyInfoDataTypeLookupTest extends \PHPUnit_Framework_TestCase {

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

		$emptyInfoStore = new MockPropertyInfoStore();
		$mockInfoStore = new MockPropertyInfoStore();

		$mockRepo = new MockRepository();
		$mockDataTypeLookup = new EntityRetrievingDataTypeLookup( $mockRepo );

		foreach ( $this->propertiesAndTypes as $propertyId => $dataTypeId ) {
			$id = new EntityId( Property::ENTITY_TYPE, $propertyId );

			// register property info
			$mockInfoStore->setPropertyInfo(
				$id,
				array( PropertyInfoStore::KEY_DATA_TYPE => $dataTypeId )
			);

			// register property as an entity, for the fallback
			$property = Property::newEmpty();
			$property->setId( $id );
			$property->setDataTypeId( $dataTypeId );
			$mockRepo->putEntity( $property );

			// try with a working info store
			$argLists[] = array(
				$mockInfoStore,
				null,
				$id,
				$dataTypeId
			);

			// try with via fallback
			$argLists[] = array(
				$emptyInfoStore,
				$mockDataTypeLookup,
				$id,
				$dataTypeId
			);
		}

		// try unknown property
		$id = new EntityId( Property::ENTITY_TYPE, 23 );

		// try with a working info store
		$argLists[] = array(
			$mockInfoStore,
			null,
			$id,
			false
		);

		// try with via fallback
		$argLists[] = array(
			$emptyInfoStore,
			$mockDataTypeLookup,
			$id,
			false
		);

		return $argLists;
	}

	/**
	 * @dataProvider getDataTypeForPropertyProvider
	 */
	public function testGetDataTypeForProperty(
		PropertyInfoStore $infoStore,
		PropertyDataTypeLookup $fallbackLookup = null,
		EntityId $propertyId,
		$expectedDataType
	) {
		if ( $expectedDataType === false ) {
			$this->setExpectedException( 'Wikibase\Lib\PropertyNotFoundException' );
		}

		$lookup = new PropertyInfoDataTypeLookup( $infoStore, $fallbackLookup );

		$actualDataType = $lookup->getDataTypeIdForProperty( $propertyId );

		if ( $expectedDataType !== false ) {
			$this->assertInternalType( 'string', $actualDataType );
			$this->assertEquals( $expectedDataType, $actualDataType );
		}
	}

}
