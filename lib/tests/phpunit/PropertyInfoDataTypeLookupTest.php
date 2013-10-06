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
 * @covers Wikibase\Lib\PropertyInfoDataTypeLookup
 *
 * @since 0.4
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
