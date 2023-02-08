<?php

namespace Wikibase\Lib\Tests\Formatters;

use DataValues\DataValue;
use DataValues\StringValue;
use InvalidArgumentException;
use MediaWiki\MediaWikiServices;
use MediaWikiTestCaseTrait;
use ValueFormatters\FormatterOptions;
use ValueFormatters\FormattingException;
use ValueFormatters\StringFormatter;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\EntityId\PlainEntityIdFormatter;
use Wikibase\Lib\Formatters\DispatchingValueFormatter;
use Wikibase\Lib\Formatters\EntityIdValueFormatter;
use Wikibase\Lib\Formatters\FormatterLabelDescriptionLookupFactory;
use Wikibase\Lib\Formatters\OutputFormatValueFormatterFactory;
use Wikibase\Lib\Formatters\SnakFormatter;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\TermLanguageFallbackChain;

/**
 * @covers \Wikibase\Lib\Formatters\OutputFormatValueFormatterFactory
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class OutputFormatValueFormatterFactoryTest extends \PHPUnit\Framework\TestCase {
	use MediaWikiTestCaseTrait;

	/**
	 * @dataProvider constructorErrorsProvider
	 */
	public function testConstructorErrors( $builder, $error ) {
		$language = MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'en' );
		$this->expectException( $error );
		new OutputFormatValueFormatterFactory( $builder, $language, new LanguageFallbackChainFactory() );
	}

	public function constructorErrorsProvider() {
		$stringFormatter = new StringFormatter( new FormatterOptions() );

		return [
			'keys must be strings' => [
				[ 17 => $stringFormatter ],
				InvalidArgumentException::class,
			],
			'builder must be callable' => [
				[ 'foo' => 17 ],
				InvalidArgumentException::class,
			],
		];
	}

	private function newOutputFormatValueFormatterFactory() {
		$factoryCallbacks = [
			'VT:string' => function( $format, FormatterOptions $options ) {
				return new StringFormatter();
			},
			'PT:url' => function( $format, FormatterOptions $options ) {
				return new StringFormatter();
			},
		];

		return new OutputFormatValueFormatterFactory(
			$factoryCallbacks,
			MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'en' ),
			new LanguageFallbackChainFactory()
		);
	}

	/**
	 * @dataProvider provideGetValueFormatter
	 */
	public function testGetValueFormatter( $format, DataValue $value, $datatype, $expectedPattern ) {
		$factory = $this->newOutputFormatValueFormatterFactory();
		$formatter = $factory->getValueFormatter( $format, new FormatterOptions() );

		$this->assertInstanceOf( DispatchingValueFormatter::class, $formatter );

		// assert that the formatter we got conforms to the expected behavior
		$actual = $formatter->formatValue( $value, $datatype );
		$this->assertMatchesRegularExpression( $expectedPattern, $actual );
	}

	public function provideGetValueFormatter() {
		return [
			'plain string' => [
				SnakFormatter::FORMAT_PLAIN,
				new StringValue( '{foo&bar}' ),
				null,
				'/^{foo&bar}$/',
			],
			'wikitext url' => [
				SnakFormatter::FORMAT_WIKI,
				new StringValue( 'http://acme.com/?foo&bar' ),
				'url',
				'!^http://acme.com/\?foo&bar$!',
			],
		];
	}

	/**
	 * @dataProvider provideApplyLanguageDefaults
	 */
	public function testApplyLanguageDefaults( FormatterOptions $options, $expectedLanguage, $expectedFallback ) {
		$factory = $this->newOutputFormatValueFormatterFactory();

		$factory->applyLanguageDefaults( $options );

		if ( $expectedLanguage !== null ) {
			$lang = $options->getOption( ValueFormatter::OPT_LANG );
			$this->assertEquals( $expectedLanguage, $lang, 'OPT_LANG' );
		}

		if ( $expectedFallback !== null ) {
			/** @var TermLanguageFallbackChain $languageFallback */
			$languageFallback = $options->getOption( FormatterLabelDescriptionLookupFactory::OPT_LANGUAGE_FALLBACK_CHAIN );
			$languages = $languageFallback->getFallbackChain();
			$lang = $languages[0]->getLanguageCode();

			$this->assertEquals( $expectedFallback, $lang, 'OPT_LANGUAGE_FALLBACK_CHAIN' );
		}
	}

	public function provideApplyLanguageDefaults() {
		$languageFallbackFactory = new LanguageFallbackChainFactory();
		$lang = MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'fr' );
		$languageFallback = $languageFallbackFactory->newFromLanguage( $lang );

		return [
			'empty' => [
				new FormatterOptions( [] ),
				'en', // determined in OutputFormatValueFormatterFactoryTest::newBuilder()
				'en',  // derived from language code
			],
			'language code set' => [
				new FormatterOptions( [ ValueFormatter::OPT_LANG => 'de' ] ),
				'de', // as given
				'de',  // derived from language code
			],
			'language fallback set' => [
				new FormatterOptions( [
					FormatterLabelDescriptionLookupFactory::OPT_LANGUAGE_FALLBACK_CHAIN => $languageFallback,
				] ),
				'en', // default code is taken from the constructor, not the fallback chain
				'fr',  // as given
			],
			'language code and fallback set' => [
				new FormatterOptions( [
					ValueFormatter::OPT_LANG => 'de',
					FormatterLabelDescriptionLookupFactory::OPT_LANGUAGE_FALLBACK_CHAIN => $languageFallback,
				] ),
				'de', // as given
				'fr',  // as given
			],
		];
	}

	public function testSetFormatterFactoryCallback() {
		$factory = $this->newOutputFormatValueFormatterFactory();
		$factory->setFormatterFactoryCallback(
			'VT:wikibase-entityid',
			function () {
				return new EntityIdValueFormatter( new PlainEntityIdFormatter() );
			}
		);

		$factory->setFormatterFactoryCallback(
			'VT:string',
			null
		);

		$formatter = $factory->getValueFormatter( SnakFormatter::FORMAT_PLAIN, new FormatterOptions() );

		$this->assertEquals(
			'Q5',
			$formatter->formatValue( new EntityIdValue( new ItemId( "Q5" ) ) ),
			'Extra formatter'
		);

		// formatter for 'VT:string' should have been removed
		$this->expectException( FormattingException::class );
		$formatter->format( new StringValue( 'boo!' ) ); // expecting a FormattingException
	}

}
