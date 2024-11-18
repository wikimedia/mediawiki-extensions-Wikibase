<?php

namespace Wikibase\DataModel\Services\Tests\Lookup;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\EntityRetrievingDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\InMemoryEntityLookup;

/**
 * @covers \Wikibase\DataModel\Services\Lookup\EntityRetrievingDataTypeLookup
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityRetrievingDataTypeLookupTest extends TestCase {

	/**
	 * @var string[]
	 */
	private static $propertiesAndTypes = [
		'P1' => 'NyanData all the way across the sky',
		'P42' => 'string',
		'P1337' => 'percentage',
		'P9001' => 'positive whole number',
	];

	/**
	 * @return EntityLookup
	 */
	private static function newEntityLookup() {
		$lookup = new InMemoryEntityLookup();

		foreach ( self::$propertiesAndTypes as $propertyId => $dataTypeId ) {
			$property = Property::newFromType( $dataTypeId );
			$property->setId( new NumericPropertyId( $propertyId ) );

			$lookup->addEntity( $property );
		}

		return $lookup;
	}

	public static function getDataTypeForPropertyProvider() {
		$argLists = [];

		foreach ( self::$propertiesAndTypes as $propertyId => $dataTypeId ) {
			$argLists[] = [
				new NumericPropertyId( $propertyId ),
				$dataTypeId,
			];
		}

		return $argLists;
	}

	/**
	 * @dataProvider getDataTypeForPropertyProvider
	 *
	 * @param NumericPropertyId $propertyId
	 * @param string $expectedDataType
	 */
	public function testGetDataTypeForProperty( NumericPropertyId $propertyId, $expectedDataType ) {
		$lookup = new EntityRetrievingDataTypeLookup( self::newEntityLookup() );

		$actualDataType = $lookup->getDataTypeIdForProperty( $propertyId );

		self::assertSame( $expectedDataType, $actualDataType );
	}

	// TODO: tests for not found

}
