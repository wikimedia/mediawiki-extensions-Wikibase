<?php

namespace Wikibase\Repo\Tests\ParserOutput;

use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Repo\MediaWikiLanguageDirectionalityLookup;
use Wikibase\Repo\ParserOutput\FallbackHintHtmlTermRenderer;

/**
 * @covers Wikibase\Repo\ParserOutput\FallbackHintHtmlTermRenderer
 *
 * @license GNU GPL v2+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class FallbackHintHtmlTermRendererTest extends PHPUnit_Framework_TestCase {

	private function newHtmlTermRenderer() {
		$languageDirectionalityLookup = new MediaWikiLanguageDirectionalityLookup();
		$languageNameLookup = $this->getMock( LanguageNameLookup::class );

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
				'<span lang="de" dir="ltr">lkt &amp; term</span><sup class="wb-language-fallback-indicator wb-language-fallback-variant"></sup>'
			],
			[
				new TermFallback(
					'en',
					'arc term',
					'arc',
					'arc'
				),
				'<span lang="arc" dir="rtl">arc term</span><sup class="wb-language-fallback-indicator"></sup>'
			],
		];
	}

	/**
	 * @dataProvider provideRenderTerm
	 */
	public function testRenderTerm( Term $term, $expected ) {
		$result = $this->newHtmlTermRenderer()->renderTerm( $term );

		$this->assertSame( $result, $expected );
	}

}
