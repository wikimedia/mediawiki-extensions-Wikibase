<?php

namespace Wikibase\Lib\Tests;

use Psr\Log\NullLogger;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\Lookup\EntityRetrievingDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException;
use Wikibase\Lib\PropertyInfoDataTypeLookup;
use Wikibase\Lib\Store\PropertyInfoLookup;
use Wikibase\Lib\Tests\Store\MockPropertyInfoLookup;

/**
 * @covers \Wikibase\Lib\PropertyInfoDataTypeLookup
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class PropertyInfoDataTypeLookupTest extends \PHPUnit\Framework\TestCase {

	private $propertiesAndTypes = [
		'P1' => 'NyanData all the way across the sky',
		'P42' => 'string',
		'P1337' => 'percentage',
		'P9001' => 'positive whole number',
	];

	public function getDataTypeForPropertyProvider() {
		$argLists = [];

		$emptyInfoLookup = new MockPropertyInfoLookup();
		$mockInfoLookup = new MockPropertyInfoLookup();

		$entityLookup = new MockRepository();
		$propertyDataTypeLookup = new EntityRetrievingDataTypeLookup( $entityLookup );

		foreach ( $this->propertiesAndTypes as $propertyId => $dataTypeId ) {
			$id = new NumericPropertyId( $propertyId );

			// register property info
			$mockInfoLookup->addPropertyInfo(
				$id,
				[ PropertyInfoLookup::KEY_DATA_TYPE => $dataTypeId ]
			);

			// register property as an entity, for the fallback
			$property = Property::newFromType( $dataTypeId );
			$property->setId( $id );
			$entityLookup->putEntity( $property );

			// try with a working info store
			$argLists[] = [
				$mockInfoLookup,
				null,
				$id,
				$dataTypeId,
			];

			// try with via fallback
			$argLists[] = [
				$emptyInfoLookup,
				$propertyDataTypeLookup,
				$id,
				$dataTypeId,
			];
		}

		// try unknown property
		$id = new NumericPropertyId( 'P23' );

		// try with a working info store
		$argLists[] = [
			$mockInfoLookup,
			null,
			$id,
			false,
		];

		// try with via fallback
		$argLists[] = [
			$emptyInfoLookup,
			$propertyDataTypeLookup,
			$id,
			false,
		];

		return $argLists;
	}

	/**
	 * @dataProvider getDataTypeForPropertyProvider
	 */
	public function testGetDataTypeForProperty(
		PropertyInfoLookup $infoLookup,
		?PropertyDataTypeLookup $fallbackLookup,
		NumericPropertyId $propertyId,
		$expectedDataType
	) {
		if ( $expectedDataType === false ) {
			$this->expectException( PropertyDataTypeLookupException::class );
		}

		$lookup = new PropertyInfoDataTypeLookup( $infoLookup, new NullLogger(), $fallbackLookup );

		$actualDataType = $lookup->getDataTypeIdForProperty( $propertyId );

		if ( $expectedDataType !== false ) {
			$this->assertIsString( $actualDataType );
			$this->assertEquals( $expectedDataType, $actualDataType );
		}
	}

}
