<?php

namespace Wikibase\Lib\Test;

use DataValues\StringValue;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\DispatchingValueFormatter;
use Wikibase\Lib\PropertyValueSnakFormatter;
use Wikibase\Lib\SnakFormatter;
use Wikibase\PropertySomeValueSnak;
use Wikibase\PropertyValueSnak;

/**
 * @covers Wikibase\Lib\PropertyValueSnakFormatter
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group WikibaseLib
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class PropertyValueSnakFormatterTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider constructorErrorsProvider
	 *
	 * @param $format
	 * @param $error
	 */
	public function testConstructorErrors( $format, $error ) {
		$this->setExpectedException( $error );

		$typeLookup = $this->getMock( 'Wikibase\Lib\PropertyDataTypeLookup' );
		$typeLookup->expects( $this->never() )->method( 'getDataTypeIdForProperty' );

		$valueFormatter = new DispatchingValueFormatter( array() );

		new PropertyValueSnakFormatter( $format, $valueFormatter, $typeLookup );
	}

	public function constructorErrorsProvider() {
		return array(
			'format must be a string' => array(
				17,
				'InvalidArgumentException'
			),
		);
	}

	/**
	 * @dataProvider formatSnakProvider
	 * @covers PropertyValueSnakFormatter::formatSnak()
	 */
	public function testFormatSnak( $snak, $type, $formatters, $expected ) {
		$typeLookup = $this->getMock( 'Wikibase\Lib\PropertyDataTypeLookup' );
		$typeLookup->expects( $this->atLeastOnce() )
			->method( 'getDataTypeIdForProperty' )
			->will( $this->returnValue( $type ) );

		$formatter = new PropertyValueSnakFormatter(
			SnakFormatter::FORMAT_PLAIN,
			new DispatchingValueFormatter( $formatters ),
			$typeLookup
		);

		$this->assertEquals( $expected, $formatter->formatSnak( $snak ) );
	}

	public function formatSnakProvider() {
		$stringFormatter = $this->getMock( 'ValueFormatters\ValueFormatter' );
		$stringFormatter->expects( $this->any() )
			->method( 'format' )
			->will( $this->returnValue( 'VT:string' ) );

		$mediaFormatter = $this->getMock( 'ValueFormatters\ValueFormatter' );
		$mediaFormatter->expects( $this->any() )
			->method( 'format' )
			->will( $this->returnValue( 'PT:commonsMedia' ) );

		$formatters = array(
			'VT:string' => $stringFormatter,
			'PT:commonsMedia' => $mediaFormatter,
		);

		return array(
			'match PT' => array(
				new PropertyValueSnak( 17, new StringValue( 'Foo.jpg' ) ),
				'commonsMedia',
				$formatters,
				'PT:commonsMedia'
			),

			'match VT' => array(
				new PropertyValueSnak( 33, new StringValue( 'something' ) ),
				'someStuff',
				$formatters,
				'VT:string'
			),
		);
	}

	/**
	 * @covers PropertyValueSnakFormatter::getFormat()
	 */
	public function testGetFormat() {
		$typeLookup = $this->getMock( 'Wikibase\Lib\PropertyDataTypeLookup' );
		$typeLookup->expects( $this->never() )->method( 'getDataTypeIdForProperty' );

		$formatter = new PropertyValueSnakFormatter( 'test', new DispatchingValueFormatter( array() ), $typeLookup );
		$this->assertEquals( 'test', $formatter->getFormat() );
	}


	/**
	 * @covers MessageSnakFormatter::canFormatSnak()
	 */
	public function testCanFormatSnak() {
		$typeLookup = $this->getMock( 'Wikibase\Lib\PropertyDataTypeLookup' );
		$typeLookup->expects( $this->never() )->method( 'getDataTypeIdForProperty' );

		$formatter = new PropertyValueSnakFormatter( 'test', new DispatchingValueFormatter( array() ), $typeLookup );

		$snak = new PropertyValueSnak( new PropertyId( "P23" ), new StringValue( 'test' ) );
		$this->assertTrue( $formatter->canFormatSnak( $snak ), $snak->getType() );

		$snak = new PropertySomeValueSnak( new PropertyId( "P24" ) );
		$this->assertFalse( $formatter->canFormatSnak( $snak ), $snak->getType() );
	}

}
