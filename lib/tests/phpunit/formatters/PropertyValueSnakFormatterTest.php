<?php
namespace Wikibase\Lib\Test;

use DataValues\StringValue;
use ValueFormatters\FormatterOptions;
use ValueFormatters\StringFormatter;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\DispatchingSnakFormatter;
use Wikibase\Lib\MessageSnakFormatter;
use Wikibase\Lib\PropertyValueSnakFormatter;
use Wikibase\Lib\SnakFormatterFactory;
use Wikibase\PropertyNoValueSnak;
use Wikibase\PropertySomeValueSnak;
use Wikibase\PropertyValueSnak;

/**
 * @covers Wikibase\Lib\PropertyValueSnakFormatter
 *
 * @since 0.5
 *
 * @ingroup WikibaseLibTest
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class PropertyValueSnakFormatterTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider constructorErrorsProvider
	 *
	 * @param $format
	 * @param $formatters
	 * @param $error
	 */
	public function testConstructorErrors( $format, $formatters, $error ) {
		$this->setExpectedException( $error );

		$typeLookup = $this->getMock( 'Wikibase\Lib\PropertyDataTypeLookup' );
		$typeLookup->expects( $this->never() )->method( 'getDataTypeIdForProperty' );;

		new PropertyValueSnakFormatter( $format, $formatters, $typeLookup );
	}

	public function constructorErrorsProvider() {
		$stringFormatter = new StringFormatter( new FormatterOptions() );

		return array(
			'format must be a string' => array(
				17,
				array(),
				'InvalidArgumentException'
			),
			'keys must be strings' => array(
				SnakFormatterFactory::FORMAT_PLAIN,
				array( 17 => $stringFormatter ),
				'InvalidArgumentException'
			),
			'keys must have prefix' => array(
				SnakFormatterFactory::FORMAT_PLAIN,
				array( 'foo' => $stringFormatter ),
				'InvalidArgumentException'
			),
			'formatters must be instances of ValueFormatter' => array(
				SnakFormatterFactory::FORMAT_PLAIN,
				array( 'novalue' => 17 ),
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
			SnakFormatterFactory::FORMAT_PLAIN,
			$formatters,
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

		$formatter = new PropertyValueSnakFormatter( 'test', array(), $typeLookup );
		$this->assertEquals( 'test', $formatter->getFormat() );
	}

}
