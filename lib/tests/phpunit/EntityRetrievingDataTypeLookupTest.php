<?php

namespace Wikibase\Lib\Test;

use Wikibase\EntityId;
use Wikibase\Lib\EntityRetrievingDataTypeLookup;
use Wikibase\Property;
use Wikibase\Test\MockRepository;

/**
 * @covers Wikibase\Lib\EntityRetrievingDataTypeLookup
 *
 * @since 0.4
 *
 * @group Wikibase
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
