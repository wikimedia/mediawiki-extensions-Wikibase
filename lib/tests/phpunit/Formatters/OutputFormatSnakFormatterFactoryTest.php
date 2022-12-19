<?php

namespace Wikibase\Lib\Tests\Formatters;

use DataValues\DataValue;
use DataValues\StringValue;
use LanguageQqx;
use MediaWiki\MediaWikiServices;
use Message;
use ValueFormatters\FormatterOptions;
use ValueFormatters\StringFormatter;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Lib\DataTypeFactory;
use Wikibase\Lib\Formatters\DispatchingSnakFormatter;
use Wikibase\Lib\Formatters\ErrorHandlingSnakFormatter;
use Wikibase\Lib\Formatters\OutputFormatSnakFormatterFactory;
use Wikibase\Lib\Formatters\OutputFormatValueFormatterFactory;
use Wikibase\Lib\Formatters\SnakFormatter;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\MessageInLanguageProvider;

/**
 * @covers \Wikibase\Lib\Formatters\OutputFormatSnakFormatterFactory
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class OutputFormatSnakFormatterFactoryTest extends \PHPUnit\Framework\TestCase {

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
			MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'en' ),
			new LanguageFallbackChainFactory()
		);

		$dataTypeLookup = $this->createMock( PropertyDataTypeLookup::class );
		$dataTypeLookup->method( 'getDataTypeIdForProperty' )
			->willReturn( $dataType );

		$messageInLanguageProvider = $this->createMock( MessageInLanguageProvider::class );
		$messageInLanguageProvider->method( 'msgInLang' )
			->willReturnCallback( function ( $key, $lang, ...$params ) {
				// ignore $lang, always use qqx
				return new Message( $key, $params, new LanguageQqx() );
			} );

		return new OutputFormatSnakFormatterFactory(
			$snakFormatterCallbacks,
			$valueFormatterFactory,
			$dataTypeLookup,
			new DataTypeFactory( [ 'string' => 'string', 'commonsMedia' => 'string' ] ),
			$messageInLanguageProvider
		);
	}

	/**
	 * @param string $format
	 *
	 * @return ValueFormatter
	 */
	public function makeMockValueFormatter( $format ) {
		$mock = $this->createMock( ValueFormatter::class );

		$mock->method( 'format' )
			->willReturnCallback(
				function( DataValue $value ) use ( $format ) {
					return strval( $value->getValue() ) . ' (' . $format . ')';
				}
			);

		return $mock;
	}

	/**
	 * @param string $format
	 *
	 * @return SnakFormatter
	 */
	public function makeMockSnakFormatter( $format ) {
		$mock = $this->createMock( SnakFormatter::class );

		$mock->method( 'formatSnak' )
			->willReturnCallback(
				function( Snak $snak ) use ( $format ) {
					$s = $snak->getType() . '/' . $snak->getPropertyId();

					if ( $snak instanceof PropertyValueSnak ) {
						$s .= '=' . strval( $snak->getDataValue()->getValue() );
					}

					return $s . ' (' . $format . ')';
				}
			);

		$mock->method( 'getFormat' )
			->willReturn( $format );

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

		$snak = new PropertyValueSnak( new NumericPropertyId( 'P5' ), $value );
		$this->assertEquals( $expected, $formatter->formatSnak( $snak ) );
	}

	public function getSnakFormatterProvider_notValueSnak(): iterable {
		yield 'unknown value' => [
			PropertySomeValueSnak::class,
			'wikibase-snakview-snaktypeselector-somevalue',
		];

		yield 'no value' => [
			PropertyNoValueSnak::class,
			'wikibase-snakview-snaktypeselector-novalue',
		];
	}

	/** @dataProvider getSnakFormatterProvider_notValueSnak */
	public function testGetSnakFormatter_notValueSnak( string $snakClass, string $expected ) {
		$factory = $this->newOutputFormatSnakFormatterFactory( 'string' );
		$formatter = $factory->getSnakFormatter( SnakFormatter::FORMAT_PLAIN, new FormatterOptions() );

		$snak = new $snakClass( new NumericPropertyId( 'P5' ) );
		$this->assertStringContainsString( $expected, $formatter->formatSnak( $snak ) );
	}

	public function getSnakFormatterProvider_options() {
		return [
			'default' => [
				[],
				ErrorHandlingSnakFormatter::class,
			],
			'OPT_ON_ERROR => ON_ERROR_WARN' => [
				[ SnakFormatter::OPT_ON_ERROR => SnakFormatter::ON_ERROR_WARN ],
				ErrorHandlingSnakFormatter::class,
			],
			'OPT_ON_ERROR => ON_ERROR_FAIL' => [
				[ SnakFormatter::OPT_ON_ERROR => SnakFormatter::ON_ERROR_FAIL ],
				DispatchingSnakFormatter::class,
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
			MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'de' ),
			new LanguageFallbackChainFactory()
		);

		$factory = new OutputFormatSnakFormatterFactory(
			[],
			$valueFormatterFactory,
			new InMemoryDataTypeLookup(),
			new DataTypeFactory( [] ),
			$this->createMock( MessageInLanguageProvider::class )
		);
		$factory->getSnakFormatter( SnakFormatter::FORMAT_PLAIN, new FormatterOptions() )
			->formatSnak( new PropertyValueSnak(
				new NumericPropertyId( 'P1' ),
				new StringValue( 'S' )
			) );
	}

}
