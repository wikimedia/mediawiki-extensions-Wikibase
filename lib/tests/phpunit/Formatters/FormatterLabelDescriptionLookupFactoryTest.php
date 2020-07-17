<?php

namespace Wikibase\Lib\Tests\Formatters;

use InvalidArgumentException;
use Language;
use OutOfBoundsException;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Services\Lookup\LanguageLabelDescriptionLookup;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\Lib\Formatters\FormatterLabelDescriptionLookupFactory;
use Wikibase\Lib\LanguageWithConversion;
use Wikibase\Lib\TermLanguageFallbackChain;

/**
 * @covers \Wikibase\Lib\Formatters\FormatterLabelDescriptionLookupFactory
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class FormatterLabelDescriptionLookupFactoryTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @dataProvider provideGetLabelDescriptionLookup
	 */
	public function testGetLabelDescriptionLookup( TermLookup $termLookup, FormatterOptions $options, $expectedLabel ) {
		$factory = new FormatterLabelDescriptionLookupFactory( $termLookup );
		$labelDescriptionLookup = $factory->getLabelDescriptionLookup( $options );

		$this->assertInstanceOf( LabelDescriptionLookup::class, $labelDescriptionLookup );

		$term = $labelDescriptionLookup->getLabel( new ItemId( 'Q1' ) );
		$this->assertEquals( $expectedLabel, $term->getText() );
	}

	public function provideGetLabelDescriptionLookup() {
		$termLookup = $this->createMock( TermLookup::class );

		$termLookup->expects( $this->any() )
			->method( 'getLabel' )
			->will( $this->returnCallback( function ( $item, $language ) {
				if ( $language === 'de' ) {
					return 'Kätzchen';
				}

				throw new OutOfBoundsException( 'no bananas' );
			} ) );

		$termLookup->expects( $this->any() )
			->method( 'getLabels' )
			->will( $this->returnValue( [ 'de' => 'Kätzchen' ] ) );

		$labelDescriptionLookup = new LanguageLabelDescriptionLookup( $termLookup, 'de' );

		$deChChain = new TermLanguageFallbackChain( [
			LanguageWithConversion::factory( 'de-ch' ),
			LanguageWithConversion::factory( 'de' ),
		] );

		$frChain = new TermLanguageFallbackChain( [
			LanguageWithConversion::factory( 'fr' ),
		] );

		return [
			'language' => [
				$termLookup,
				new FormatterOptions( [
					ValueFormatter::OPT_LANG => 'de',
				] ),
				'Kätzchen'
			],
			'language and fallback chain' => [
				$termLookup,
				new FormatterOptions( [
					ValueFormatter::OPT_LANG => 'fr',
					FormatterLabelDescriptionLookupFactory::OPT_LANGUAGE_FALLBACK_CHAIN => $deChChain,
				] ),
				'Kätzchen'
			],
		];
	}

	/**
	 * @dataProvider provideGetLabelDescriptionLookup_failure
	 */
	public function testGetLabelDescriptionLookup_failure( FormatterOptions $options ) {
		$termLookup = $this->createMock( TermLookup::class );
		$factory = new FormatterLabelDescriptionLookupFactory( $termLookup );

		$this->expectException( InvalidArgumentException::class );
		$factory->getLabelDescriptionLookup( $options );
	}

	public function provideGetLabelDescriptionLookup_failure() {
		return [
			'bad language' => [
				new FormatterOptions( [
					ValueFormatter::OPT_LANG => Language::factory( 'en' ),
				] ),
			],
			'bad fallback chain' => [
				new FormatterOptions( [
					FormatterLabelDescriptionLookupFactory::OPT_LANGUAGE_FALLBACK_CHAIN => [ 'x', 'y', 'z' ],
				] ),
			],
			'no options' => [
				new FormatterOptions( [] ),
			],
		];
	}

}
