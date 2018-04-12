<?php

namespace Wikibase\Lib\Tests\Formatters;

use DataValues\StringValue;
use PHPUnit4And6Compat;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Lib\Formatters\BinaryOptionDispatchingSnakFormatter;
use Wikibase\Lib\SnakFormatter;

/**
 * @covers Wikibase\Lib\Formatters\BinaryOptionDispatchingSnakFormatter
 *
 * @group SnakFormatters
 * @group DataValueExtensions
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class BinaryOptionDispatchingSnakFormatterTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	/**
	 * @dataProvider formatSnakProvider
	 */
	public function testFormatSnak(
		Snak $snak,
		$specialHandling
	) {
		$formatter = new BinaryOptionDispatchingSnakFormatter(
			'text/whatever',
			$this->getPropertyDataTypeLookup(),
			$this->getSnakFormatter( $specialHandling ? 1 : 0, 'a' ),
			$this->getSnakFormatter( $specialHandling ? 0 : 1, 'b' ),
			[ 'sdfd', 'special', 'dsfd' ]
		);

		$this->assertSame(
			$specialHandling ? 'a' : 'b',
			$formatter->formatSnak( $snak )
		);
	}

	public function formatSnakProvider() {
		$pSpecial = new PropertyId( 'P1' );
		$pRegular = new PropertyId( 'P2' );
		$value = new StringValue( '' );

		return [
			'PropertyNoValueSnak gets fallback treatment always' => [
				new PropertyNoValueSnak( $pSpecial ),
				false
			],
			'PropertySomeValueSnak gets fallback treatment always' => [
				new PropertySomeValueSnak( $pSpecial ),
				false
			],
			'PropertyValueSnak with special treatment' => [
				new PropertyValueSnak( $pSpecial, $value ),
				true
			],
			'PropertyValueSnak without special treatment' => [
				new PropertyValueSnak( $pRegular, $value ),
				false
			],
			'Fallback on non-existing Properties' => [
				new PropertyValueSnak( new PropertyId( 'P3' ), $value ),
				false
			],
		];
	}

	public function testGetFormat() {
		$formatter = new BinaryOptionDispatchingSnakFormatter(
			'text/whatever',
			new InMemoryDataTypeLookup(),
			$this->getMock( SnakFormatter::class ),
			$this->getMock( SnakFormatter::class ),
			[]
		);

		$this->assertSame( 'text/whatever', $formatter->getFormat() );
	}

	/**
	 * @param int $expectedCallCount
	 * @param string $result
	 *
	 * @return SnakFormatter
	 */
	private function getSnakFormatter( $expectedCallCount, $result = '' ) {
		$snakFormatter = $this->getMock( SnakFormatter::class );
		$snakFormatter->expects( $this->exactly( $expectedCallCount ) )
			->method( 'formatSnak' )
			->with( $this->isInstanceOf( Snak::class ) )
			->will( $this->returnValue( $result ) );

		return $snakFormatter;
	}

	/**
	 * @return PropertyDataTypeLookup
	 */
	private function getPropertyDataTypeLookup() {
		$propertyDataTypeLookup = $this->getMock( PropertyDataTypeLookup::class );
		$propertyDataTypeLookup->expects( $this->any() )
			->method( 'getDataTypeIdForProperty' )
			->will( $this->returnCallback( function( PropertyId $propertyId ) {
				switch ( $propertyId->getSerialization() ) {
					case 'P1':
						return 'special';
					case 'P2':
						return 'something';
					default:
						throw new PropertyDataTypeLookupException( $propertyId );
				}
			} ) );

		return $propertyDataTypeLookup;
	}

}
