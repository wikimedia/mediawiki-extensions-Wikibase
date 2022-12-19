<?php

namespace Wikibase\Repo\Tests\ParserOutput;

use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Repo\ParserOutput\FallbackHintHtmlTermRenderer;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\ParserOutput\FallbackHintHtmlTermRenderer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class FallbackHintHtmlTermRendererTest extends \PHPUnit\Framework\TestCase {

	private function newHtmlTermRenderer() {
		$languageDirectionalityLookup = WikibaseRepo::getLanguageDirectionalityLookup();
		$languageNameLookup = $this->createMock( LanguageNameLookup::class );

		return new FallbackHintHtmlTermRenderer(
			$languageDirectionalityLookup,
			$languageNameLookup
		);
	}

	public function provideRenderTerm() {
		return [
			[ new Term( 'lkt', 'lkt term' ), 'lkt term' ],
			[ new Term( 'lkt', 'lkt & term' ), 'lkt &amp; term' ],
			[ new TermFallback( 'lkt', 'lkt & term', 'lkt', 'lkt' ), 'lkt &amp; term' ],
			[
				new TermFallback(
					'de-at',
					'lkt & term',
					'de',
					'de'
				),
				'<span lang="de" dir="ltr">lkt &amp; term</span><sup '
					. "class=\"wb-language-fallback-indicator wb-language-fallback-variant\">\u{00A0}</sup>",
			],
			[
				new TermFallback(
					'en',
					'arc term',
					'arc',
					'arc'
				),
				'<span lang="arc" dir="rtl">arc term</span><sup '
					. "class=\"wb-language-fallback-indicator\">\u{00A0}</sup>",
			],
		];
	}

	/**
	 * @dataProvider provideRenderTerm
	 */
	public function testRenderTerm( Term $term, $expected ) {
		$result = $this->newHtmlTermRenderer()->renderTerm( $term );

		$this->assertSame( $expected, $result );
	}

}
