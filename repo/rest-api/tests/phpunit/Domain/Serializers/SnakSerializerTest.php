<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Domain\Serializers;

use DataValues\StringValue;
use Generator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Serializers\SnakSerializer as LegacySnakSerializer;
use Wikibase\DataModel\Serializers\TypedSnakSerializer;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException;
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

	/**
	 * @var MockObject|TypedSnakSerializer
	 */
	private $legacySnackSerializer;

	protected function setUp(): void {
		parent::setUp();

		$this->legacySnackSerializer = $this->createMock( LegacySnakSerializer::class );
		$this->legacySnackSerializer->method( 'serialize' )->willReturn( [ 'key' => 'value' ] );
	}

	/**
	 * @dataProvider snakProvider
	 */
	public function testSnakContainsDatatypeField( Snak $snak, string $expectedDatatype ): void {
		$this->assertSame(
			[ 'key' => 'value', 'datatype' => $expectedDatatype ],
			$this->newSerializer()->serialize( $snak )
		);
	}

	public function snakProvider(): Generator {
		yield [
			new PropertySomeValueSnak( new NumericPropertyId( 'P1' ) ),
			'something'
		];

		yield [
			new PropertyNoValueSnak( new NumericPropertyId( 'P2' ) ),
			'really'
		];

		yield [
			new PropertyValueSnak( new NumericPropertyId( 'P3' ), new StringValue( 'potato' ) ),
			'special'
		];
	}

	/**
	 * @return MockObject | PropertyDataTypeLookup
	 */
	private function newPropertyDataTypeLookup() {
		$propertyDataTypeLookup = $this->createMock( PropertyDataTypeLookup::class );
		$propertyDataTypeLookup->method( 'getDataTypeIdForProperty' )
			->willReturnCallback( function( NumericPropertyId $propertyId ) {
				switch ( $propertyId->getSerialization() ) {
					case 'P1':
						return 'something';
					case 'P2':
						return 'really';
					case 'P3':
						return 'special';
					default:
						throw new PropertyDataTypeLookupException( $propertyId );
				}
			} );

		return $propertyDataTypeLookup;
	}

	private function newSerializer(): SnakSerializer {
		return new SnakSerializer(
			new TypedSnakSerializer( $this->legacySnackSerializer ),
			$this->newPropertyDataTypeLookup()
		);
	}

}
