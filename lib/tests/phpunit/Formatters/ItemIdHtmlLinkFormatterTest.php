<?php

namespace Wikibase\Lib\Tests\Formatters;

use HamcrestPHPUnitIntegration;
use MediaWikiTestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookupException;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\Lib\ItemIdHtmlLinkFormatter;
use Wikibase\Lib\LanguageFallbackIndicator;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\NonExistingEntityIdHtmlFormatter;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup;

/**
 * @covers \Wikibase\Lib\ItemIdHtmlLinkFormatter
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

	private $currentUserLanguage;
	/** @var string[] List of fallback languages */
	protected $fallbackChain = [];

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
							'de' => 'German'
						],
						'de' => [
							'en' => 'Englisch',
							'de' => 'Deutsch'
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
			new TermFallback('de', 'Label in English', 'en', 'en')
		);
		$this->assertContains( $fallbackMarker, $result );
	}

	public function formatProvider_fallback() {
		$deTerm = new Term( 'de', 'Kätzchen' );
		$deTermFallback = new TermFallback( 'de', 'Kätzchen', 'de', 'de' );
		$deAtTerm = new TermFallback( 'de-at', 'Kätzchen', 'de', 'de' );
		$atDeTerm = new TermFallback( 'de', 'Kätzchen', 'de-at', 'de-at' );
		$deChTerm = new TermFallback( 'de-ch', 'Frass', 'de-ch', 'de' );
		$enGbEnCaTerm = new TermFallback( 'en-gb', 'Kitten', 'en', 'en-ca' );
		$deEnTerm = new TermFallback( 'de', 'Kitten', 'en', 'en' );

		$translitDeCh = wfMessage( 'wikibase-language-fallback-transliteration-hint', 'Deutsch', 'Schweizer Hochdeutsch' )->text();
		$translitEnCa = wfMessage( 'wikibase-language-fallback-transliteration-hint', 'Canadian English', 'English' )->text();

		return [
			'fallback to base' => [
				'expectedRegex' => '@ lang="de">Kätzchen</a><sup class="wb-language-fallback-'
					. 'indicator wb-language-fallback-variant">Deutsch</sup>@',
				'term' => $deAtTerm,
			],
			'fallback to variant' => [
				'expectedRegex' => '@ lang="de-at">Kätzchen</a><sup class="wb-language-fallback-'
					. 'indicator wb-language-fallback-variant">Österreichisches Deutsch</sup>@',
				'term' => $atDeTerm,
			],
			'transliteration to requested language' => [
				'expectedRegex' => '@>Frass</a><sup class="wb-language-fallback-'
					. 'indicator wb-language-fallback-transliteration">'
					. preg_quote( $translitDeCh, '@' )
					. '</sup>@',
				'term' => $deChTerm,
			],
			'transliteration to other variant' => [
				'expectedRegex' => '@ lang="en">Kitten</a><sup class="wb-language-fallback-'
					. 'indicator wb-language-fallback-transliteration wb-language-fallback-'
					. 'variant">'
					. preg_quote( $translitEnCa, '@' )
					. '</sup>@',
				'term' => $enGbEnCaTerm,
			],
			'fallback to alternative language' => [
				'expectedRegex' => '@ lang="en">Kitten</a><sup class="wb-language-fallback-'
					. 'indicator">english in german</sup>@',
				'term' => $deEnTerm,
			],
		];
	}

	/**
	 * @dataProvider formatProvider_fallback
	 */
	public function testFormat_fallback( $expectedRegex, Term $term ) {
		$entityIdHtmlLinkFormatter = $this->getFormatter( true, true, $term );

		$result = $entityIdHtmlLinkFormatter->formatEntityId( new ItemId( 'Q42' ) );

		$this->assertRegExp( $expectedRegex, $result );
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
		$this->setUserLang( 'en' );

		$localTitle = $this->getMock( Title::class );
		$localTitle->expects( $this->any() )
			->method( 'isLocal' )
			->will( $this->returnValue( true ) );
		$localTitle->expects( $this->any() )
			->method( 'getLocalUrl' )
			->will( $this->returnValue( '/wiki/Q42' ) );

		$foreignTitle = $this->getMock( Title::class );
		$foreignTitle->expects( $this->any() )
			->method( 'isLocal' )
			->will( $this->returnValue( false ) );
		$foreignTitle->expects( $this->any() )
			->method( 'getFullUrl' )
			->will( $this->returnValue( 'http://foo.wiki/wiki/Q42' ) );

		$entityTitleLookup = $this->getMock( EntityTitleLookup::class );
		$entityTitleLookup->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnCallback( function ( EntityId $id ) use ( $localTitle, $foreignTitle ) {
				return $id->isForeign() ? $foreignTitle : $localTitle;
			} ) );

		$formatter = new ItemIdHtmlLinkFormatter(
			$this->getLabelDescriptionLookup( new Term( 'en', 'Something' ) ),
			$entityTitleLookup,
			$this->getMock( LanguageNameLookup::class )
		);

		$this->assertRegExp( '|"http://foo.wiki/wiki/Q42".*>Something<|', $formatter->formatEntityId( new ItemId( 'foo:Q42' ) ) );
		$this->assertRegExp( '|"/wiki/Q42".*>Something<|', $formatter->formatEntityId( new ItemId( 'Q42' ) ) );
	}

	public function testFormat_redirectHasClass() {
		$exists = true;
		$isRedirect = true;
		$entityTitleLookup = $this->newEntityTitleLookup( $exists, $isRedirect );
		$formatter = new ItemIdHtmlLinkFormatter(
			$this->getLabelDescriptionLookup(),
			$entityTitleLookup,
			$this->getMock( LanguageNameLookup::class )
		);

		$formattedEntityId = $formatter->formatEntityId( new ItemId( 'Q42' ) );

		$this->assertThatHamcrest( $formattedEntityId, htmlPiece( havingChild( withClass( 'mw-redirect' ) ) ) );
	}

	/**
	 * @param Term|null $term
	 *
	 * @return LabelDescriptionLookup
	 */
	private function getLabelDescriptionLookup( Term $term = null ) {
		$labelDescriptionLookup = $this->createMock(
			LanguageFallbackLabelDescriptionLookup::class
		);
		$labelDescriptionLookup->expects( $this->any() )
			->method( 'getLabel' )
			->will( $this->returnValue( $term ?: new Term( 'xy', 'A label' ) ) );

		return $labelDescriptionLookup;
	}

	/**
	 * @return LabelDescriptionLookup
	 */
	private function getLabelDescriptionLookupNoLabel() {
		$labelDescriptionLookup = $this->getMock( LabelDescriptionLookup::class );
		$labelDescriptionLookup->expects( $this->any() )
			->method( 'getLabel' )
			->will(
				$this->throwException(
					new LabelDescriptionLookupException(
						new ItemId( 'Q100' ),
						'meep'
					)
				)
			);

		return $labelDescriptionLookup;
	}

	private function getFormatter( $hasLabel, $exists, Term $term = null ) {
		if ( $hasLabel ) {
			$labelDescriptionLookup = $this->getLabelDescriptionLookup( $term );
		} else {
			$labelDescriptionLookup = $this->getLabelDescriptionLookupNoLabel();
		}

		$entityTitleLookup = $this->newEntityTitleLookup( $exists );

		$languageNameLookup = $this->getMock( LanguageNameLookup::class );
		$languageNameLookup->expects( $this->any() )
			->method( 'getName' )
			->will(
				$this->returnCallback(
					function ( $languageCode ) {
						$names = [
							'de' => 'Deutsch',
							'de-at' => 'Österreichisches Deutsch',
							'de-ch' => 'Schweizer Hochdeutsch',
							'en' => 'english in german',
							'en-ca' => 'Canadian English'
						];
						return $names[ $languageCode ];
					}
				)
			);

		$itemIdHtmlLinkFormatter = new ItemIdHtmlLinkFormatter(
			$labelDescriptionLookup,
			$entityTitleLookup,
			$languageNameLookup
		);

		return $itemIdHtmlLinkFormatter;
	}

	/**
	 * @param bool $exists
	 * @param bool $isRedirect
	 *
	 * @return EntityTitleLookup
	 */
	private function newEntityTitleLookup( $exists = true, $isRedirect = false ) {
		$entityTitleLookup = $this->getMock( EntityTitleLookup::class );
		$entityTitleLookup->expects( $this->any() )
			->method( 'getTitleForId' )
			->will(
				$this->returnCallback(
					function ( EntityId $id ) use ( $exists, $isRedirect ) {
						$title = Title::newFromText( $id->getSerialization() );
						$title->resetArticleID( $exists ? $id->getNumericId() : 0 );
						$title->mRedirect = $isRedirect;

						return $title;
					}
				)
			);

		return $entityTitleLookup;
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

	private function dummy( $class ) {
		return $this->prophesize( $class )->reveal();
	}

	/**
	 * @param $itemId
	 * @return string
	 */
	private function itemPageUrl( $itemId ) {
		return Title::newFromText( $itemId )->getLocalURL();
	}

	private function givenUserLanguageIs( $languageCode ) {
		$this->setUserLang( 'qqx' );
		$this->currentUserLanguage = $languageCode;

		return new class( $this ) extends ItemIdHtmlLinkFormatterTest {

			public function __construct( $testCase ) {
				$this->testCase = $testCase;
			}

			public function withFallbackChain( ...$languageCodes ) {
				$this->testCase->fallbackChain = $languageCodes;
			}

		};
	}

	private function givenItemHasLabel( $itemId, $labelLanguage, $labelText ) {
		$this->givenItemExists( 'Q1' );

		$currentLanguage = &$this->currentUserLanguage;
		$fallbackChain = &$this->fallbackChain;

		$this->labelDescriptionLookup
			->getLabel( new ItemId( $itemId ) )
			->will(
				function () use ( $labelLanguage, $labelText, &$currentLanguage, &$fallbackChain ) {
					if ( $labelLanguage === $currentLanguage ) {
						return new TermFallback(
							$labelLanguage,
							$labelText,
							$labelLanguage,
							$labelLanguage
						);
					}
					if ( in_array( $labelLanguage, $fallbackChain ) ) {
						return new TermFallback(
							$currentLanguage,
							$labelText,
							$labelLanguage,
							$labelLanguage
						);
					} else {
						return null;
					}
				}
			);
	}

	/**
	 * @param string $itemId
	 */
	private function givenItemDoesNotExist( $itemId ) {
		$this->entityTitleLookup->getTitleForId( new ItemId( $itemId ) )->willReturn( null );
	}

	/**
	 * @param string $itemId
	 */
	private function givenItemExists( $itemId ) {
		$title = Title::newFromText( $itemId );
		$title->resetArticleID( ( new ItemId( $itemId ) )->getNumericId() );
		$title->mRedirect = false;

		$this->entityTitleLookup->getTitleForId( new ItemId( $itemId ) )->willReturn( $title );
	}

}
