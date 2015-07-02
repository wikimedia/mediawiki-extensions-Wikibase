<?php

namespace Wikibase\DataModel\Tests\Snak;

use DataValues\StringValue;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\DerivedPropertyValueSnak;

/**
 * @covers Wikibase\DataModel\Snak\DerivedPropertyValueSnak
 *
 * @group Wikibase
 * @group WikibaseDataModel
 * @group WikibaseSnak
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class DerivedPropertyValueSnakTest extends SnakObjectTest {

	public function constructorProvider() {
		return array(
			'No extras' => array(
				true,
				new PropertyId( 'P1' ),
				new StringValue( 'a' ),
				array(),
			),
			'2 extras' => array(
				true,
				new PropertyId( 'P9001' ),
				new StringValue( 'bc' ),
				array( 'foo' => new StringValue( 'foo' ), 'bar' => new StringValue( 'bar' ) ),
			),
			'fail - Integer key' => array(
				'InvalidArgumentException',
				new PropertyId( 'P9001' ),
				new StringValue( 'bc' ),
				array( new StringValue( 'foo' ) ),
			),
			'fail - not a value' => array(
				'InvalidArgumentException',
				new PropertyId( 'P9001' ),
				new StringValue( 'bc' ),
				array( 'foo' => 'bar' ),
			),
		);
	}

	public function getClass() {
		return 'Wikibase\DataModel\Snak\DerivedPropertyValueSnak';
	}

	/**
	 * This test is a safeguard to make sure hashes are not changed unintentionally.
	 */
	public function testHashStability() {
		$snak = new DerivedPropertyValueSnak(
			new PropertyId( 'P1' ),
			new StringValue( 'a' ),
			array( 'foo' => new StringValue( 'foo' ) )
		);
		$hash = $snak->getHash();

		// @codingStandardsIgnoreStart
		$expected = sha1( 'C:48:"Wikibase\DataModel\Snak\DerivedPropertyValueSnak":53:{a:2:{i:0;i:1;i:1;C:22:"DataValues\StringValue":1:{a}}}' );
		// @codingStandardsIgnoreEnd
		$this->assertSame( $expected, $hash );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetDerivedDataValues( DerivedPropertyValueSnak $snak, $args ) {
		$dataValues = $snak->getDerivedDataValues();
		$this->assertEquals( $args[2], $dataValues );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetDerivedDataValue( DerivedPropertyValueSnak $snak, $args ) {
		$this->assertEquals( null, $snak->getDerivedDataValue( 'ponies and unicorns' ) );
		foreach ( $args[2] as $expectedKey => $expectedDataValue ) {
			$dataValue = $snak->getDerivedDataValue( $expectedKey );
			$this->assertEquals( $expectedDataValue, $dataValue );
		}
	}

}
