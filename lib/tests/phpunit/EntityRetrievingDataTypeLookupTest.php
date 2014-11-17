<?php

namespace Wikibase\Lib\Test;

use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\EntityRetrievingDataTypeLookup;
use Wikibase\Test\MockRepository;

/**
 * @covers Wikibase\Lib\EntityRetrievingDataTypeLookup
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
		'P1' => 'NyanData all the way across the sky',
		'P42' => 'string',
		'P1337' => 'percentage',
		'P9001' => 'positive whole number',
	);

	private function newEntityLookup() {
		$mockRepository = new MockRepository();

		foreach ( $this->propertiesAndTypes as $propertyId => $dataTypeId ) {
			$property = Property::newFromType( $dataTypeId );
			$property->setId( new PropertyId( $propertyId ) );

			$mockRepository->putEntity( $property );
		}

		return $mockRepository;
	}

	public function getDataTypeForPropertyProvider() {
		$argLists = array();

		foreach ( $this->propertiesAndTypes as $propertyId => $dataTypeId ) {
			$argLists[] = array(
				new PropertyId( $propertyId ),
				$dataTypeId
			);
		}

		return $argLists;
	}

	/**
	 * @dataProvider getDataTypeForPropertyProvider
	 *
	 * @param PropertyId $propertyId
	 * @param string $expectedDataType
	 */
	public function testGetDataTypeForProperty( PropertyId $propertyId, $expectedDataType ) {
		$lookup = new EntityRetrievingDataTypeLookup( $this->newEntityLookup() );

		$actualDataType = $lookup->getDataTypeIdForProperty( $propertyId );
		$this->assertInternalType( 'string', $actualDataType );

		$this->assertEquals( $expectedDataType, $actualDataType );
	}

	// TODO: tests for not found

}
