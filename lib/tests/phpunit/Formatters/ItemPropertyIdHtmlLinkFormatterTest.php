<?php

namespace Wikibase\Lib\Tests\Formatters;

use HamcrestPHPUnitIntegration;
use MediaWikiIntegrationTestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\Lib\Formatters\ItemPropertyIdHtmlLinkFormatter;
use Wikibase\Lib\Formatters\NonExistingEntityIdHtmlBrokenLinkFormatter;
use Wikibase\Lib\Formatters\NonExistingEntityIdHtmlFormatter;
use Wikibase\Lib\LanguageFallbackIndicator;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\EntityTitleTextLookup;
use Wikibase\Lib\Store\EntityUrlLookup;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup;

/**
 * @covers \Wikibase\Lib\Formatters\ItemPropertyIdHtmlLinkFormatter
 *
 * @group ValueFormatters
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class ItemPropertyIdHtmlLinkFormatterTest extends MediaWikiIntegrationTestCase {
	use HamcrestPHPUnitIntegration;

	/** @var EntityTitleLookup|ObjectProphecy */
	private $entityTitleLookup;

	/** @var LanguageFallbackLabelDescriptionLookup|ObjectProphecy */
	private $labelDescriptionLookup;

	/** @var LanguageNameLookup|ObjectProphecy */
	private $languageNameLookup;

	protected $currentUserLanguage;
	/** @var string[] List of fallback languages */
	protected $fallbackChain = [];

	/**
	 * @var string[] Some languages can be transliterated from other languages(source language).
	 *                    This is map from source language (index) to another language
	 */
	protected $transliterationMap = [];

	/**
	 * @var NonExistingEntityIdHtmlFormatter
	 */
	private $nonExistingEntityIdHtmlFormatter;

	const SOME_TRANSLITERATED_TEXT = 'some-transliterated-text';

	protected function setUp(): void {
		parent::setUp();

		$this->nonExistingEntityIdHtmlFormatter = new NonExistingEntityIdHtmlFormatter( 'wikibase-deletedentity-' );
		$this->entityTitleLookup = $this->prophesize( EntityTitleLookup::class );
		$this->labelDescriptionLookup = $this->prophesize(
			LanguageFallbackLabelDescriptionLookup::class
		);
		$this->languageNameLookup = $this->prophesize( LanguageNameLookup::class );

		$currentUserLanguage = &$this->currentUserLanguage;

		$this->languageNameLookup->getName( Argument::any() )
			->will(
				function ( $args ) use ( &$currentUserLanguage ) {
					$languageCode = $args[0];
					$languageNamesIn = [
						'en' => [
							'en' => 'English',
							'de' => 'German',
						],
						'de' => [
							'en' => 'Englisch',
							'de' => 'Deutsch',
						],
						'crh-latn' => [
							'en' => 'en in crh-latn',
							'crh-latn' => 'crh-latn in crh-latn',
							'crh-cyrl' => 'crh-cyrl in crh-latn',
						],
					];

					if ( !isset( $languageNamesIn[ $currentUserLanguage ][ $languageCode ] ) ) {
						throw new \Exception(
							"Language name for `{$languageCode}` "
							. "in `{$currentUserLanguage}` is not found"
						);
					}

					return $languageNamesIn[ $currentUserLanguage ][ $languageCode ];
				}
			);
	}

	public function testGivenItemExists_ResultingLinkPointsToItemPage() {
		$this->givenItemExists( 'Q42' );

		$entityIdHtmlLinkFormatter = $this->createFormatter();
		$result = $entityIdHtmlLinkFormatter->formatEntityId( new ItemId( 'Q42' ) );

		$expectedUrl = $this->itemPageUrl( 'Q42' );
		$this->assertThatHamcrest(
			$result,
			is( htmlPiece( havingDirectChild(
				tagMatchingOutline( "<a href=\"${expectedUrl}\"/>" ) )
		) ) );
	}

	public function testItemHasLabelInUserLanguage_ResultingLinkHasLabelAsAText() {
		$this->givenUserLanguageIs( 'en' );
		$this->givenItemHasLabel( 'Q1', 'en', 'Some label' );

		$entityIdHtmlLinkFormatter = $this->createFormatter();
		$result = $entityIdHtmlLinkFormatter->formatEntityId( new ItemId( 'Q1' ) );

		$this->assertThatHamcrest(
			$result,
			is( htmlPiece( havingChild(
						both( withTagName( 'a' ) )
							->andAlso( havingTextContents( 'Some label' ) )
			) ) )
		);
	}

	public function testItemDoesNotHaveLabelInUserLanguage_ResultingLinkUsesIdAsAText() {
		$this->givenUserLanguageIs( 'en' );
		$this->givenItemExists( 'Q1' );

		$entityIdHtmlLinkFormatter = $this->createFormatter();
		$result = $entityIdHtmlLinkFormatter->formatEntityId( new ItemId( 'Q1' ) );

		$this->assertThatHamcrest(
			$result,
			is( htmlPiece( havingChild(
						both( withTagName( 'a' ) )
							->andAlso( havingTextContents( 'Q1' ) )
			) ) )
		);
	}

	public function testItemDoesNotExist_DelegatesFormattingToNonExistingEntityIdHtmlFormatter() {
		$this->givenItemDoesNotExist( 'Q1' );

		$entityIdHtmlLinkFormatter = $this->createFormatter();
		$result = $entityIdHtmlLinkFormatter->formatEntityId( new ItemId( 'Q1' ) );

		$expectedResult = $this->nonExistingEntityIdHtmlFormatter->formatEntityId( new ItemId( 'Q1' ) );
		$this->assertEquals( $expectedResult, $result );
	}

	public function testGivenLabelInFallbackLanguageExists_UsesThatLabelAsTheText() {
		$this->givenUserLanguageIs( 'de' )
			->withFallbackChain( 'en' );
		$this->givenItemHasLabel( 'Q1', 'en', 'Label in English' );

		$entityIdHtmlLinkFormatter = $this->createFormatter();
		$result = $entityIdHtmlLinkFormatter->formatEntityId( new ItemId( 'Q1' ) );

		$this->assertThatHamcrest(
			$result,
			is( htmlPiece( havingChild(
			   both( withTagName( 'a' ) )
				   ->andAlso( havingTextContents( 'Label in English' ) )
			) ) )
		);
	}

	public function testGivenLabelInFallbackLanguageExists_LinkHasLangAttributeSet() {
		$this->givenUserLanguageIs( 'de' )
			->withFallbackChain( $fallbackLanguage = 'en' );
		$this->givenItemHasLabel( 'Q1', $fallbackLanguage, 'some text' );

		$entityIdHtmlLinkFormatter = $this->createFormatter();
		$result = $entityIdHtmlLinkFormatter->formatEntityId( new ItemId( 'Q1' ) );

		$this->assertThatHamcrest(
			$result,
			is( htmlPiece( havingChild(
			   both( withTagName( 'a' ) )
				   ->andAlso( withAttribute( 'lang' )->havingValue( $fallbackLanguage ) )
			) ) )
		);
	}

	public function testGivenLabelInFallbackLanguageExists_ResultContainsFallbackMarker() {
		$this->givenUserLanguageIs( 'de' )
			->withFallbackChain( 'en' );
		$this->givenItemHasLabel( 'Q1', 'en', 'Label in English' );

		$entityIdHtmlLinkFormatter = $this->createFormatter();
		$result = $entityIdHtmlLinkFormatter->formatEntityId( new ItemId( 'Q1' ) );

		$languageFallbackIndicator = new LanguageFallbackIndicator(
			$this->languageNameLookup->reveal()
		);
		$fallbackMarker = $languageFallbackIndicator->getHtml(
			new TermFallback( 'de', 'Label in English', 'en', 'en' )
		);
		$this->assertStringContainsString( $fallbackMarker, $result );
	}

	public function testGivenLabelInTransliteratableLanguageExists_ResultContainsFallbackMarker() {
		$this->givenUserLanguageIs( 'crh-latn' )
			->canBeTransliteratedFrom( 'crh-cyrl' );
		$this->givenItemHasLabel( 'Q1', 'crh-cyrl', 'къырымтатарджа' );

		$entityIdHtmlLinkFormatter = $this->createFormatter();
		$result = $entityIdHtmlLinkFormatter->formatEntityId( new ItemId( 'Q1' ) );

		$languageFallbackIndicator = new LanguageFallbackIndicator( $this->languageNameLookup->reveal() );
		$fallbackMarker = $languageFallbackIndicator->getHtml(
			new TermFallback( 'crh-latn', self::SOME_TRANSLITERATED_TEXT, 'crh-latn', 'crh-cyrl' )
		);
		$this->assertStringContainsString( $fallbackMarker, $result );
	}

	public function testGivenEntityIdWithNullTitle_htmlForNonExistentEntityIsDisplayed() {
		$entityTitleLookup = $this->createMock( EntityTitleLookup::class );
		$entityTitleLookup->expects( $this->any() )
			->method( $this->anything() )
			->will( $this->returnValue( null ) );

		$formatter = new ItemPropertyIdHtmlLinkFormatter(
			$this->createMock( LanguageFallbackLabelDescriptionLookup::class ),
			$entityTitleLookup,
			$this->createMock( LanguageNameLookup::class ),
			$this->nonExistingEntityIdHtmlFormatter
		);

		$expectedPattern = '/^Q123' . preg_quote( wfMessage( 'word-separator' )->text(), '/' ) . '.*>' .
			preg_quote( wfMessage( 'parentheses', wfMessage( 'wikibase-deletedentity-item' )->text() )->text(), '/' ) .
			'</';

		$this->assertRegExp( $expectedPattern, $formatter->formatEntityId( new ItemId( 'Q123' ) ) );
	}

	public function testGivenForeignItemId_fullUrlIsUsedInTheOutput() {
		$this->givenUserLanguageIs( 'en' );
		$this->givenItemExists( 'foo:Q1' )->andIsNotLocal();
		$this->givenItemHasLabel( 'foo:Q1', 'en', 'Something' );

		$formatter = $this->createFormatter();
		$result = $formatter->formatEntityId( new ItemId( 'foo:Q1' ) );

		$isFullUrl = startsWith( 'http' );
		$this->assertThatHamcrest(
			$result,
			is( htmlPiece( havingChild(
				withAttribute( 'href' )->havingValue( $isFullUrl )
	   ) ) ) );
	}

	public function testGivenItemIsRedirect_ResultHasRedirectCssClass() {
		$this->givenItemExists( 'Q1' )->andIsRedirect();

		$entityIdHtmlLinkFormatter = $this->createFormatter();
		$result = $entityIdHtmlLinkFormatter->formatEntityId( new ItemId( 'Q1' ) );

		$this->assertThatHamcrest( $result, htmlPiece(
			havingRootElement( withClass( 'mw-redirect' ) ) ) );
	}

	public function testGivenPropertyExists_ResultingLinkPointsToPropertyPage() {
		$this->givenPropertyExists( 'P42' );

		$entityIdHtmlLinkFormatter = $this->createFormatter();
		$result = $entityIdHtmlLinkFormatter->formatEntityId( new PropertyId( 'P42' ) );

		$expectedUrl = $this->propertyPageUrl( 'P42' );
		$this->assertThatHamcrest(
			$result,
			is( htmlPiece( havingDirectChild(
					tagMatchingOutline( "<a href=\"${expectedUrl}\"/>" ) )
			) ) );
	}

	public function testPropertyHasLabelInUserLanguage_ResultingLinkHasLabelAsAText() {
		$this->givenUserLanguageIs( 'en' );
		$this->givenPropertyHasLabel( 'P1', 'en', 'Some label' );

		$entityIdHtmlLinkFormatter = $this->createFormatter();
		$result = $entityIdHtmlLinkFormatter->formatEntityId( new PropertyId( 'P1' ) );

		$this->assertThatHamcrest(
			$result,
			is( htmlPiece( havingChild(
				both( withTagName( 'a' ) )
					->andAlso( havingTextContents( 'Some label' ) )
			) ) )
		);
	}

	public function testPropertyDoesNotHaveLabelInUserLanguage_ResultingLinkUsesIdAsAText() {
		$this->givenUserLanguageIs( 'en' );
		$this->givenPropertyExists( 'P1' );

		$entityIdHtmlLinkFormatter = $this->createFormatter();
		$result = $entityIdHtmlLinkFormatter->formatEntityId( new PropertyId( 'P1' ) );

		$this->assertThatHamcrest(
			$result,
			is( htmlPiece( havingChild(
				both( withTagName( 'a' ) )
					->andAlso( havingTextContents( 'P1' ) )
			) ) )
		);
	}

	public function testPropertyDoesNotExist_DelegatesFormattingToNonExistingEntityIdHtmlBrokenLinkFormatter() {
		$this->givenPropertyDoesNotExist( 'P1' );

		$this->nonExistingEntityIdHtmlFormatter = new NonExistingEntityIdHtmlBrokenLinkFormatter(
			'wikibase-deletedentity-',
			$this->getEntityTitleTextLookup( 'P1' ),
			$this->getEntityUrlLookup()
		);

		$entityIdHtmlLinkFormatter = $this->createFormatter();
		$result = $entityIdHtmlLinkFormatter->formatEntityId( new PropertyId( 'P1' ) );

		$expectedResult = $this->nonExistingEntityIdHtmlFormatter->formatEntityId( new PropertyId( 'P1' ) );
		$this->assertEquals( $expectedResult, $result );
	}

	private function getEntityTitleTextLookup( $entityId ) {
		$entityTitleTextLookup = $this->createMock( EntityTitleTextLookup::class );
		$entityTitleTextLookup
			->expects( $this->any() )
			->method( 'getPrefixedText' )
			->willReturn( 'Property:' . $entityId );
		return $entityTitleTextLookup;
	}

	private function getEntityUrlLookup() {
		$entityUrlLookup = $this->createMock( EntityUrlLookup::class );
		$entityUrlLookup
			->expects( $this->any() )
			->method( 'getFullUrl' )
			->willReturn( 'http://someUrl.com' );
		return $entityUrlLookup;
	}

	public function testGivenPropertyLabelInFallbackLanguageExists_UsesThatLabelAsTheText() {
		$this->givenUserLanguageIs( 'de' )
			->withFallbackChain( 'en' );
		$this->givenPropertyHasLabel( 'P1', 'en', 'Label in English' );

		$entityIdHtmlLinkFormatter = $this->createFormatter();
		$result = $entityIdHtmlLinkFormatter->formatEntityId( new PropertyId( 'P1' ) );

		$this->assertThatHamcrest(
			$result,
			is( htmlPiece( havingChild(
				both( withTagName( 'a' ) )
					->andAlso( havingTextContents( 'Label in English' ) )
			) ) )
		);
	}

	public function testGivenPropertyLabelInFallbackLanguageExists_LinkHasLangAttributeSet() {
		$this->givenUserLanguageIs( 'de' )
			->withFallbackChain( $fallbackLanguage = 'en' );
		$this->givenPropertyHasLabel( 'P1', $fallbackLanguage, 'some text' );

		$entityIdHtmlLinkFormatter = $this->createFormatter();
		$result = $entityIdHtmlLinkFormatter->formatEntityId( new PropertyId( 'P1' ) );

		$this->assertThatHamcrest(
			$result,
			is( htmlPiece( havingChild(
				both( withTagName( 'a' ) )
					->andAlso( withAttribute( 'lang' )->havingValue( $fallbackLanguage ) )
			) ) )
		);
	}

	public function testGivenPropertyLabelInFallbackLanguageExists_ResultContainsFallbackMarker() {
		$this->givenUserLanguageIs( 'de' )
			->withFallbackChain( 'en' );
		$this->givenPropertyHasLabel( 'P1', 'en', 'Label in English' );

		$entityIdHtmlLinkFormatter = $this->createFormatter();
		$result = $entityIdHtmlLinkFormatter->formatEntityId( new PropertyId( 'P1' ) );

		$languageFallbackIndicator = new LanguageFallbackIndicator(
			$this->languageNameLookup->reveal()
		);
		$fallbackMarker = $languageFallbackIndicator->getHtml(
			new TermFallback( 'de', 'Label in English', 'en', 'en' )
		);
		$this->assertStringContainsString( $fallbackMarker, $result );
	}

	public function testGivenPropertyLabelInTransliteratableLanguageExists_ResultContainsFallbackMarker() {
		$this->givenUserLanguageIs( 'crh-latn' )
			->canBeTransliteratedFrom( 'crh-cyrl' );
		$this->givenPropertyHasLabel( 'P1', 'crh-cyrl', 'къырымтатарджа' );

		$entityIdHtmlLinkFormatter = $this->createFormatter();
		$result = $entityIdHtmlLinkFormatter->formatEntityId( new PropertyId( 'P1' ) );

		$languageFallbackIndicator = new LanguageFallbackIndicator( $this->languageNameLookup->reveal() );
		$fallbackMarker = $languageFallbackIndicator->getHtml(
			new TermFallback( 'crh-latn', self::SOME_TRANSLITERATED_TEXT, 'crh-latn', 'crh-cyrl' )
		);
		$this->assertStringContainsString( $fallbackMarker, $result );
	}

	public function testGivenForeignPropertyId_fullUrlIsUsedInTheOutput() {
		$this->givenUserLanguageIs( 'en' );
		$this->givenPropertyExists( 'foo:P1' )->andIsNotLocal();
		$this->givenPropertyHasLabel( 'foo:P1', 'en', 'Something' );

		$formatter = $this->createFormatter();
		$result = $formatter->formatEntityId( new PropertyId( 'foo:P1' ) );

		$isFullUrl = startsWith( 'http' );
		$this->assertThatHamcrest(
			$result,
			is( htmlPiece( havingChild(
				withAttribute( 'href' )->havingValue( $isFullUrl )
			) ) ) );
	}

	/**
	 * @return ItemPropertyIdHtmlLinkFormatter
	 */
	protected function createFormatter() {
		return new ItemPropertyIdHtmlLinkFormatter(
			$this->labelDescriptionLookup->reveal(),
			$this->entityTitleLookup->reveal(),
			$this->languageNameLookup->reveal(),
			$this->nonExistingEntityIdHtmlFormatter
		);
	}

	private function itemPageUrl( $itemId ) {
		return "/index.php/{$itemId}";
	}

	private function givenUserLanguageIs( $languageCode ) {
		$this->setUserLang( $languageCode );
		$this->currentUserLanguage = $languageCode;

		return new class( $this ) extends ItemPropertyIdHtmlLinkFormatterTest {

			public function __construct( $testCase ) {
				$this->testCase = $testCase;
			}

			public function withFallbackChain( ...$languageCodes ) {
				$this->testCase->fallbackChain = $languageCodes;
			}

			public function canBeTransliteratedFrom( $anotherLanguage ) {
				$this->testCase->transliterationMap[ $anotherLanguage ] = $this->testCase->currentUserLanguage;
			}

		};
	}

	private function givenItemHasLabel( $itemId, $labelLanguage, $labelText ) {
		$this->givenItemExists( 'Q1' );

		$this->givenEntityHasLabel( new ItemId( $itemId ), $labelLanguage, $labelText );
	}

	private function givenEntityHasLabel( EntityId $id, $labelLanguage, $labelText ) {
		$testCase = $this;
		$this->labelDescriptionLookup
			->getLabel( $id )
			->will(
				function () use (
					$labelLanguage,
					$labelText,
					$testCase
				) {
					// Here we imitate the behaviour of LanguageFallbackLabelDescriptionLookup
					$requestLanguage = $testCase->currentUserLanguage;
					if ( $labelLanguage === $requestLanguage ) {
						// The case when we have an exact match and no fallback is applied
						return new TermFallback(
							$requestLanguage,
							$labelText,
							$labelLanguage,
							$labelLanguage
						);
					}

					if ( in_array( $labelLanguage, $testCase->fallbackChain ) ) {
						// The case when we don't have an exact match and have to use label in
						// one of the languages from the fallback chain
						return new TermFallback(
							$requestLanguage,
							$labelText,
							$labelLanguage,
							$labelLanguage
						);
					}

					if (
						isset( $testCase->transliterationMap[ $labelLanguage ] ) &&
						$testCase->transliterationMap[ $labelLanguage ] === $requestLanguage
					) {
						// The case when we don't have an exact match but can transliterate label
						// in another language to the language we need.
						// @see \Wikibase\LanguageFallbackChain::extractPreferredValue
						$actualLanguageCode = $requestLanguage;
						return new TermFallback(
							$requestLanguage,
							self::SOME_TRANSLITERATED_TEXT,
							$actualLanguageCode,
							$labelLanguage
						);
					}

					return null;
				}
			);
	}

	/**
	 * @param string $itemId
	 */
	private function givenItemDoesNotExist( $itemId ) {
		$title = $this->prophesize( Title::class );
		$title->isKnown()->willReturn( false );

		$this->entityTitleLookup
			->getTitleForId( new ItemId( $itemId ) )
			->willReturn( $title->reveal() );
	}

	/**
	 * @param string $itemId
	 */
	private function givenItemExists( $itemId ) {
		$title = $this->prophesize( Title::class );
		$title->isLocal()->willReturn( true );
		$title->isKnown()->willReturn( true );
		$title->isRedirect()->willReturn( false );
		$title->getLocalURL()->willReturn( $this->itemPageUrl( $itemId ) );
		$title->getPrefixedText()->willReturn( $itemId );

		$this->entityTitleLookup->getTitleForId( new ItemId( $itemId ) )->willReturn(
			$title->reveal()
		);

		return new class( $title ) {

			public function __construct( $title ) {
				$this->title = $title;
			}

			public function andIsRedirect() {
				$this->title->isRedirect()->willReturn( true );
			}

			public function andIsNotLocal() {
				$this->title->isLocal()->willReturn( false );
				$this->title->getFullURL()->willReturn( 'http://some.url/' );
			}

		};
	}

	private function givenPropertyHasLabel( $propertyId, $labelLanguage, $labelText ) {
		$this->givenPropertyExists( 'P1' );

		$this->givenEntityHasLabel( new PropertyId( $propertyId ), $labelLanguage, $labelText );
	}

	private function propertyPageUrl( $propertyId ) {
		return "/index.php/Property:{$propertyId}";
	}

	/**
	 * @param string $propertyId
	 */
	private function givenPropertyDoesNotExist( $propertyId ) {
		$title = $this->prophesize( Title::class );
		$title->isKnown()->willReturn( false );
		$title->isLocal()->willReturn( true );

		$this->entityTitleLookup
			->getTitleForId( new PropertyId( $propertyId ) )
			->willReturn( $title->reveal() );
	}

	/**
	 * @param string $propertyId
	 */
	private function givenPropertyExists( $propertyId ) {
		$title = $this->prophesize( Title::class );
		$title->isLocal()->willReturn( true );
		$title->isKnown()->willReturn( true );
		$title->isRedirect()->willReturn( false );
		$title->getLocalURL()->willReturn( $this->propertyPageUrl( $propertyId ) );
		$title->getPrefixedText()->willReturn( $propertyId );

		$this->entityTitleLookup->getTitleForId( new PropertyId( $propertyId ) )->willReturn(
			$title->reveal()
		);

		return new class( $title ) {

			public function __construct( $title ) {
				$this->title = $title;
			}

			public function andIsNotLocal() {
				$this->title->isLocal()->willReturn( false );
				$this->title->getFullURL()->willReturn( 'http://some.url/' );
			}

		};
	}

}
