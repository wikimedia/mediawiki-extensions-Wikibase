<?php

namespace Wikibase\Lib\Tests\Formatters;

use DataValues\StringValue;
use DataValues\UnDeserializableValue;
use InvalidArgumentException;
use MediaWikiCoversValidator;
use MediaWikiTestCaseTrait;
use OutOfBoundsException;
use ValueFormatters\Exceptions\MismatchingDataValueTypeException;
use ValueFormatters\FormatterOptions;
use ValueFormatters\FormattingException;
use ValueFormatters\StringFormatter;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Lib\DataType;
use Wikibase\Lib\DataTypeFactory;
use Wikibase\Lib\DataValue\UnmappedEntityIdValue;
use Wikibase\Lib\Formatters\DispatchingValueFormatter;
use Wikibase\Lib\Formatters\PropertyValueSnakFormatter;
use Wikibase\Lib\Formatters\SnakFormatter;
use Wikibase\Lib\Formatters\UnDeserializableValueFormatter;
use Wikibase\Lib\Formatters\UnmappedEntityIdValueFormatter;

/**
 * @covers \Wikibase\Lib\Formatters\PropertyValueSnakFormatter
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class PropertyValueSnakFormatterTest extends \PHPUnit\Framework\TestCase {
	use MediaWikiCoversValidator;
	use MediaWikiTestCaseTrait;

	/**
	 * @dataProvider constructorErrorsProvider
	 */
	public function testConstructorErrors( $format, $error ) {
		$this->expectException( $error );

		$this->getDummyPropertyValueSnakFormatter( $format );
	}

	public function constructorErrorsProvider() {
		return [
			'format must be a string' => [
				17,
				InvalidArgumentException::class,
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
				new PropertyDataTypeLookupException( new NumericPropertyId( 'P666' ) ) );
		}

		$typeLookup = $this->createMock( PropertyDataTypeLookup::class );
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

		$typeFactory = $this->createMock( DataTypeFactory::class );

		$typeFactory->method( 'getType' )
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
			$this->expectException( $expectedException );
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

		$this->assertMatchesRegularExpression( $expected, $actual );
	}

	/**
	 * @param string $value
	 *
	 * @return ValueFormatter
	 */
	private function getMockFormatter( $value ) {
		$formatter = $this->createMock( ValueFormatter::class );
		$formatter->method( 'format' )
			->willReturn( $value );

		return $formatter;
	}

	public function formatSnakProvider() {
		$formatters = [
			'VT:bad' => new UnDeserializableValueFormatter( new FormatterOptions() ),
			'VT:string' => $this->getMockFormatter( 'VT:string' ),
			'PT:commonsMedia' => $this->getMockFormatter( 'PT:commonsMedia' ),
		];

		$dispatchingFormatter = new DispatchingValueFormatter( $formatters );

		return [
			'match PT' => [
				new PropertyValueSnak( 17, new StringValue( 'Foo.jpg' ) ),
				'commonsMedia',
				'string',
				SnakFormatter::FORMAT_PLAIN,
				$dispatchingFormatter,
				'/^PT:commonsMedia$/',
			],

			'match VT' => [
				new PropertyValueSnak( 33, new StringValue( 'something' ) ),
				'someStuff',
				'string',
				SnakFormatter::FORMAT_WIKI,
				$dispatchingFormatter,
				'/^VT:string$/',
			],

			'use plain value formatter' => [
				new PropertyValueSnak( 33, new StringValue( 'something' ) ),
				'url',
				'string',
				SnakFormatter::FORMAT_WIKI,
				new StringFormatter(),
				'/^something$/',
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
				MismatchingDataValueTypeException::class,
			],

			'VT mismatching PT, fail' => [
				new PropertyValueSnak( 7, new StringValue( 'dummy' ) ),
				'url',
				'iri', // url expects an iri, but will get a string
				SnakFormatter::FORMAT_WIKI,
				$dispatchingFormatter,
				null,
				MismatchingDataValueTypeException::class,
			],

			'property not found, fail' => [
				new PropertyValueSnak( 7, new StringValue( 'dummy' ) ),
				'', // triggers an exception from the mock DataTypeFactory
				'', // should not be used
				SnakFormatter::FORMAT_HTML,
				$dispatchingFormatter,
				null,
				PropertyDataTypeLookupException::class,
			],

			'data type not found, fail' => [
				new PropertyValueSnak( 7, new StringValue( 'dummy' ) ),
				'url',
				'', // triggers an exception from the mock DataTypeFactory
				SnakFormatter::FORMAT_HTML,
				$dispatchingFormatter,
				null,
				FormattingException::class,
			],
		];
	}

	private function getDummyPropertyValueSnakFormatter( $format = 'test' ) {
		$typeLookup = $this->createMock( PropertyDataTypeLookup::class );
		$typeLookup->expects( $this->never() )->method( 'getDataTypeIdForProperty' );

		$typeFactory = $this->createMock( DataTypeFactory::class );

		$typeFactory->expects( $this->never() )->method( 'getType' );

		$valueFormatter = new DispatchingValueFormatter( [] );

		$formatter = new PropertyValueSnakFormatter( $format, $valueFormatter, $typeLookup, $typeFactory );
		return $formatter;
	}

	public function testGetFormat() {
		$formatter = $this->getDummyPropertyValueSnakFormatter();
		$this->assertEquals( 'test', $formatter->getFormat() );
	}

	public function testGivenUnmappedEntityIdValue_doesNotBotherCheckingDataValueTypeMatch() {
		$valueFormatter = new DispatchingValueFormatter( [
			'VT:wikibase-unmapped-entityid' => new UnmappedEntityIdValueFormatter(),
			'VT:string' => $this->getMockFormatter( 'VT:string' ),
		] );

		$typeFactory = $this->createMock( DataTypeFactory::class );

		$typeFactory->expects( $this->never() )
			->method( 'getType' );

		$formatter = new PropertyValueSnakFormatter(
			SnakFormatter::FORMAT_PLAIN,
			$valueFormatter,
			$this->getMockDataTypeLookup( 'wikibase-weird-entity-type' ),
			$typeFactory
		);

		$this->assertEquals(
			'X123',
			$formatter->formatSnak( new PropertyValueSnak( 17, new UnmappedEntityIdValue( 'X123' ) ) )
		);
	}

}
