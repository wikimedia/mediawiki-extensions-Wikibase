<?php

namespace Wikibase\Lib\Tests\Formatters;

use InvalidArgumentException;
use Language;
use OutOfBoundsException;
use PHPUnit4And6Compat;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Services\Lookup\LanguageLabelDescriptionLookup;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\LanguageFallbackChain;
use Wikibase\LanguageWithConversion;
use Wikibase\Lib\FormatterLabelDescriptionLookupFactory;

/**
 * @covers Wikibase\Lib\FormatterLabelDescriptionLookupFactory
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class FormatterLabelDescriptionLookupFactoryTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

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
		$termLookup = $this->getMock( TermLookup::class );

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

		$deChChain = new LanguageFallbackChain( [
			LanguageWithConversion::factory( 'de-ch' ),
			LanguageWithConversion::factory( 'de' ),
		] );

		$frChain = new LanguageFallbackChain( [
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
			'language and fallback chain and LabelDescriptionLookup' => [
				$termLookup,
				new FormatterOptions( [
					ValueFormatter::OPT_LANG => 'fr',
					FormatterLabelDescriptionLookupFactory::OPT_LANGUAGE_FALLBACK_CHAIN => $frChain,
					FormatterLabelDescriptionLookupFactory::OPT_LABEL_DESCRIPTION_LOOKUP => $labelDescriptionLookup
				] ),
				'Kätzchen'
			],
		];
	}

	/**
	 * @dataProvider provideGetLabelDescriptionLookup_failure
	 */
	public function testGetLabelDescriptionLookup_failure( FormatterOptions $options ) {
		$termLookup = $this->getMock( TermLookup::class );
		$factory = new FormatterLabelDescriptionLookupFactory( $termLookup );

		$this->setExpectedException( InvalidArgumentException::class );
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
			'bad LabelDescriptionLookup' => [
				new FormatterOptions( [
					FormatterLabelDescriptionLookupFactory::OPT_LABEL_DESCRIPTION_LOOKUP => new LanguageFallbackChain( [] )
				] ),
			],
			'no options' => [
				new FormatterOptions( [] ),
			],
		];
	}

}
