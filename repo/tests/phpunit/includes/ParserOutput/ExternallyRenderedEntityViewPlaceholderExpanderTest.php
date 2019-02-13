<?php

namespace Wikibase\Repo\Tests\ParserOutput;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\ParserOutput\ExternallyRenderedEntityViewPlaceholderExpander;
use Wikibase\Repo\ParserOutput\TermboxView;

/**
 * @covers \Wikibase\Repo\ParserOutput\ExternallyRenderedEntityViewPlaceholderExpander
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

	/**
	 * @expectedException \RuntimeException
	 */
	public function testGivenUnknownPlaceholder_getHtmlForPlaceholderThrows() {
		( new ExternallyRenderedEntityViewPlaceholderExpander( '' ) )
			->getHtmlForPlaceholder( 'unknown-placeholder' );
	}

}
