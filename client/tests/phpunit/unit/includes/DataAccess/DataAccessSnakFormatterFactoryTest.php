<?php

namespace Wikibase\Client\Tests\Unit\DataAccess;

use DataValues\StringValue;
use Language;
use MediaWiki\MediaWikiServices;
use ValueFormatters\FormatterOptions;
use Wikibase\Client\DataAccess\DataAccessSnakFormatterFactory;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Lib\Formatters\OutputFormatSnakFormatterFactory;
use Wikibase\Lib\Formatters\SnakFormatter;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\FallbackLabelDescriptionLookup;
use Wikibase\Lib\Store\FallbackLabelDescriptionLookupFactory;

/**
 * @covers \Wikibase\Client\DataAccess\DataAccessSnakFormatterFactory
 *
 * @note We also have integration tests for this at DataAccessSnakFormatterOutputFormatTest.
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseDataAccess
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class DataAccessSnakFormatterFactoryTest extends \PHPUnit\Framework\TestCase {

	private function getDataAccessSnakFormatterFactory( $expectedFormat ) {
		$fallbackLabelDescriptionLookupFactory = $this->createMock( FallbackLabelDescriptionLookupFactory::class );
		$fallbackLabelDescriptionLookupFactory->expects( $this->once() )
			->method( 'newLabelDescriptionLookup' )
			->willReturn( $this->createMock( FallbackLabelDescriptionLookup::class ) );

		return new DataAccessSnakFormatterFactory(
			$this->getLanguageFallbackChainFactory(),
			$this->getOutputFormatSnakFormatterFactory( $expectedFormat ),
			new InMemoryDataTypeLookup(),
			new ItemIdParser(),
			$fallbackLabelDescriptionLookupFactory,
			false
		);
	}

	/**
	 * @return LanguageFallbackChainFactory
	 */
	private function getLanguageFallbackChainFactory() {
		$realFactory = new LanguageFallbackChainFactory();

		$factory = $this->createMock( LanguageFallbackChainFactory::class );

		$factory->expects( $this->once() )
			->method( 'newFromLanguage' )
			->with( $this->isInstanceOf( Language::class ) )
			->willReturnCallback( [ $realFactory, 'newFromLanguage' ] );

		return $factory;
	}

	/**
	 * @return OutputFormatSnakFormatterFactory
	 */
	private function getOutputFormatSnakFormatterFactory( $expectedFormat ) {
		$factory = $this->createMock( OutputFormatSnakFormatterFactory::class );

		$snakFormatter = $this->createMock( SnakFormatter::class );

		$snakFormatter->method( 'formatSnak' )
			->willReturnCallback( function( PropertyValueSnak $snak ) {
				return $snak->getDataValue()->getValue();
			} );

		$snakFormatter->method( 'getFormat' )
			->willReturn( $expectedFormat );

		$factory->expects( $this->once() )
			->method( 'getSnakFormatter' )
			->with( $expectedFormat, $this->isInstanceOf( FormatterOptions::class ) )
			->willReturn( $snakFormatter );

		return $factory;
	}

	public function testNewEscapedPlainTextSnakFormatter() {
		$factory = $this->getDataAccessSnakFormatterFactory( SnakFormatter::FORMAT_PLAIN );
		$snakFormatter = $factory->newWikitextSnakFormatter(
			MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'fr' ),
			$this->createMock( UsageAccumulator::class )
		);

		$this->assertInstanceOf( SnakFormatter::class, $snakFormatter );
		$this->assertSame( SnakFormatter::FORMAT_PLAIN, $snakFormatter->getFormat() );
	}

	public function richWikitextSnakFormatterProvider() {
		$id = new NumericPropertyId( 'P1' );

		return [
			[ new PropertyValueSnak( $id, new StringValue( '' ) ), '' ],
			[ new PropertyValueSnak( $id, new StringValue( '<RAW>' ) ), '<span><RAW></span>' ],
		];
	}

	/**
	 * @dataProvider richWikitextSnakFormatterProvider
	 */
	public function testRichWikitextSnakFormatter( Snak $snak, $expected ) {
		$factory = $this->getDataAccessSnakFormatterFactory( SnakFormatter::FORMAT_WIKI );
		$snakFormatter = $factory->newWikitextSnakFormatter(
			MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'fr' ),
			$this->createMock( UsageAccumulator::class ),
			DataAccessSnakFormatterFactory::TYPE_RICH_WIKITEXT
		);

		$this->assertSame( $expected, $snakFormatter->formatSnak( $snak ) );
		$this->assertSame( SnakFormatter::FORMAT_WIKI, $snakFormatter->getFormat() );
	}

}
