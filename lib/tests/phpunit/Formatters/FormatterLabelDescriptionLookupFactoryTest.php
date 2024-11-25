<?php

namespace Wikibase\Lib\Tests\Formatters;

use InvalidArgumentException;
use MediaWiki\MediaWikiServices;
use OutOfBoundsException;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
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
	public function testGetLabelDescriptionLookup( callable $termLookupFactory, callable $optionsFactory, $expectedLabel ) {
		$itemId = new ItemId( 'Q1' );
		$redirectResolvingLatestRevisionLookup = $this->createStub( RedirectResolvingLatestRevisionLookup::class );
		$redirectResolvingLatestRevisionLookup->method( 'lookupLatestRevisionResolvingRedirect' )
			->willReturn( [
				123, // some non-null revision id
				$itemId,
			] );

		$factory = new FormatterLabelDescriptionLookupFactory(
			$termLookupFactory( $this ),
			new TermFallbackCacheFacade( new FakeCache(), 9999 ),
			$redirectResolvingLatestRevisionLookup
		);
		$labelDescriptionLookup = $factory->getLabelDescriptionLookup( $optionsFactory( $this ) );

		$this->assertInstanceOf( LabelDescriptionLookup::class, $labelDescriptionLookup );

		$term = $labelDescriptionLookup->getLabel( $itemId );
		$this->assertEquals( $expectedLabel, $term->getText() );
	}

	public static function provideGetLabelDescriptionLookup() {
		$termLookupFactory = function ( self $self ) {
			$termLookup = $self->createMock( TermLookup::class );

			$termLookup->method( 'getLabel' )
				->willReturnCallback( function ( $item, $language ) {
					if ( $language === 'de' ) {
						return 'Kätzchen';
					}

					throw new OutOfBoundsException( 'no bananas' );
				} );

			$termLookup->method( 'getLabels' )
				->willReturn( [ 'de' => 'Kätzchen' ] );

			return $termLookup;
		};

		$getDeChChain = function ( self $self ) {
			$stubContentLanguages = $self->createStub( ContentLanguages::class );
			$stubContentLanguages->method( 'hasLanguage' )
				->willReturn( true );
			return new TermLanguageFallbackChain( [
				LanguageWithConversion::factory( 'de-ch' ),
				LanguageWithConversion::factory( 'de' ),
			], $stubContentLanguages );
		};

		return [
			'language' => [
				$termLookupFactory,
				fn () => new FormatterOptions( [
					ValueFormatter::OPT_LANG => 'de',
				] ),
				'Kätzchen',
			],
			'language and fallback chain' => [
				$termLookupFactory,
				fn ( self $self ) => new FormatterOptions( [
					ValueFormatter::OPT_LANG => 'fr',
					FormatterLabelDescriptionLookupFactory::OPT_LANGUAGE_FALLBACK_CHAIN => $getDeChChain( $self ),
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

	public static function provideGetLabelDescriptionLookup_failure() {
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
