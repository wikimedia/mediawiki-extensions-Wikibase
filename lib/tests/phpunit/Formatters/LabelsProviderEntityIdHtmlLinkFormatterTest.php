<?php

namespace Wikibase\Lib\Tests\Formatters;

use HamcrestPHPUnitIntegration;
use MediaWikiIntegrationTestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\Lib\Formatters\LabelsProviderEntityIdHtmlLinkFormatter;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\Store\EntityExistenceChecker;
use Wikibase\Lib\Store\EntityRedirectChecker;
use Wikibase\Lib\Store\EntityTitleTextLookup;
use Wikibase\Lib\Store\EntityUrlLookup;

/**
 * @covers \Wikibase\Lib\Formatters\LabelsProviderEntityIdHtmlLinkFormatter
 *
 * @group ValueFormatters
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class LabelsProviderEntityIdHtmlLinkFormatterTest extends MediaWikiIntegrationTestCase {
	use HamcrestPHPUnitIntegration;

	private const DEFAULT_URL = '/wiki/Q42';

	private $labelDescriptionLookup;
	private $languageNameLookup;
	private $existenceChecker;
	private $titleTextFormatter;
	private $urlLookup;
	private $redirectChecker;

	protected function setUp(): void {
		parent::setUp();

		$this->labelDescriptionLookup = $this->newMockLabelDescriptionLookup();
		$this->languageNameLookup = $this->newMockLanguageNameLookup();
		$this->existenceChecker = $this->newMockExistenceChecker();
		$this->titleTextFormatter = $this->newMockTitleTextFormatter();
		$this->urlLookup = $this->newMockUrlLookup();
		$this->redirectChecker = $this->newMockRedirectChecker();
	}

	public function formatProvider() {
		return [
			'has a label' => [
				'expectedPattern' => '@href="' . self::DEFAULT_URL . '".*>A label<@',
				'label' => new Term( 'en', 'A label' ),
			],
			"has no label" => [
				'expectedPattern' => '@href="' . self::DEFAULT_URL . '".*>Q42<@',
				'label' => null,
			],
			'has a title' => [
				'expectedPattern' => '@title="A title".*@',
				'label' => null,
				'title' => 'A title',
			],
			"has no title" => [
				'expectedPattern' => '@a href="' . self::DEFAULT_URL . '">Q42<@',
				'label' => null,
				'title' => null,
			],
			"doesn't exist, lookup labels" => [
				'expectedPattern' => '@^Q42' . preg_quote( wfMessage( 'word-separator' )->text(), '@' ) . '.*>' .
					preg_quote( wfMessage( 'parentheses', wfMessage( 'wikibase-deletedentity-item' )->text() )->text(), '@' ) .
					'<@',
				'label' => null,
				'title' => null,
				'exists' => false,
			],
		];
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
			'plain term' => [
				'expectedPattern' => '@>Kätzchen<@',
				'term' => $deTerm,
			],
			'plain fallabck term' => [
				'expectedPattern' => '@>Kätzchen<@',
				'term' => $deTermFallback,
			],
			'fallback to base' => [
				'expectedPattern' => '@ lang="de">Kätzchen</a>'
					. "<sup class=\"wb-language-fallback-indicator wb-language-fallback-variant\">\u{00A0}Deutsch</sup>@",
				'term' => $deAtTerm,
			],
			'fallback to variant' => [
				'expectedPattern' => '@ lang="de-at">Kätzchen</a>'
					. "<sup class=\"wb-language-fallback-indicator wb-language-fallback-variant\">\u{00A0}Österreichisches Deutsch</sup>@",
				'term' => $atDeTerm,
			],
			'transliteration to requested language' => [
				'expectedPattern' => '@>Frass</a>'
					. '<sup class="wb-language-fallback-indicator wb-language-fallback-transliteration">'
					. "\u{00A0}" . preg_quote( $translitDeCh, '@' )
					. '</sup>@',
				'term' => $deChTerm,
			],
			'transliteration to other variant' => [
				'expectedPattern' => '@ lang="en">Kitten</a>'
					. '<sup class="wb-language-fallback-indicator wb-language-fallback-transliteration '
					. 'wb-language-fallback-variant">'
					. "\u{00A0}" . preg_quote( $translitEnCa, '@' )
					. '</sup>@',
				'term' => $enGbEnCaTerm,
			],
			'fallback to alternative language' => [
				'expectedPattern' => '@ lang="en">Kitten</a>'
					. "<sup class=\"wb-language-fallback-indicator\">\u{00A0}english in german</sup>@",
				'term' => $deEnTerm,
			],
		];
	}

	/**
	 * @dataProvider formatProvider
	 */
	public function testFormat( $expectedPattern, $label, $title = null, $exists = true ) {
		$entityId = new ItemId( 'Q42' );
		$this->labelDescriptionLookup = $this->newMockLabelDescriptionLookup( $label, $entityId );
		$this->existenceChecker = $this->newMockExistenceChecker( $exists, $entityId );
		$this->titleTextFormatter = $this->newMockTitleTextFormatter( $title, $entityId );

		$result = $this->newFormatter()->formatEntityId( $entityId );

		$this->assertMatchesRegularExpression( $expectedPattern, $result );
	}

	/**
	 * @dataProvider formatProvider_fallback
	 */
	public function testFormat_fallback( $expectedPattern, Term $term ) {
		$entityId = new ItemId( 'Q42' );
		$this->labelDescriptionLookup = $this->newMockLabelDescriptionLookup( $term, $entityId );

		$result = $this->newFormatter()->formatEntityId( $entityId );

		$this->assertMatchesRegularExpression( $expectedPattern, $result );
	}

	public function testGivenEntityIdWithNullTitle_htmlForNonExistentEntityIsDisplayed() {
		$entityId = new ItemId( 'Q42' );
		$this->existenceChecker = $this->newMockExistenceChecker( false, $entityId );

		$expectedPattern = '@^Q42' . preg_quote( wfMessage( 'word-separator' )->text(), '@' ) . '.*>' .
			preg_quote( wfMessage( 'parentheses', wfMessage( 'wikibase-deletedentity-item' )->text() )->text(), '@' ) .
			'<@';

		$this->assertMatchesRegularExpression( $expectedPattern, $this->newFormatter()->formatEntityId( $entityId ) );
	}

	public function testUsesUrlFromLookup() {
		$url = '/wiki/Q23';
		$entityId = new ItemId( 'Q23' );
		$this->urlLookup = $this->newMockUrlLookup( $url, $entityId );

		$expectedPattern = '@href="' . $url . '".*>@';

		$this->assertMatchesRegularExpression( $expectedPattern, $this->newFormatter()->formatEntityId( $entityId ) );
	}

	public function testFormat_redirectHasClass() {
		$entityId = new ItemId( 'Q42' );
		$this->redirectChecker = $this->newMockRedirectChecker( true, $entityId );

		$this->assertThatHamcrest(
			$this->newFormatter()->formatEntityId( $entityId ),
			htmlPiece( havingChild( withClass( 'mw-redirect' ) ) )
		);
	}

	private function newFormatter(): LabelsProviderEntityIdHtmlLinkFormatter {
		return new LabelsProviderEntityIdHtmlLinkFormatter(
			$this->labelDescriptionLookup,
			$this->languageNameLookup,
			$this->existenceChecker,
			$this->titleTextFormatter,
			$this->urlLookup,
			$this->redirectChecker
		);
	}

	private function newMockLabelDescriptionLookup( Term $label = null, ItemId $entityId = null ): LabelDescriptionLookup {
		$labelDescriptionLookup = $this->createMock( LabelDescriptionLookup::class );
		$labelDescriptionLookup
			->method( 'getLabel' )
			->with( $entityId ?? $this->anything() )
			->willReturn( $label );

		return $labelDescriptionLookup;
	}

	private function newMockLanguageNameLookup(): LanguageNameLookup {
		$languageNameLookup = $this->createMock( LanguageNameLookup::class );
		$languageNameLookup->method( 'getName' )
			->willReturnCallback(
				function ( $languageCode ) {
					$names = [
						'de' => 'Deutsch',
						'de-at' => 'Österreichisches Deutsch',
						'de-ch' => 'Schweizer Hochdeutsch',
						'en' => 'english in german',
						'en-ca' => 'Canadian English',
					];
					return $names[$languageCode];
				}
			);

		return $languageNameLookup;
	}

	private function newMockExistenceChecker( bool $exists = true, EntityId $entityId = null ): EntityExistenceChecker {
		$entityExistenceChecker = $this->createMock( EntityExistenceChecker::class );
		$entityExistenceChecker
			->method( 'exists' )
			->with( $entityId ?? $this->anything() )
			->willReturn( $exists );

		return $entityExistenceChecker;
	}

	private function newMockTitleTextFormatter( string $titleText = null, EntityId $entityId = null ): EntityTitleTextLookup {
		$titleTextFormatter = $this->createMock( EntityTitleTextLookup::class );
		$titleTextFormatter
			->method( 'getPrefixedText' )
			->with( $entityId ?? $this->anything() )
			->willReturn( $titleText );

		return $titleTextFormatter;
	}

	private function newMockUrlLookup( string $url = self::DEFAULT_URL, EntityId $entityId = null ): EntityUrlLookup {
		$urlLookup = $this->createMock( EntityUrlLookup::class );
		$urlLookup->method( 'getLinkUrl' )
			->with( $entityId ?? $this->anything() )
			->willReturn( $url );
		return $urlLookup;
	}

	private function newMockRedirectChecker( bool $isRedirect = false, EntityId $entityId = null ): EntityRedirectChecker {
		$entityRedirectChecker = $this->createMock( EntityRedirectChecker::class );
		$entityRedirectChecker
			->method( 'isRedirect' )
			->with( $entityId ?? $this->anything() )
			->willReturn( $isRedirect );

		return $entityRedirectChecker;
	}
}
