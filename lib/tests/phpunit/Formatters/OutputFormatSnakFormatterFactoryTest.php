<?php

namespace Wikibase\Lib\Tests\Formatters;

use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\Lib\DataTypeFactory;
use DataValues\DataValue;
use DataValues\StringValue;
use Language;
use ValueFormatters\FormatterOptions;
use ValueFormatters\StringFormatter;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\DispatchingSnakFormatter;
use Wikibase\Lib\Formatters\ErrorHandlingSnakFormatter;
use Wikibase\Lib\OutputFormatSnakFormatterFactory;
use Wikibase\Lib\OutputFormatValueFormatterFactory;
use Wikibase\Lib\SnakFormatter;

/**
 * @covers Wikibase\Lib\OutputFormatSnakFormatterFactory
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class OutputFormatSnakFormatterFactoryTest extends \PHPUnit_Framework_TestCase {

	private function newOutputFormatSnakFormatterFactory( $dataType = 'string' ) {
		$snakFormatterCallbacks = [
			'PT:commonsMedia' => function( $format, FormatterOptions $options ) {
				return $this->makeMockSnakFormatter( $format );
			},
		];

		$valueFormatterCallbacks = [
			'VT:string' => function( $format, FormatterOptions $options ) {
				return $this->makeMockValueFormatter( $format );
			},
		];
		$valueFormatterFactory = new OutputFormatValueFormatterFactory(
			$valueFormatterCallbacks,
			Language::factory( 'en' ),
			new LanguageFallbackChainFactory()
		);

		$dataTypeLookup = $this->getMock( PropertyDataTypeLookup::class );
		$dataTypeLookup->expects( $this->any() )
			->method( 'getDataTypeIdForProperty' )
			->will( $this->returnValue( $dataType ) );

		return new OutputFormatSnakFormatterFactory(
			$snakFormatterCallbacks,
			$valueFormatterFactory,
			$dataTypeLookup,
			new DataTypeFactory( [ 'string' => 'string', 'commonsMedia' => 'string' ] )
		);
	}

	/**
	 * @param string $format
	 *
	 * @return ValueFormatter
	 */
	public function makeMockValueFormatter( $format ) {
		$mock = $this->getMock( ValueFormatter::class );

		$mock->expects( $this->any() )
			->method( 'format' )
			->will( $this->returnCallback(
				function( DataValue $value ) use ( $format ) {
					return strval( $value->getValue() ) . ' (' . $format . ')';
				}
			) );

		return $mock;
	}

	/**
	 * @param string $format
	 *
	 * @return SnakFormatter
	 */
	public function makeMockSnakFormatter( $format ) {
		$mock = $this->getMock( SnakFormatter::class );

		$mock->expects( $this->any() )
			->method( 'formatSnak' )
			->will( $this->returnCallback(
				function( Snak $snak ) use ( $format ) {
					$s = $snak->getType() . '/' . $snak->getPropertyId();

					if ( $snak instanceof PropertyValueSnak ) {
						$s .= '=' . strval( $snak->getDataValue()->getValue() );
					}

					return $s . ' (' . $format . ')';
				}
			) );

		$mock->expects( $this->any() )
			->method( 'getFormat' )
			->will( $this->returnValue( $format ) );

		return $mock;
	}

	public function getSnakFormatterProvider() {
		return [
			'plain value' => [
				SnakFormatter::FORMAT_PLAIN,
				'string',
				new StringValue( 'foo' ),
				'foo (text/plain)',
			],
			'html value' => [
				SnakFormatter::FORMAT_HTML,
				'string',
				new StringValue( 'foo' ),
				'foo (text/html)',
			],
			'plain snak' => [
				SnakFormatter::FORMAT_PLAIN,
				'commonsMedia', // the mock has a SnakFormatter for commonsMedia
				new StringValue( 'foo.jpg' ),
				'value/P5=foo.jpg (text/plain)',
			],
			'html snak' => [
				SnakFormatter::FORMAT_HTML,
				'commonsMedia', // the mock has a SnakFormatter for commonsMedia
				new StringValue( 'foo.jpg' ),
				'value/P5=foo.jpg (text/html)',
			],
		];
	}

	/**
	 * @dataProvider getSnakFormatterProvider
	 */
	public function testGetSnakFormatter( $format, $dataType, DataValue $value, $expected ) {
		$factory = $this->newOutputFormatSnakFormatterFactory( $dataType );
		$formatter = $factory->getSnakFormatter( $format, new FormatterOptions() );

		$this->assertInstanceOf( SnakFormatter::class, $formatter );
		$this->assertEquals( $format, $formatter->getFormat() );

		$snak = new PropertyValueSnak( new PropertyId( 'P5' ), $value );
		$this->assertEquals( $expected, $formatter->formatSnak( $snak ) );
	}

	public function getSnakFormatterProvider_options() {
		return [
			'default' => [
				[],
				ErrorHandlingSnakFormatter::class
			],
			'OPT_ON_ERROR => ON_ERROR_WARN' => [
				[ SnakFormatter::OPT_ON_ERROR => SnakFormatter::ON_ERROR_WARN ],
				ErrorHandlingSnakFormatter::class
			],
			'OPT_ON_ERROR => ON_ERROR_FAIL' => [
				[ SnakFormatter::OPT_ON_ERROR => SnakFormatter::ON_ERROR_FAIL ],
				DispatchingSnakFormatter::class
			],
		];
	}

	/**
	 * @dataProvider getSnakFormatterProvider_options
	 */
	public function testGetSnakFormatter_options( array $options, $expectedType ) {
		$factory = $this->newOutputFormatSnakFormatterFactory();
		$formatter = $factory->getSnakFormatter(
			SnakFormatter::FORMAT_WIKI,
			new FormatterOptions( $options )
		);

		$this->assertInstanceOf( $expectedType, $formatter );
	}

	public function testGetSnakFormatter_languageOption() {
		$callbacks = [
			'VT:string' => function( $format, FormatterOptions $options ) {
				$this->assertSame( 'de', $options->getOption( ValueFormatter::OPT_LANG ) );
				return new StringFormatter( $options );
			},
		];
		$valueFormatterFactory = new OutputFormatValueFormatterFactory(
			$callbacks,
			Language::factory( 'de' ),
			new LanguageFallbackChainFactory()
		);

		$factory = new OutputFormatSnakFormatterFactory(
			[],
			$valueFormatterFactory,
			new InMemoryDataTypeLookup(),
			new DataTypeFactory( [] )
		);
		$factory->getSnakFormatter( SnakFormatter::FORMAT_PLAIN, new FormatterOptions() );
	}

}
