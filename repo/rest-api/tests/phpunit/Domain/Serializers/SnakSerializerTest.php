<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Domain\Serializers;

use DataValues\StringValue;
use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Serializers\SnakSerializer as LegacySnakSerializer;
use Wikibase\DataModel\Serializers\TypedSnakSerializer;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Repo\RestApi\Domain\Serializers\SnakSerializer;

/**
 * @covers \Wikibase\Repo\RestApi\Domain\Serializers\SnakSerializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SnakSerializerTest extends TestCase {

	private const STUB_SNAK_SERIALIZATION = [ 'key' => 'value' ];
	private const PROPERTY_DATA = [
		[ 'propertyId' => 'P1', 'datatype' => 'string' ],
		[ 'propertyId' => 'P123', 'datatype' => 'quantity' ],
		[ 'propertyId' => 'P321', 'datatype' => 'item' ],
	];

	/**
	 * @dataProvider snakProvider
	 */
	public function testSnakContainsDatatypeField( Snak $snak, string $expectedDatatype ): void {
		$this->assertSame(
			self::STUB_SNAK_SERIALIZATION + [ 'datatype' => $expectedDatatype ],
			$this->newSerializer()->serialize( $snak )
		);
	}

	public function snakProvider(): Generator {
		yield [
			new PropertySomeValueSnak( new NumericPropertyId( self::PROPERTY_DATA[0]['propertyId'] ) ),
			self::PROPERTY_DATA[0]['datatype']
		];

		yield [
			new PropertyNoValueSnak( new NumericPropertyId( self::PROPERTY_DATA[1]['propertyId'] ) ),
			self::PROPERTY_DATA[1]['datatype']
		];

		yield [
			new PropertyValueSnak( new NumericPropertyId( self::PROPERTY_DATA[2]['propertyId'] ), new StringValue( 'potato' ) ),
			self::PROPERTY_DATA[2]['datatype']
		];
	}

	private function newPropertyDataTypeLookup(): PropertyDataTypeLookup {
		$propertyDataTypeLookup = new InMemoryDataTypeLookup();
		foreach ( self::PROPERTY_DATA as $property ) {
			$propertyDataTypeLookup->setDataTypeForProperty( new NumericPropertyId( $property['propertyId'] ), $property['datatype'] );
		}

		return $propertyDataTypeLookup;
	}

	private function newSerializer(): SnakSerializer {
		$legacySerializer = $this->createStub( LegacySnakSerializer::class );
		$legacySerializer->method( 'serialize' )->willReturn( self::STUB_SNAK_SERIALIZATION );

		return new SnakSerializer(
			new TypedSnakSerializer( $legacySerializer ),
			$this->newPropertyDataTypeLookup()
		);
	}

}
