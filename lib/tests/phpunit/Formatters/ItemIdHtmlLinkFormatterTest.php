<?php

namespace Wikibase\Lib\Tests\Formatters;

use HamcrestPHPUnitIntegration;
use MediaWikiTestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Title;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\Lib\Formatters\ItemIdHtmlLinkFormatter;
use Wikibase\Lib\LanguageFallbackIndicator;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\NonExistingEntityIdHtmlFormatter;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup;

/**
 * @covers \Wikibase\Lib\Formatters\ItemIdHtmlLinkFormatter
 *
 * @group ValueFormatters
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class ItemIdHtmlLinkFormatterTest extends MediaWikiTestCase {
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

	const SOME_TRANSLITERATED_TEXT = 'some-transliterated-text';

	protected function setUp() {
		parent::setUp();

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

		$nonExistingEntityIdHtmlFormatter = new NonExistingEntityIdHtmlFormatter( 'wikibase-deletedentity-' );
		$expectedResult = $nonExistingEntityIdHtmlFormatter->formatEntityId( new ItemId( 'Q1' ) );
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
		$this->assertContains( $fallbackMarker, $result );
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
		$this->assertContains( $fallbackMarker, $result );
	}

	public function testGivenEntityIdWithNullTitle_htmlForNonExistentEntityIsDisplayed() {
		$entityTitleLookup = $this->getMock( EntityTitleLookup::class );
		$entityTitleLookup->expects( $this->any() )
			->method( $this->anything() )
			->will( $this->returnValue( null ) );

		$formatter = new ItemIdHtmlLinkFormatter(
			$this->createMock( LanguageFallbackLabelDescriptionLookup::class ),
			$entityTitleLookup,
			$this->getMock( LanguageNameLookup::class )
		);

		$expectedPattern = '/^Q123' . preg_quote( wfMessage( 'word-separator' )->text(), '/' ) . '.*>' .
			preg_quote( wfMessage( 'parentheses', wfMessage( 'wikibase-deletedentity-item' )->text() )->text(), '/' ) .
			'</';

		$this->assertRegExp( $expectedPattern, $formatter->formatEntityId( new ItemId( 'Q123' ) ) );
	}

	public function testGivenForeignEntityId_fullUrlIsUsedInTheOutput() {
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

	/**
	 * @return ItemIdHtmlLinkFormatter
	 */
	protected function createFormatter() {
		return new ItemIdHtmlLinkFormatter(
			$this->labelDescriptionLookup->reveal(),
			$this->entityTitleLookup->reveal(),
			$this->languageNameLookup->reveal()
		);
	}

	private function itemPageUrl( $itemId ) {
		return "/index.php/{$itemId}";
	}

	private function givenUserLanguageIs( $languageCode ) {
		$this->setUserLang( $languageCode );
		$this->currentUserLanguage = $languageCode;

		return new class( $this ) extends ItemIdHtmlLinkFormatterTest {

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

		$testCase = $this;

		$this->labelDescriptionLookup
			->getLabel( new ItemId( $itemId ) )
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
		$title->exists()->willReturn( false );
		$title->isLocal()->willReturn( true );

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
		$title->exists()->willReturn( true );
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

}
