<?php

namespace Wikibase\DataModel\Tests\Snak;

use DataValues\NumberValue;
use DataValues\StringValue;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;

/**
 * @covers \Wikibase\DataModel\Snak\PropertyNoValueSnak
 * @covers \Wikibase\DataModel\Snak\PropertySomeValueSnak
 * @covers \Wikibase\DataModel\Snak\PropertyValueSnak
 * @uses \Wikibase\DataModel\Snak\SnakObject
 * @uses \DataValues\NumberValue
 * @uses \DataValues\StringValue
 * @uses \Wikibase\DataModel\Entity\EntityId
 * @uses \Wikibase\DataModel\Entity\NumericPropertyId
 *
 * @group Wikibase
 * @group WikibaseDataModel
 * @group WikibaseSnak
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SnakTest extends \PHPUnit\Framework\TestCase {

	public static function snakProvider(): iterable {
		$id42 = new NumericPropertyId( 'p42' );

		yield [ new PropertyNoValueSnak( $id42 ) ];
		yield [ new PropertySomeValueSnak( $id42 ) ];
		yield [ new PropertyValueSnak( $id42, new StringValue( 'Ohi there!' ) ) ];
		yield [ new PropertyValueSnak( $id42, new NumberValue( 42 ) ) ];
	}

	/**
	 * @dataProvider snakProvider
	 * @param Snak $snak
	 */
	public function testGetType( Snak $snak ) {
		$this->assertIsString( $snak->getType() );
	}

	/**
	 * @dataProvider snakProvider
	 * @param Snak $snak
	 */
	public function testGetPropertyId( Snak $snak ) {
		$this->assertInstanceOf( NumericPropertyId::class, $snak->getPropertyId() );
	}

	/**
	 * @dataProvider snakProvider
	 * @param Snak $snak
	 */
	public function testSerialize( Snak $snak ) {
		$serialization = serialize( $snak );
		$this->assertIsString( $serialization );

		$newInstance = unserialize( $serialization );
		$this->assertInstanceOf( get_class( $snak ), $newInstance );

		$this->assertEquals( $snak, $newInstance );
		$this->assertEquals( $snak->getPropertyId(), $newInstance->getPropertyId() );
		$this->assertSame( $snak->getType(), $newInstance->getType() );
	}

	/**
	 * @dataProvider snakProvider
	 * @param Snak $snak
	 */
	public function testGetHash( Snak $snak ) {
		$hash = $snak->getHash();
		$this->assertIsString( $hash );
		$this->assertSame( $hash, $snak->getHash() );
	}

	/**
	 * @dataProvider snakProvider
	 * @param Snak $snak
	 */
	public function testEquals( Snak $snak ) {
		$this->assertTrue( $snak->equals( $snak ) );
		$this->assertFalse( $snak->equals( '~=[,,_,,]:3' ) );
	}

	public function testEqualsMoar() {
		$id42 = new NumericPropertyId( 'p42' );

		$snak = new PropertyNoValueSnak( $id42 );

		$this->assertFalse( $snak->equals( new PropertySomeValueSnak( $id42 ) ) );

		$this->assertFalse( $snak->equals( new PropertyValueSnak(
			$id42,
			new StringValue( 'Ohi there!' )
		) ) );

		$id43 = new NumericPropertyId( 'p43' );

		$this->assertFalse( $snak->equals( new PropertyNoValueSnak( $id43 ) ) );
	}

}
