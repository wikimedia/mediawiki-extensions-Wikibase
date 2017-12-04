<?php

namespace Wikibase\Lib\Tests\Formatters;

use Wikibase\Lib\DataType;
use Wikibase\Lib\DataTypeFactory;
use DataValues\StringValue;
use DataValues\UnDeserializableValue;
use InvalidArgumentException;
use OutOfBoundsException;
use ValueFormatters\Exceptions\MismatchingDataValueTypeException;
use ValueFormatters\FormatterOptions;
use ValueFormatters\FormattingException;
use ValueFormatters\StringFormatter;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Lib\Formatters\DispatchingValueFormatter;
use Wikibase\Lib\PropertyValueSnakFormatter;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\UnDeserializableValueFormatter;

/**
 * @covers Wikibase\Lib\PropertyValueSnakFormatter
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class PropertyValueSnakFormatterTest extends \MediaWikiTestCase {

	/**
	 * @dataProvider constructorErrorsProvider
	 */
	public function testConstructorErrors( $format, $error ) {
		$this->setExpectedException( $error );

		$this->getDummyPropertyValueSnakFormatter( $format );
	}

	public function constructorErrorsProvider() {
		return [
			'format must be a string' => [
				17,
				InvalidArgumentException::class
			],
		];
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
				new PropertyDataTypeLookupException( new PropertyId( 'P666' ) ) );
		}

		$typeLookup = $this->getMock( PropertyDataTypeLookup::class );
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
		if ( $valueType !== '' ) {
			$getValueTypeIdForPropertyResult = $this->returnValue( new DataType( $dataType, $valueType ) );
		} else {
			$getValueTypeIdForPropertyResult = $this->throwException(
				new OutOfBoundsException( 'unknown datatype ' . $dataType ) );
		}

		$typeFactory = $this->getMockBuilder( DataTypeFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$typeFactory->expects( $this->any() )
			->method( 'getType' )
			->will( $getValueTypeIdForPropertyResult );

		return $typeFactory;
	}

	/**
	 * @dataProvider formatSnakProvider
	 */
	public function testFormatSnak(
		$snak, $dataType, $valueType, $targetFormat, ValueFormatter $formatter,
		$expected, $expectedException = null
	) {
		if ( $expectedException !== null ) {
			$this->setExpectedException( $expectedException );
		}

		$typeLookup = $this->getMockDataTypeLookup( $dataType );
		$typeFactory = $this->getMockDataTypeFactory( $dataType, $valueType );

		$formatter = new PropertyValueSnakFormatter(
			$targetFormat,
			$formatter,
			$typeLookup,
			$typeFactory
		);

		$actual = $formatter->formatSnak( $snak );

		$this->assertRegExp( $expected, $actual );
	}

	/**
	 * @param string $value
	 *
	 * @return ValueFormatter
	 */
	private function getMockFormatter( $value ) {
		$formatter = $this->getMock( ValueFormatter::class );
		$formatter->expects( $this->any() )
			->method( 'format' )
			->will( $this->returnValue( $value ) );

		return $formatter;
	}

	public function formatSnakProvider() {
		$formatters = [
			'VT:bad' => new UnDeserializableValueFormatter( new FormatterOptions() ),
			'VT:string' => $this->getMockFormatter( 'VT:string' ),
			'PT:commonsMedia' => $this->getMockFormatter( 'PT:commonsMedia' )
		];

		$dispatchingFormatter = new DispatchingValueFormatter( $formatters );

		return [
			'match PT' => [
				new PropertyValueSnak( 17, new StringValue( 'Foo.jpg' ) ),
				'commonsMedia',
				'string',
				SnakFormatter::FORMAT_PLAIN,
				$dispatchingFormatter,
				'/^PT:commonsMedia$/'
			],

			'match VT' => [
				new PropertyValueSnak( 33, new StringValue( 'something' ) ),
				'someStuff',
				'string',
				SnakFormatter::FORMAT_WIKI,
				$dispatchingFormatter,
				'/^VT:string$/'
			],

			'use plain value formatter' => [
				new PropertyValueSnak( 33, new StringValue( 'something' ) ),
				'url',
				'string',
				SnakFormatter::FORMAT_WIKI,
				new StringFormatter(),
				'/^something$/'
			],

			'UnDeserializableValue, fail' => [
				new PropertyValueSnak( 7,
					new UnDeserializableValue( 'cookie', 'globecoordinate', 'cannot understand!' )
				),
				'globe-coordinate',
				'globecoordinate',
				SnakFormatter::FORMAT_HTML,
				$dispatchingFormatter,
				null,
				MismatchingDataValueTypeException::class
			],

			'VT mismatching PT, fail' => [
				new PropertyValueSnak( 7, new StringValue( 'dummy' ) ),
				'url',
				'iri', // url expects an iri, but will get a string
				SnakFormatter::FORMAT_WIKI,
				$dispatchingFormatter,
				null,
				MismatchingDataValueTypeException::class
			],

			'property not found, fail' => [
				new PropertyValueSnak( 7, new StringValue( 'dummy' ) ),
				'', // triggers an exception from the mock DataTypeFactory
				'', // should not be used
				SnakFormatter::FORMAT_HTML,
				$dispatchingFormatter,
				null,
				PropertyDataTypeLookupException::class
			],

			'data type not found, fail' => [
				new PropertyValueSnak( 7, new StringValue( 'dummy' ) ),
				'url',
				'', // triggers an exception from the mock DataTypeFactory
				SnakFormatter::FORMAT_HTML,
				$dispatchingFormatter,
				null,
				FormattingException::class
			],
		];
	}

	private function getDummyPropertyValueSnakFormatter( $format = 'test' ) {
		$typeLookup = $this->getMock( PropertyDataTypeLookup::class );
		$typeLookup->expects( $this->never() )->method( 'getDataTypeIdForProperty' );

		$typeFactory = $this->getMockBuilder( DataTypeFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$typeFactory->expects( $this->never() )->method( 'getType' );

		$valueFormatter = new DispatchingValueFormatter( [] );

		$formatter = new PropertyValueSnakFormatter( $format, $valueFormatter, $typeLookup, $typeFactory );
		return $formatter;
	}

	public function testGetFormat() {
		$formatter = $this->getDummyPropertyValueSnakFormatter();
		$this->assertEquals( 'test', $formatter->getFormat() );
	}

}
