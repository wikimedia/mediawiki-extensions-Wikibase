<?php

namespace Wikibase\Lib\Tests\Formatters;

use InvalidArgumentException;
use MediaWiki\MediaWikiServices;
use OutOfBoundsException;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Services\Lookup\LanguageLabelDescriptionLookup;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\Formatters\FormatterLabelDescriptionLookupFactory;
use Wikibase\Lib\LanguageWithConversion;
use Wikibase\Lib\Store\RedirectResolvingLatestRevisionLookup;
use Wikibase\Lib\TermFallbackCache\TermFallbackCacheFacade;
use Wikibase\Lib\TermLanguageFallbackChain;
use Wikibase\Lib\Tests\FakeCache;

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
		$itemId = new ItemId( 'Q1' );
		$redirectResolvingLatestRevisionLookup = $this->createStub( RedirectResolvingLatestRevisionLookup::class );
		$redirectResolvingLatestRevisionLookup->method( 'lookupLatestRevisionResolvingRedirect' )
			->willReturn( [
				123, // some non-null revision id
				$itemId,
			] );

		$factory = new FormatterLabelDescriptionLookupFactory(
			$termLookup,
			new TermFallbackCacheFacade( new FakeCache(), 9999 ),
			$redirectResolvingLatestRevisionLookup
		);
		$labelDescriptionLookup = $factory->getLabelDescriptionLookup( $options );

		$this->assertInstanceOf( LabelDescriptionLookup::class, $labelDescriptionLookup );

		$term = $labelDescriptionLookup->getLabel( $itemId );
		$this->assertEquals( $expectedLabel, $term->getText() );
	}

	public function provideGetLabelDescriptionLookup() {
		$termLookup = $this->createMock( TermLookup::class );

		$termLookup->method( 'getLabel' )
			->willReturnCallback( function ( $item, $language ) {
				if ( $language === 'de' ) {
					return 'Kätzchen';
				}

				throw new OutOfBoundsException( 'no bananas' );
			} );

		$termLookup->method( 'getLabels' )
			->willReturn( [ 'de' => 'Kätzchen' ] );

		$labelDescriptionLookup = new LanguageLabelDescriptionLookup( $termLookup, 'de' );

		$stubContentLanguages = $this->createStub( ContentLanguages::class );
		$stubContentLanguages->method( 'hasLanguage' )
			->willReturn( true );
		$deChChain = new TermLanguageFallbackChain( [
			LanguageWithConversion::factory( 'de-ch' ),
			LanguageWithConversion::factory( 'de' ),
		], $stubContentLanguages );

		$frChain = new TermLanguageFallbackChain( [
			LanguageWithConversion::factory( 'fr' ),
		], $stubContentLanguages );

		return [
			'language' => [
				$termLookup,
				new FormatterOptions( [
					ValueFormatter::OPT_LANG => 'de',
				] ),
				'Kätzchen',
			],
			'language and fallback chain' => [
				$termLookup,
				new FormatterOptions( [
					ValueFormatter::OPT_LANG => 'fr',
					FormatterLabelDescriptionLookupFactory::OPT_LANGUAGE_FALLBACK_CHAIN => $deChChain,
				] ),
				'Kätzchen',
			],
		];
	}

	/**
	 * @dataProvider provideGetLabelDescriptionLookup_failure
	 */
	public function testGetLabelDescriptionLookup_failure( FormatterOptions $options ) {
		$termLookup = $this->createMock( TermLookup::class );
		$factory = new FormatterLabelDescriptionLookupFactory(
			$termLookup,
			$this->createStub( TermFallbackCacheFacade::class ),
			$this->createStub( RedirectResolvingLatestRevisionLookup::class )
		);

		$this->expectException( InvalidArgumentException::class );
		$factory->getLabelDescriptionLookup( $options );
	}

	public function provideGetLabelDescriptionLookup_failure() {
		return [
			'bad language' => [
				new FormatterOptions( [
					ValueFormatter::OPT_LANG => MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'en' ),
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
