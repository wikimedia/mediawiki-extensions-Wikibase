<?php

namespace Wikibase\Lib\Test;

use DataValues\StringValue;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\DispatchingSnakFormatter;
use Wikibase\Lib\MessageSnakFormatter;
use Wikibase\Lib\SnakFormatter;
use Wikibase\PropertyNoValueSnak;
use Wikibase\PropertySomeValueSnak;
use Wikibase\PropertyValueSnak;

/**
 * @covers Wikibase\Lib\DispatchingSnakFormatter
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group WikibaseLib
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class DispatchingSnakFormatterTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider constructorErrorsProvider
	 *
	 * @param $format
	 * @param $formatters
	 * @param $error
	 */
	public function testConstructorErrors( $format, $formatters, $error ) {
		$this->setExpectedException( $error );

		new DispatchingSnakFormatter( $format, $formatters );
	}

	public static function constructorErrorsProvider() {
		$formatter = new MessageSnakFormatter( 'novalue', wfMessage( 'wikibase-snakview-snaktypeselector-novalue' ), SnakFormatter::FORMAT_PLAIN );

		return array(
			'format must be a string' => array(
				17,
				array(),
				'InvalidArgumentException'
			),
			'keys must be strings' => array(
				SnakFormatter::FORMAT_PLAIN,
				array( 17 => $formatter ),
				'InvalidArgumentException'
			),
			'formatters must be instances of SnakFormatter' => array(
				SnakFormatter::FORMAT_PLAIN,
				array( 'novalue' => 17 ),
				'InvalidArgumentException'
			),
			'mismatching output format' => array(
				SnakFormatter::FORMAT_HTML,
				array( 'novalue' => $formatter ),
				'InvalidArgumentException'
			),
		);
	}

	public function testFormatSnak() {
		$novalue = wfMessage( 'wikibase-snakview-snaktypeselector-novalue' );
		$somevalue = wfMessage( 'wikibase-snakview-snaktypeselector-somevalue' );
		$value = wfMessage( 'wikibase-snakview-snaktypeselector-value' );

		$formatter = new DispatchingSnakFormatter( SnakFormatter::FORMAT_PLAIN, array(
			'novalue' => new MessageSnakFormatter( 'novalue', $novalue, SnakFormatter::FORMAT_PLAIN ),
			'somevalue' => new MessageSnakFormatter( 'somevalue', $somevalue, SnakFormatter::FORMAT_PLAIN ),
			'value' => new MessageSnakFormatter( 'value', $value, SnakFormatter::FORMAT_PLAIN ),
		) );

		$novalueSnak = new PropertyNoValueSnak( new PropertyId( 'P23' ) );
		$somevalueSnak = new PropertySomeValueSnak( new PropertyId( 'P23' ) );
		$valueSnak = new PropertyValueSnak( new PropertyId( 'P23' ), new StringValue( 'test' ) );

		$this->assertEquals( $novalue->text(), $formatter->formatSnak( $novalueSnak ) );
		$this->assertEquals( $somevalue->text(), $formatter->formatSnak( $somevalueSnak ) );
		$this->assertEquals( $value->text(), $formatter->formatSnak( $valueSnak ) );
	}


	public function testGetSnakTypes() {
		$novalue = wfMessage( 'wikibase-snakview-snaktypeselector-novalue' );
		$somevalue = wfMessage( 'wikibase-snakview-snaktypeselector-somevalue' );
		$value = wfMessage( 'wikibase-snakview-snaktypeselector-value' );

		$formatters = array(
			'novalue' => new MessageSnakFormatter( 'novalue', $novalue, SnakFormatter::FORMAT_PLAIN ),
			'somevalue' => new MessageSnakFormatter( 'somevalue', $somevalue, SnakFormatter::FORMAT_PLAIN ),
			'value' => new MessageSnakFormatter( 'value', $value, SnakFormatter::FORMAT_PLAIN ),
		);

		$formatter = new DispatchingSnakFormatter( SnakFormatter::FORMAT_PLAIN, $formatters );

		$this->assertEquals( array_keys( $formatters ), $formatter->getSnakTypes() );

		foreach ( $formatters as $type => $expected ) {
			$actual = $formatter->getFormatter( $type );
			$this->assertSame( $formatters[$type], $actual );
		}
	}

	public function testCanFormatSnak() {
		$novalue = wfMessage( 'wikibase-snakview-snaktypeselector-novalue' );

		$formatters = array(
			'novalue' => new MessageSnakFormatter( 'novalue', $novalue, SnakFormatter::FORMAT_PLAIN ),
		);

		$formatter = new DispatchingSnakFormatter( SnakFormatter::FORMAT_PLAIN, $formatters );

		$snak = new PropertyNoValueSnak( new PropertyId( "P23" ) );
		$this->assertTrue( $formatter->canFormatSnak( $snak ), $snak->getType() );

		$snak = new PropertySomeValueSnak( new PropertyId( "P23" ) );
		$this->assertFalse( $formatter->canFormatSnak( $snak ), $snak->getType() );
	}

	public function testGetFormat() {
		$formatter = new DispatchingSnakFormatter( 'test', array() );
		$this->assertEquals( 'test', $formatter->getFormat() );
	}

}
