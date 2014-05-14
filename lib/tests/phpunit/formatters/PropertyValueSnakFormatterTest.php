<?php

namespace Wikibase\Lib\Test;

use DataTypes\DataType;
use DataTypes\DataTypeFactory;
use DataValues\StringValue;
use DataValues\UnDeserializableValue;
use ValueFormatters\FormatterOptions;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Lib\DispatchingValueFormatter;
use Wikibase\Lib\PropertyDataTypeLookup;
use Wikibase\Lib\PropertyNotFoundException;
use Wikibase\Lib\PropertyValueSnakFormatter;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\UnDeserializableValueFormatter;

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

		$this->getDummyPropertyValueSnakFormatter( $format );
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
	 * @param string $dataType
	 *
	 * @return PropertyDataTypeLookup
	 */
	private function getMockDataTypeLookup( $dataType ) {
		if ( $dataType !== '' ) {
			$getDataTypeIdForPropertyResult = $this->returnValue( $dataType );
		} else {
			$getDataTypeIdForPropertyResult = $this->throwException(
				new PropertyNotFoundException( new PropertyId( 'P666' ) ) );
		}

		$typeLookup = $this->getMock( 'Wikibase\Lib\PropertyDataTypeLookup' );
		$typeLookup->expects( $this->atLeastOnce() )
			->method( 'getDataTypeIdForProperty' )
			->will( $getDataTypeIdForPropertyResult );

		return $typeLookup;
	}

	/**
	 * @param string $dataType
	 * @param string $valueType
	 *
	 * @return DataTypeFactory
	 */
	private function getMockDataTypeFactory( $dataType, $valueType ) {
		$typeFactory = $this->getMock( 'DataTypes\DataTypeFactory' );
		$typeFactory->expects( $this->any() )
			->method( 'getType' )
			->will( $this->returnValue( new DataType( $dataType, $valueType, array() ) ) );

		return $typeFactory;
	}

	/**
	 * @dataProvider formatSnakProvider
	 * @covers PropertyValueSnakFormatter::formatSnak()
	 */
	public function testFormatSnak( $snak, $dataType, $valueType, $targetFormat, $formatters, $expected ) {
		$typeLookup = $this->getMockDataTypeLookup( $dataType );
		$typeFactory = $this->getMockDataTypeFactory( $dataType, $valueType );

		$formatter = new PropertyValueSnakFormatter(
			$targetFormat,
			new DispatchingValueFormatter( $formatters ),
			$typeLookup,
			$typeFactory
		);

		// NOTE: we want to suppress warnings here, so we can test that mismatching DataValues
		// are still processed correctly after causing a warning.
		wfSuppressWarnings();
		$actual = $formatter->formatSnak( $snak );
		wfRestoreWarnings();

		$this->assertRegExp( $expected, $actual );
	}

	private function getMockFormatter( $value ) {
		$formatter = $this->getMock( 'ValueFormatters\ValueFormatter' );
		$formatter->expects( $this->any() )
			->method( 'format' )
			->will( $this->returnValue( $value ) );

		return $formatter;
	}

	public function formatSnakProvider() {
		$formatters = array(
			'VT:bad' => new UnDeserializableValueFormatter( new FormatterOptions() ),
			'VT:string' => $this->getMockFormatter( 'VT:string' ),
			'PT:commonsMedia' => $this->getMockFormatter( 'PT:commonsMedia' )
		);

		return array(
			'match PT' => array(
				new PropertyValueSnak( 17, new StringValue( 'Foo.jpg' ) ),
				'commonsMedia',
				'string',
				SnakFormatter::FORMAT_PLAIN,
				$formatters,
				'/^PT:commonsMedia$/'
			),

			'match VT' => array(
				new PropertyValueSnak( 33, new StringValue( 'something' ) ),
				'someStuff',
				'string',
				SnakFormatter::FORMAT_WIKI,
				$formatters,
				'/^VT:string$/'
			),

			//NOTE: will fail unless warnings are suppressed
			'UnDeserializableValue' => array(
				new PropertyValueSnak( 7,
					new UnDeserializableValue( 'cookie', 'globecoordinate', 'cannot understand!' )
				),
				'globe-coordinate',
				'bad',
				SnakFormatter::FORMAT_HTML,
				$formatters,
				// message key: wikibase-undeserializable-value
				'/value is invalid/'
			),

			//NOTE: will fail unless warnings are suppressed
			'VT mismatching PT' => array(
				new PropertyValueSnak( 7, new StringValue( 'dummy' ) ),
				'url',
				'iri', // url expects an iri, but will get a string
				SnakFormatter::FORMAT_WIKI,
				$formatters,
				// message key: wikibase-snakformatter-valuetype-mismatch
				'@^VT:string <span class="error">\(.*does not match.*\)</span>$@'
			),

			//NOTE: will fail unless warnings are suppressed
			'property not found' => array(
				new PropertyValueSnak( 7, new StringValue( 'dummy' ) ),
				'', // triggers an exception from the mock PropertyDataTypeLookup
				'xxx', // should not be used
				SnakFormatter::FORMAT_HTML,
				$formatters,
				// message key: wikibase-snakformatter-property-not-found
				'@^VT:string <span class="error">\(.*not found.*\)</span>$@'
			),
		);
	}

	private function getDummyPropertyValueSnakFormatter( $format = 'test' ) {
		$typeLookup = $this->getMock( 'Wikibase\Lib\PropertyDataTypeLookup' );
		$typeLookup->expects( $this->never() )->method( 'getDataTypeIdForProperty' );

		$typeFactory = $this->getMock( 'DataTypes\DataTypeFactory' );
		$typeFactory->expects( $this->never() )->method( 'getType' );

		$valueFormatter = new DispatchingValueFormatter( array() );

		$formatter = new PropertyValueSnakFormatter( $format, $valueFormatter, $typeLookup, $typeFactory );
		return $formatter;
	}

	/**
	 * @covers PropertyValueSnakFormatter::getFormat()
	 */
	public function testGetFormat() {
		$formatter = $this->getDummyPropertyValueSnakFormatter();
		$this->assertEquals( 'test', $formatter->getFormat() );
	}

	/**
	 * @covers MessageSnakFormatter::canFormatSnak()
	 */
	public function testCanFormatSnak() {
		$formatter = $this->getDummyPropertyValueSnakFormatter();

		$snak = new PropertyValueSnak( new PropertyId( "P23" ), new StringValue( 'test' ) );
		$this->assertTrue( $formatter->canFormatSnak( $snak ), $snak->getType() );

		$snak = new PropertySomeValueSnak( new PropertyId( "P24" ) );
		$this->assertFalse( $formatter->canFormatSnak( $snak ), $snak->getType() );
	}

}
