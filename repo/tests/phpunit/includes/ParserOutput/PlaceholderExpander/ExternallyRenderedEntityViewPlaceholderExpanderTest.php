<?php

namespace Wikibase\Repo\Tests\ParserOutput\PlaceholderExpander;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\ParserOutput\PlaceholderExpander\ExternallyRenderedEntityViewPlaceholderExpander;
use Wikibase\Repo\ParserOutput\TermboxView;

/**
 * @covers \Wikibase\Repo\ParserOutput\PlaceholderExpander\ExternallyRenderedEntityViewPlaceholderExpander
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ExternallyRenderedEntityViewPlaceholderExpanderTest extends TestCase {

	public function testGivenWbUiPlaceholder_getHtmlForPlaceholderReturnsMarkupBlob() {
		$html = '<div>termbox</div>';
		$expander = new ExternallyRenderedEntityViewPlaceholderExpander( $html );

		$this->assertSame( $html, $expander->getHtmlForPlaceholder( TermboxView::TERMBOX_PLACEHOLDER ) );
	}

	public function testGivenWbUiPlaceholderAndNoHtmlBlob_getHtmlForPlaceholderReturnsFallbackHtml() {
		$expander = new ExternallyRenderedEntityViewPlaceholderExpander( null );

		$this->assertSame(
			ExternallyRenderedEntityViewPlaceholderExpander::FALLBACK_HTML,
			$expander->getHtmlForPlaceholder( TermboxView::TERMBOX_PLACEHOLDER )
		);
	}

	/**
	 * @expectedException \RuntimeException
	 */
	public function testGivenUnknownPlaceholder_getHtmlForPlaceholderThrows() {
		( new ExternallyRenderedEntityViewPlaceholderExpander( '' ) )
			->getHtmlForPlaceholder( 'unknown-placeholder' );
	}

}
